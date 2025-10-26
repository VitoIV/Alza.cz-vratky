<?php

namespace App\Controllers;

use App\Repositories\TaxonomyRepository;
use App\Support\View;

class TaxonomyController
{
    public function index(): void
    {
        $repo = new TaxonomyRepository();
        $teams = $repo->allTeams();
        $categories = $repo->categoriesByTeam();
        $causes = $repo->rootCausesByCategory();
        View::render('taxonomy/index', [
            'teams' => $teams,
            'categories' => $categories,
            'causes' => $causes,
        ]);
    }

    public function store(): void
    {
        $repo = new TaxonomyRepository();
        $type = $_POST['entity_type'] ?? null;
        if ($type === 'team') {
            $repo->createTeam([
                'key' => $_POST['key'],
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'prompt_definition' => $_POST['prompt_definition'],
                'position' => (int) ($_POST['position'] ?? 0),
            ]);
        } elseif ($type === 'category') {
            $repo->createCategory([
                'team_id' => (int) $_POST['team_id'],
                'key' => $_POST['key'],
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'prompt_definition' => $_POST['prompt_definition'],
                'position' => (int) ($_POST['position'] ?? 0),
                'active' => isset($_POST['active']),
            ]);
        } elseif ($type === 'root_cause') {
            $repo->createRootCause([
                'team_id' => (int) $_POST['team_id'],
                'category_id' => (int) $_POST['category_id'],
                'key' => $_POST['key'],
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'prompt_definition' => $_POST['prompt_definition'],
                'position' => (int) ($_POST['position'] ?? 0),
                'active' => isset($_POST['active']),
            ]);
        }
        header('Location: /taxonomy');
    }

    public function update(string $type, int $id): void
    {
        $repo = new TaxonomyRepository();
        if ($type === 'team') {
            $repo->updateTeam($id, [
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'prompt_definition' => $_POST['prompt_definition'],
                'position' => (int) ($_POST['position'] ?? 0),
            ]);
        } elseif ($type === 'category') {
            $repo->updateCategory($id, [
                'team_id' => (int) $_POST['team_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'prompt_definition' => $_POST['prompt_definition'],
                'position' => (int) ($_POST['position'] ?? 0),
                'active' => isset($_POST['active']),
            ]);
        } elseif ($type === 'root_cause') {
            $repo->updateRootCause($id, [
                'team_id' => (int) $_POST['team_id'],
                'category_id' => (int) $_POST['category_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'prompt_definition' => $_POST['prompt_definition'],
                'position' => (int) ($_POST['position'] ?? 0),
                'active' => isset($_POST['active']),
            ]);
        }
        header('Location: /taxonomy');
    }

    public function delete(string $type, int $id): void
    {
        $repo = new TaxonomyRepository();
        if ($type === 'team') {
            $repo->deleteTeam($id);
        } elseif ($type === 'category') {
            $repo->deleteCategory($id);
        } elseif ($type === 'root_cause') {
            $repo->deleteRootCause($id);
        }
        header('Location: /taxonomy');
    }
}
