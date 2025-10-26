<?php

namespace App\Repositories;

use App\Support\Database;

class TaxonomyRepository
{
    public function allTeams(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM teams ORDER BY position, name');
        return $stmt->fetchAll();
    }

    public function team(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM teams WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $team = $stmt->fetch();
        return $team ?: null;
    }

    public function createTeam(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO teams (`key`, name, description, prompt_definition, position, created_at, updated_at) VALUES (:key, :name, :description, :prompt_definition, :position, NOW(), NOW())');
        $stmt->execute([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function updateTeam(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE teams SET name=:name, description=:description, prompt_definition=:prompt_definition, position=:position, updated_at = NOW() WHERE id=:id');
        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'id' => $id,
        ]);
    }

    public function deleteTeam(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM teams WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function categoriesByTeam(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM categories ORDER BY position, name');
        $categories = $stmt->fetchAll();
        $grouped = [];
        foreach ($categories as $category) {
            $grouped[$category['team_id']][] = $category;
        }
        return $grouped;
    }

    public function createCategory(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO categories (team_id, `key`, name, description, prompt_definition, position, active, created_at, updated_at) VALUES (:team_id, :key, :name, :description, :prompt_definition, :position, :active, NOW(), NOW())');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => isset($data['active']) ? (int) $data['active'] : 1,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function updateCategory(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE categories SET team_id=:team_id, name=:name, description=:description, prompt_definition=:prompt_definition, position=:position, active=:active, updated_at = NOW() WHERE id=:id');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => isset($data['active']) ? (int) $data['active'] : 1,
            'id' => $id,
        ]);
    }

    public function deleteCategory(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function rootCausesByCategory(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM root_causes ORDER BY position, name');
        $causes = $stmt->fetchAll();
        $grouped = [];
        foreach ($causes as $cause) {
            $grouped[$cause['category_id']][] = $cause;
        }
        return $grouped;
    }

    public function createRootCause(array $data): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('INSERT INTO root_causes (team_id, category_id, `key`, name, description, prompt_definition, position, active, created_at, updated_at) VALUES (:team_id, :category_id, :key, :name, :description, :prompt_definition, :position, :active, NOW(), NOW())');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'category_id' => $data['category_id'],
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => isset($data['active']) ? (int) $data['active'] : 1,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public function updateRootCause(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE root_causes SET team_id=:team_id, category_id=:category_id, name=:name, description=:description, prompt_definition=:prompt_definition, position=:position, active=:active, updated_at = NOW() WHERE id=:id');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => isset($data['active']) ? (int) $data['active'] : 1,
            'id' => $id,
        ]);
    }

    public function deleteRootCause(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM root_causes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
