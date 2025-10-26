<?php

namespace App\Repositories;

use App\Support\Database;
use PDO;

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
        $stmt = Database::connection()->prepare('INSERT INTO teams (key, name, description, prompt_definition, position) VALUES (:key, :name, :description, :prompt_definition, :position) RETURNING id');
        $stmt->execute([
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function updateTeam(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE teams SET name=:name, description=:description, prompt_definition=:prompt_definition, position=:position WHERE id=:id');
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
        $stmt = Database::connection()->prepare('INSERT INTO categories (team_id, key, name, description, prompt_definition, position, active) VALUES (:team_id, :key, :name, :description, :prompt_definition, :position, :active) RETURNING id');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => $data['active'] ?? true,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function updateCategory(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE categories SET team_id=:team_id, name=:name, description=:description, prompt_definition=:prompt_definition, position=:position, active=:active WHERE id=:id');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => $data['active'] ?? true,
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
        $stmt = Database::connection()->prepare('INSERT INTO root_causes (team_id, category_id, key, name, description, prompt_definition, position, active) VALUES (:team_id, :category_id, :key, :name, :description, :prompt_definition, :position, :active) RETURNING id');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'category_id' => $data['category_id'],
            'key' => $data['key'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => $data['active'] ?? true,
        ]);
        return (int) $stmt->fetchColumn();
    }

    public function updateRootCause(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE root_causes SET team_id=:team_id, category_id=:category_id, name=:name, description=:description, prompt_definition=:prompt_definition, position=:position, active=:active WHERE id=:id');
        $stmt->execute([
            'team_id' => $data['team_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'prompt_definition' => $data['prompt_definition'],
            'position' => $data['position'] ?? 0,
            'active' => $data['active'] ?? true,
            'id' => $id,
        ]);
    }

    public function deleteRootCause(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM root_causes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
