<?php

namespace App\Repositories;

use App\Support\Database;

class ProposalRepository
{
    public function pending(): array
    {
        $sql = "SELECT p.*, br.issue_text, br.product_name, br.rma
                FROM proposals p
                JOIN batch_records br ON p.batch_record_id = br.id
                WHERE p.status = 'pending'
                ORDER BY p.created_at DESC";

        $stmt = Database::connection()->query($sql);

        return $stmt->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO proposals (batch_record_id, type, suggestion_key, suggestion_name, suggestion_description, suggestion_prompt_definition, confidence, status, created_at, updated_at) VALUES (:batch_record_id, :type, :suggestion_key, :suggestion_name, :suggestion_description, :suggestion_prompt_definition, :confidence, :status, NOW(), NOW())');
        $stmt->execute([
            'batch_record_id' => $data['batch_record_id'],
            'type' => $data['type'],
            'suggestion_key' => $data['suggestion_key'],
            'suggestion_name' => $data['suggestion_name'],
            'suggestion_description' => $data['suggestion_description'],
            'suggestion_prompt_definition' => $data['suggestion_prompt_definition'],
            'confidence' => $data['confidence'],
            'status' => $data['status'] ?? 'pending',
        ]);
    }

    public function updateStatus(int $id, string $status, ?int $entityId = null): void
    {
        $stmt = Database::connection()->prepare('UPDATE proposals SET status = :status, resolved_entity_id = :entity_id, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'entity_id' => $entityId,
            'id' => $id,
        ]);
    }
}
