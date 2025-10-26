<?php

namespace App\Controllers;

use App\Repositories\BatchRepository;
use App\Support\View;
use App\Support\Database;

class DashboardController
{
    public function index(): void
    {
        $batchRepo = new BatchRepository();
        $batches = $batchRepo->all();

        $pdo = Database::connection();
        $stats = $pdo->query('SELECT
            COALESCE(SUM(CASE WHEN actionable_flag THEN 1 ELSE 0 END),0) AS actionable_total,
            COALESCE(SUM(CASE WHEN actionable_flag THEN 0 ELSE 1 END),0) AS non_actionable_total,
            COALESCE(SUM(prompt_tokens),0) AS prompt_tokens,
            COALESCE(SUM(completion_tokens),0) AS completion_tokens
        FROM batch_records')->fetch();

        $topRootCauses = $pdo->query("SELECT rc.name AS root_cause_name, COUNT(*) AS total FROM batch_records br LEFT JOIN root_causes rc ON rc.id = br.root_cause_id WHERE br.actionable_flag = true AND br.status = 'completed' GROUP BY rc.name ORDER BY total DESC LIMIT 10")->fetchAll();

        View::render('dashboard/index', [
            'batches' => $batches,
            'stats' => $stats,
            'topRootCauses' => $topRootCauses,
        ]);
    }
}
