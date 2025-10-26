<?php

namespace App\Controllers;

use App\Repositories\GlossaryRepository;
use App\Repositories\TaxonomyRepository;
use App\Support\View;
use App\Support\TextNormalizer;

class GlossaryController
{
    public function index(): void
    {
        $repo = new GlossaryRepository();
        $taxonomy = new TaxonomyRepository();
        View::render('glossary/index', [
            'entries' => $repo->all(),
            'teams' => $taxonomy->allTeams(),
            'categories' => $taxonomy->categoriesByTeam(),
            'causes' => $taxonomy->rootCausesByCategory(),
        ]);
    }

    public function store(): void
    {
        $repo = new GlossaryRepository();
        $repo->create([
            'phrase' => $_POST['phrase'],
            'normalized_phrase' => TextNormalizer::canonical($_POST['phrase']),
            'language' => $_POST['language'] ?? 'unknown',
            'threshold' => (float) ($_POST['threshold'] ?? 0.9),
            'team_id' => (int) $_POST['team_id'],
            'category_id' => (int) $_POST['category_id'],
            'root_cause_id' => (int) $_POST['root_cause_id'],
            'actionable_flag' => isset($_POST['actionable_flag']),
            'notes' => $_POST['notes'] ?? null,
            'active' => isset($_POST['active']),
        ]);
        header('Location: /glossary');
    }

    public function update(int $id): void
    {
        $repo = new GlossaryRepository();
        $repo->update($id, [
            'phrase' => $_POST['phrase'],
            'normalized_phrase' => TextNormalizer::canonical($_POST['phrase']),
            'language' => $_POST['language'] ?? 'unknown',
            'threshold' => (float) ($_POST['threshold'] ?? 0.9),
            'team_id' => (int) $_POST['team_id'],
            'category_id' => (int) $_POST['category_id'],
            'root_cause_id' => (int) $_POST['root_cause_id'],
            'actionable_flag' => isset($_POST['actionable_flag']),
            'notes' => $_POST['notes'] ?? null,
            'active' => isset($_POST['active']),
        ]);
        header('Location: /glossary');
    }

    public function delete(int $id): void
    {
        (new GlossaryRepository())->delete($id);
        header('Location: /glossary');
    }
}
