<?php

namespace App\Repositories;

use App\Support\Config;
use App\Support\Database;

class BatchRepository
{
    public function all(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM batches ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM batches WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $batch = $stmt->fetch();
        return $batch ?: null;
    }

    public function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO batches (name, filename, status, total_records, processed_records, actionable_records, backoff_step, created_at, updated_at) VALUES (:name, :filename, :status, :total_records, 0, 0, 0, NOW(), NOW())');
        $stmt->execute([
            'name' => $data['name'],
            'filename' => $data['filename'],
            'status' => $data['status'],
            'total_records' => $data['total_records'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $message = null): void
    {
        $stmt = Database::connection()->prepare('UPDATE batches SET status = :status, status_message = :message, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'message' => $message,
            'id' => $id,
        ]);
    }

    public function incrementProcessed(int $id, int $actionableDelta, float $latencyMs, int $promptTokens, int $completionTokens): void
    {
        $stmt = Database::connection()->prepare('UPDATE batches SET processed_records = processed_records + 1, actionable_records = actionable_records + :actionableDelta, total_prompt_tokens = total_prompt_tokens + :promptTokens, total_completion_tokens = total_completion_tokens + :completionTokens, total_latency_ms = total_latency_ms + :latencyMs, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'actionableDelta' => $actionableDelta,
            'latencyMs' => $latencyMs,
            'promptTokens' => $promptTokens,
            'completionTokens' => $completionTokens,
        ]);
    }

    public function setBackoff(int $id, int $step, int $waitSeconds, string $message): void
    {
        $timezone = Config::get('app.timezone');
        $now = $timezone ? new \DateTimeImmutable('now', new \DateTimeZone($timezone)) : new \DateTimeImmutable();
        $until = $now->modify('+' . $waitSeconds . ' seconds')->format('Y-m-d H:i:s');

        $stmt = Database::connection()->prepare('UPDATE batches SET backoff_step = :step, backoff_until = :until, status_message = :message, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'step' => $step,
            'until' => $until,
            'message' => $message,
        ]);
    }

    public function clearBackoff(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE batches SET backoff_step = 0, backoff_until = NULL, status_message = NULL, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
