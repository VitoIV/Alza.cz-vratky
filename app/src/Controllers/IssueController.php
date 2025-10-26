<?php

namespace App\Controllers;

use App\Repositories\IssueRepository;
use App\Support\View;

class IssueController
{
    public function index(): void
    {
        $repo = new IssueRepository();
        $batchId = $_GET['batch'] ?? null;
        if (!$batchId) {
            $issues = [];
        } else {
            $issues = $repo->forBatch((int) $batchId);
        }
        View::render('issues/index', ['issues' => $issues, 'batchId' => $batchId]);
    }

    public function show(int $id): void
    {
        $repo = new IssueRepository();
        $issue = $repo->detail($id);
        if (!$issue) {
            http_response_code(404);
            echo 'Issue not found';
            return;
        }
        View::render('issues/show', ['issue' => $issue]);
    }
}
