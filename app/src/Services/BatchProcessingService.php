<?php

namespace App\Services;

use App\Repositories\BatchRepository;
use App\Repositories\RecordRepository;
use App\Repositories\IssueRepository;
use App\Repositories\ProposalRepository;
use App\Support\Config;

class BatchProcessingService
{
    private BatchRepository $batches;
    private RecordRepository $records;
    private IssueRepository $issues;
    private ProposalRepository $proposals;
    private LogService $log;
    private GlossaryMatcher $glossary;
    private PromptBuilder $promptBuilder;
    private OpenAIClient $openai;
    private array $taxonomyIndex;
    private string $systemPrompt;
    private int $chunkSize;
    private array $backoffIntervals;

    public function __construct()
    {
        $this->batches = new BatchRepository();
        $this->records = new RecordRepository();
        $this->issues = new IssueRepository();
        $this->proposals = new ProposalRepository();
        $this->log = new LogService();
        $this->glossary = new GlossaryMatcher();
        $this->promptBuilder = new PromptBuilder();
        $this->openai = new OpenAIClient();
        $this->taxonomyIndex = $this->promptBuilder->taxonomyIndex();
        $this->systemPrompt = $this->promptBuilder->systemPrompt();
        $processingConfig = Config::get('processing', []);
        $this->chunkSize = max(1, (int) ($processingConfig['chunk_size'] ?? 3));
        $this->backoffIntervals = $processingConfig['rate_limit_backoff'] ?? [60, 180, 300];
    }

    public function process(int $batchId): array
    {
        $batch = $this->batches->find($batchId);
        if (!$batch) {
            return ['error' => 'Batch neexistuje'];
        }

        if ($batch['status'] === 'halted') {
            return ['status' => 'halted', 'message' => 'Zpracování je pozastaveno.'];
        }

        if (!empty($batch['backoff_until'])) {
            $until = strtotime($batch['backoff_until']);
            if ($until && $until > time()) {
                return [
                    'status' => 'backoff',
                    'wait_seconds' => max(1, $until - time()),
                    'message' => $batch['status_message'] ?? 'Čekám na obnovení limitu API.',
                ];
            }
        }

        $records = $this->records->nextPendingRecords($batchId, $this->chunkSize);
        if (empty($records)) {
            $pending = $this->records->countByStatus($batchId, 'pending');
            if ($pending === 0 && $batch['status'] !== 'completed') {
                $this->finalizeBatch($batchId);
            }
            return [
                'status' => 'idle',
                'processed' => 0,
                'remaining' => $pending,
            ];
        }

        if ($batch['status'] !== 'processing') {
            $this->batches->updateStatus($batchId, 'processing');
        }

        $processed = 0;
        $actionable = 0;
        $remaining = null;

        foreach ($records as $record) {
            $this->records->markProcessing($record['id']);
            $issueText = trim($record['issue_text'] ?? '');
            $start = microtime(true);

            try {
                if ($issueText === '') {
                    $classification = $this->classifyEmpty($record, $start);
                } else {
                    $classification = $this->classifyRecord($batchId, $record, $issueText, $start);
                }
            } catch (OpenAIException $e) {
                if ($e->statusCode() === 429) {
                    $this->records->markPending($record['id']);
                    $backoff = $this->applyBackoff($batchId, $batch);
                    $this->log->append("batch-{$batchId}.log", 'Rate limit: '.($e->getMessage()));
                    $remaining = $this->records->countByStatus($batchId, 'pending');
                    return array_merge($backoff, ['processed' => $processed, 'remaining' => $remaining]);
                }
                $this->records->markError($record['id'], $e->getMessage());
                $this->log->append("batch-{$batchId}.log", 'Chyba OpenAI: '.$e->getMessage());
                continue;
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                $this->records->markError($record['id'], $message);
                $this->log->append("batch-{$batchId}.log", 'Chyba zpracování: '.$message);
                if (str_contains($message, 'OpenAI API klíč')) {
                    $remaining = $this->records->countByStatus($batchId, 'pending');
                    return [
                        'status' => 'error',
                        'message' => $message,
                        'processed' => $processed,
                        'remaining' => $remaining,
                    ];
                }
                continue;
            }

            if (!$classification) {
                $this->records->markError($record['id'], 'Klasifikace nebyla určena.');
                continue;
            }

            $this->records->saveClassification($record['id'], $classification);
            $this->batches->incrementProcessed(
                $batchId,
                $classification['actionable_flag'] ? 1 : 0,
                $classification['latency_ms'],
                $classification['prompt_tokens'],
                $classification['completion_tokens']
            );
            $processed++;
            if ($classification['actionable_flag']) {
                $actionable++;
            }
            $this->batches->clearBackoff($batchId);
            $this->log->append("batch-{$batchId}.log", sprintf('Záznam #%d → %s (%s)', $record['id'], $classification['classification_source'], $classification['actionable_flag'] ? 'ANO' : 'NE'));
        }

        $remaining = $this->records->countByStatus($batchId, 'pending');
        if ($remaining === 0) {
            $this->finalizeBatch($batchId);
        }

        return [
            'status' => 'processing',
            'processed' => $processed,
            'actionable' => $actionable,
            'remaining' => $remaining,
        ];
    }

