<?php

namespace App\Repositories;

use App\Support\Database;

class IssueRepository
{
    public function forBatch(int $batchId): array
    {
        $sql = 'SELECT i.*, t.name AS team_name, c.name AS category_name, rc.name AS root_cause_name
                FROM issues i
                LEFT JOIN teams t ON i.team_id = t.id
                LEFT JOIN categories c ON i.category_id = c.id
                LEFT JOIN root_causes rc ON i.root_cause_id = rc.id
                WHERE i.batch_id = :batch
                ORDER BY i.total_records DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['batch' => $batchId]);

        return $stmt->fetchAll();
    }

    public function detail(int $issueId): ?array
    {
        $detailSql = 'SELECT i.*, t.name AS team_name, c.name AS category_name, rc.name AS root_cause_name
                      FROM issues i
                      LEFT JOIN teams t ON i.team_id = t.id
                      LEFT JOIN categories c ON i.category_id = c.id
                      LEFT JOIN root_causes rc ON i.root_cause_id = rc.id
                      WHERE i.id = :id';

        $stmt = Database::connection()->prepare($detailSql);
        $stmt->execute(['id' => $issueId]);
        $issue = $stmt->fetch();

        if (!$issue) {
            return null;
        }

        $recordSql = 'SELECT br.*
                      FROM issue_records ir
                      JOIN batch_records br ON ir.batch_record_id = br.id
                      WHERE ir.issue_id = :issue
                      ORDER BY br.id';

        $recordStmt = Database::connection()->prepare($recordSql);
        $recordStmt->execute(['issue' => $issueId]);
        $issue['records'] = $recordStmt->fetchAll();

        return $issue;
    }

    public function createAggregatedIssues(int $batchId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM issues WHERE batch_id = :batch')->execute(['batch' => $batchId]);
        $pdo->prepare('DELETE ir FROM issue_records ir JOIN batch_records br ON ir.batch_record_id = br.id WHERE br.batch_id = :batch')->execute(['batch' => $batchId]);

        $groupSql = "SELECT id, actionable_flag, team_id, category_id, root_cause_id, recommended_action
                     FROM batch_records
                     WHERE batch_id = :batch
                       AND status = 'completed'
                       AND (needs_review IS NULL OR needs_review = 0)";

        $groupStmt = $pdo->prepare($groupSql);
        $groupStmt->execute(['batch' => $batchId]);

        $grouped = [];

        while ($row = $groupStmt->fetch()) {
            if ($row['team_id'] && $this->isContentTeam((int) $row['team_id'])) {
                $key = 'record-' . $row['id'];
            } else {
                $key = implode('|', [
                    $row['team_id'] ?? 'null',
                    $row['category_id'] ?? 'null',
                    $row['root_cause_id'] ?? 'null',
                    $row['recommended_action'] ?? '',
                    (int) $row['actionable_flag'],
                ]);
            }

            $grouped[$key]['records'][] = $row['id'];
            $grouped[$key]['template'] = $row;
        }

        $issueInsert = $pdo->prepare('INSERT INTO issues (batch_id, actionable_flag, team_id, category_id, root_cause_id, recommended_action, total_records, created_at, updated_at) VALUES (:batch_id, :actionable_flag, :team_id, :category_id, :root_cause_id, :recommended_action, :total_records, NOW(), NOW())');
        $linkInsert = $pdo->prepare('INSERT INTO issue_records (issue_id, batch_record_id) VALUES (:issue_id, :record_id)');

        foreach ($grouped as $payload) {
            $template = $payload['template'];

            $issueInsert->execute([
                'batch_id' => $batchId,
                'actionable_flag' => $template['actionable_flag'],
                'team_id' => $template['team_id'],
                'category_id' => $template['category_id'],
                'root_cause_id' => $template['root_cause_id'],
                'recommended_action' => $template['recommended_action'],
                'total_records' => count($payload['records']),
            ]);

            $issueId = (int) $pdo->lastInsertId();

            foreach ($payload['records'] as $recordId) {
                $linkInsert->execute(['issue_id' => $issueId, 'record_id' => $recordId]);
            }
        }

        $pdo->commit();
    }

    private function isContentTeam(int $teamId): bool
    {
        $stmt = Database::connection()->prepare('SELECT `key` FROM teams WHERE id = :id');
        $stmt->execute(['id' => $teamId]);
        $key = $stmt->fetchColumn();

        if (!$key) {
            return false;
        }

        return $key === 'CONTENT';
    }
}
