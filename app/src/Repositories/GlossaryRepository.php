<?php

namespace App\Repositories;

use App\Support\Database;

class GlossaryRepository
{
    public function all(): array
    {
        $stmt = Database::connection()->query('SELECT g.*, t.name AS team_name, c.name AS category_name, rc.name AS root_cause_name FROM glossary_entries g LEFT JOIN teams t ON g.team_id = t.id LEFT JOIN categories c ON g.category_id = c.id LEFT JOIN root_causes rc ON g.root_cause_id = rc.id ORDER BY language, phrase');
        return $stmt->fetchAll();
    }

    public function activeEntries(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM glossary_entries WHERE active = 1 ORDER BY language, phrase');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO glossary_entries (phrase, normalized_phrase, language, threshold, team_id, category_id, root_cause_id, actionable_flag, notes, active, created_at, updated_at) VALUES (:phrase, :normalized_phrase, :language, :threshold, :team_id, :category_id, :root_cause_id, :actionable_flag, :notes, :active, NOW(), NOW())');
        $stmt->execute([
            'phrase' => $data['phrase'],
            'normalized_phrase' => $data['normalized_phrase'],
            'language' => $data['language'],
            'threshold' => $data['threshold'],
            'team_id' => $data['team_id'],
            'category_id' => $data['category_id'],
            'root_cause_id' => $data['root_cause_id'],
            'actionable_flag' => $data['actionable_flag'] ? 1 : 0,
            'notes' => $data['notes'],
            'active' => isset($data['active']) ? (int) $data['active'] : 1,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE glossary_entries SET phrase=:phrase, normalized_phrase=:normalized_phrase, language=:language, threshold=:threshold, team_id=:team_id, category_id=:category_id, root_cause_id=:root_cause_id, actionable_flag=:actionable_flag, notes=:notes, active=:active, updated_at = NOW() WHERE id=:id');
        $stmt->execute([
            'phrase' => $data['phrase'],
            'normalized_phrase' => $data['normalized_phrase'],
            'language' => $data['language'],
            'threshold' => $data['threshold'],
            'team_id' => $data['team_id'],
            'category_id' => $data['category_id'],
            'root_cause_id' => $data['root_cause_id'],
            'actionable_flag' => $data['actionable_flag'] ? 1 : 0,
            'notes' => $data['notes'],
            'active' => isset($data['active']) ? (int) $data['active'] : 1,
            'id' => $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM glossary_entries WHERE id=:id');
        $stmt->execute(['id' => $id]);
    }
}
