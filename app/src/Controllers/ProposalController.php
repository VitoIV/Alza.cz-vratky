<?php

namespace App\Controllers;

use App\Repositories\ProposalRepository;
use App\Repositories\TaxonomyRepository;
use App\Repositories\RecordRepository;
use App\Support\View;

class ProposalController
{
    public function index(): void
    {
        $repo = new ProposalRepository();
        $taxonomy = new TaxonomyRepository();
        View::render('proposals/index', [
            'proposals' => $repo->pending(),
            'teams' => $taxonomy->allTeams(),
            'categories' => $taxonomy->categoriesByTeam(),
            'causes' => $taxonomy->rootCausesByCategory(),
        ]);
    }

    public function resolve(int $id): void
    {
        $repo = new ProposalRepository();
        $action = $_POST['action'] ?? 'reject';
        if ($action === 'approve') {
            $type = $_POST['type'];
            $taxonomy = new TaxonomyRepository();
            if ($type === 'category') {
                $entityId = $taxonomy->createCategory([
                    'team_id' => (int) $_POST['team_id'],
                    'key' => $_POST['key'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'prompt_definition' => $_POST['prompt_definition'],
                    'position' => 0,
                    'active' => true,
                ]);
            } else {
                $entityId = $taxonomy->createRootCause([
                    'team_id' => (int) $_POST['team_id'],
                    'category_id' => (int) $_POST['category_id'],
                    'key' => $_POST['key'],
                    'name' => $_POST['name'],
                    'description' => $_POST['description'],
                    'prompt_definition' => $_POST['prompt_definition'],
                    'position' => 0,
                    'active' => true,
                ]);
            }
            $repo->updateStatus($id, 'approved', $entityId);
        } else {
            $repo->updateStatus($id, 'rejected');
        }
        header('Location: /proposals');
    }
}