    private function classifyEmpty(array $record, float $start): array
    {
        $latency = round((microtime(true) - $start) * 1000, 2);
        return [
            'status' => 'completed',
            'actionable_flag' => false,
            'team_id' => null,
            'category_id' => null,
            'root_cause_id' => null,
            'recommended_action' => null,
            'classification_source' => 'empty',
            'raw' => ['reason' => 'empty_text'],
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'latency_ms' => $latency,
            'needs_review' => true,
        ];
    }

    private function classifyRecord(int $batchId, array $record, string $issueText, float $start): array
    {
        $language = $record['language'] ?? 'unknown';
        $glossaryMatch = $this->glossary->match($issueText, $language);
        if ($glossaryMatch) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'status' => 'completed',
                'actionable_flag' => $glossaryMatch['actionable_flag'],
                'team_id' => $glossaryMatch['team_id'],
                'category_id' => $glossaryMatch['category_id'],
                'root_cause_id' => $glossaryMatch['root_cause_id'],
                'recommended_action' => null,
                'classification_source' => 'glossary',
                'raw' => ['match' => $glossaryMatch['phrase']],
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'latency_ms' => $latency,
                'needs_review' => false,
            ];
        }

        $duplicate = $this->records->duplicateCompleted($batchId, $record['hash_key'], $record['product_code']);
        if ($duplicate) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            return [
                'status' => 'completed',
                'actionable_flag' => (bool) $duplicate['actionable_flag'],
                'team_id' => $duplicate['team_id'],
                'category_id' => $duplicate['category_id'],
                'root_cause_id' => $duplicate['root_cause_id'],
                'recommended_action' => $duplicate['recommended_action'],
                'classification_source' => 'duplicate',
                'raw' => ['source_record_id' => $duplicate['id']],
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'latency_ms' => $latency,
                'needs_review' => (bool) $duplicate['needs_review'],
            ];
        }

        $userPrompt = $this->promptBuilder->userPrompt($record);
        $response = $this->openai->classify($this->systemPrompt, $userPrompt);
        $latency = round((microtime(true) - $start) * 1000, 2);
        $data = json_decode($response['content'], true);
        if (!is_array($data)) {
            throw new \RuntimeException('GPT vrátil neplatnou odpověď.');
        }

        $teamKey = isset($data['team_key']) ? strtoupper(trim($data['team_key'])) : null;
        $categoryKey = isset($data['category_key']) ? strtoupper(trim($data['category_key'])) : null;
        $rootKeys = $data['root_cause_keys'] ?? [];
        if (!is_array($rootKeys)) {
            $rootKeys = $rootKeys !== null && $rootKeys !== '' ? [$rootKeys] : [];
        }
        $rootKeys = array_values(array_filter(array_map(function ($key) {
            if ($key === null) {
                return null;
            }
            return strtoupper(trim((string) $key));
        }, $rootKeys)));
        $actionable = strtoupper($data['actionable_flag'] ?? 'NE') === 'ANO';
        $recommended = trim($data['recommended_action'] ?? '');
        $needsReview = (bool) ($data['needs_review'] ?? false);
        if (!$actionable) {
            $recommended = '';
        }

        $teamId = $teamKey && isset($this->taxonomyIndex['teams'][$teamKey]) ? (int) $this->taxonomyIndex['teams'][$teamKey]['id'] : null;
        if (!$teamId) {
            $needsReview = true;
        }

        $categoryId = null;
        if ($categoryKey) {
            $categoryId = $this->taxonomyIndex['categories'][$categoryKey]['id'] ?? null;
            if (!$categoryId) {
                $needsReview = true;
            }
        }

        $rootCauseId = null;
        if (!empty($rootKeys)) {
            $foundRoot = false;
            foreach ($rootKeys as $rootKey) {
                if (isset($this->taxonomyIndex['root_causes'][$rootKey])) {
                    $rootCauseId = $this->taxonomyIndex['root_causes'][$rootKey]['id'];
                    $foundRoot = true;
                    break;
                }
            }
            if (!$foundRoot) {
                $needsReview = true;
            }
        }

        $this->storeProposals($record['id'], $data['proposals'] ?? []);

        return [
            'status' => 'completed',
            'actionable_flag' => $actionable,
            'team_id' => $teamId,
            'category_id' => $categoryId,
            'root_cause_id' => $rootCauseId,
            'recommended_action' => $recommended !== '' ? $recommended : null,
            'classification_source' => 'gpt',
            'raw' => $data,
            'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
            'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
            'latency_ms' => $latency,
            'needs_review' => $needsReview,
        ];
    }

    private function storeProposals(int $recordId, array $proposals): void
    {
        foreach ($proposals as $proposal) {
            if (empty($proposal['type']) || !in_array($proposal['type'], ['category', 'root_cause'], true)) {
                continue;
            }
            $this->proposals->create([
                'batch_record_id' => $recordId,
                'type' => $proposal['type'],
                'suggestion_key' => $proposal['suggested_key'] ?? null,
                'suggestion_name' => $proposal['name'] ?? null,
                'suggestion_description' => $proposal['description'] ?? null,
                'suggestion_prompt_definition' => $proposal['prompt_definition'] ?? null,
                'confidence' => isset($proposal['confidence']) ? (float) $proposal['confidence'] : 0.5,
                'status' => 'pending',
            ]);
        }
    }

    private function applyBackoff(int $batchId, array $batch): array
    {
        $step = (int) ($batch['backoff_step'] ?? 0);
        if ($step >= count($this->backoffIntervals)) {
            $this->batches->updateStatus($batchId, 'halted', 'Zpracování zastaveno kvůli opakovanému rate limitu.');
            return [
                'status' => 'halted',
                'message' => 'Zpracování zastaveno kvůli opakovanému rate limitu.',
                'wait_seconds' => null,
            ];
        }
        $wait = $this->backoffIntervals[$step];
        $this->batches->setBackoff($batchId, $step + 1, $wait, 'OpenAI rate limit, čekám '.$wait.' s');
        return [
            'status' => 'backoff',
            'message' => 'OpenAI rate limit, další pokus za '.$wait.' s',
            'wait_seconds' => $wait,
        ];
    }

    private function finalizeBatch(int $batchId): void
    {
        $this->issues->createAggregatedIssues($batchId);
        $this->batches->updateStatus($batchId, 'completed', 'Batch dokončen.');
        $this->batches->clearBackoff($batchId);
        $this->log->append("batch-{$batchId}.log", 'Batch dokončen.');
    }
}
