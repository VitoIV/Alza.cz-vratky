<?php

namespace App\Repositories;

use App\Support\Database;

class RecordRepository
{
    public function createMany(int $batchId, array $records): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO batch_records (batch_id, rma, product_code, seo_prefix, requested_at, product_name, issue_text, language, hash_key, status, created_at, updated_at) VALUES (:batch_id, :rma, :product_code, :seo_prefix, :requested_at, :product_name, :issue_text, :language, :hash_key, :status, NOW(), NOW())');
        foreach ($records as $record) {
            $stmt->execute([
                'batch_id' => $batchId,
                'rma' => $record['RMA (New)'] ?? null,
                'product_code' => $record['Produkt'] ?? null,
                'seo_prefix' => $record['SEO Prefix'] ?? null,
                'requested_at' => $record['Datum vytvoření'] ?? null,
                'product_name' => $record['Název produktu'] ?? null,
                'issue_text' => $record['Popis závady'] ?? '',
                'language' => $record['language'] ?? null,
                'hash_key' => $record['hash_key'] ?? null,
                'status' => 'pending',
            ]);
        }
        $pdo->commit();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM batch_records WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();
        return $record ?: null;
    }

    public function nextPendingRecords(int $batchId, int $limit): array
    {
        $sql = "SELECT * FROM batch_records WHERE batch_id = :batch AND status = 'pending' ORDER BY id ASC LIMIT :limit";
        $stmt = Database::connection()->prepare($sql);
        $stmt->bindValue('batch', $batchId, \PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function markProcessing(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE batch_records SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => 'processing']);
    }

    public function markPending(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE batch_records SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => 'pending']);
    }

    public function markError(int $id, string $message): void
    {
        $stmt = Database::connection()->prepare('UPDATE batch_records SET status = :status, error_message = :message, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id, 'status' => 'error', 'message' => $message]);
    }

    public function saveClassification(int $id, array $classification): void
    {
        $stmt = Database::connection()->prepare('UPDATE batch_records SET status = :status, actionable_flag = :actionable_flag, team_id = :team_id, category_id = :category_id, root_cause_id = :root_cause_id, recommended_action = :recommended_action, classification_source = :classification_source, classification_json = :classification_json, prompt_tokens = :prompt_tokens, completion_tokens = :completion_tokens, latency_ms = :latency_ms, needs_review = :needs_review, error_message = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'status' => $classification['status'],
            'actionable_flag' => $classification['actionable_flag'],
            'team_id' => $classification['team_id'],
            'category_id' => $classification['category_id'],
            'root_cause_id' => $classification['root_cause_id'],
            'recommended_action' => $classification['recommended_action'],
            'classification_source' => $classification['classification_source'],
            'classification_json' => json_encode($classification['raw'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'prompt_tokens' => $classification['prompt_tokens'],
            'completion_tokens' => $classification['completion_tokens'],
            'latency_ms' => $classification['latency_ms'],
            'needs_review' => $classification['needs_review'] ?? false,
        ]);
    }

    public function duplicateCompleted(int $batchId, string $hashKey, ?string $productCode): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM batch_records WHERE batch_id = :batch AND hash_key = :hash AND status = \'completed\' ORDER BY id ASC LIMIT 1');
        $stmt->execute(['batch' => $batchId, 'hash' => $hashKey]);
        $record = $stmt->fetch();
        if (!$record) {
            return null;
        }
        if ($productCode && $record['product_code'] && $record['product_code'] !== $productCode) {
            return null;
        }
        return $record;
    }

    public function recordsForBatch(int $batchId, array $filters = []): array
    {
        $sql = 'SELECT br.*, t.name AS team_name, c.name AS category_name, rc.name AS root_cause_name FROM batch_records br LEFT JOIN teams t ON br.team_id = t.id LEFT JOIN categories c ON br.category_id = c.id LEFT JOIN root_causes rc ON br.root_cause_id = rc.id WHERE br.batch_id = :batch';
        $params = ['batch' => $batchId];
        if (!empty($filters['team_id'])) {
            $sql .= ' AND br.team_id = :team_id';
            $params['team_id'] = $filters['team_id'];
        }
        if (isset($filters['actionable_flag'])) {
            $sql .= ' AND br.actionable_flag = :actionable';
            $params['actionable'] = $filters['actionable_flag'];
        }
        if (!empty($filters['status'])) {
            $sql .= ' AND br.status = :status_filter';
            $params['status_filter'] = $filters['status'];
        }
        $sql .= ' ORDER BY br.id DESC LIMIT 500';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countByStatus(int $batchId, string $status): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM batch_records WHERE batch_id = :batch AND status = :status');
        $stmt->execute(['batch' => $batchId, 'status' => $status]);
        return (int) $stmt->fetchColumn();
    }
}
