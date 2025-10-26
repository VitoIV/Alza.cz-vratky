<?php

namespace App\Controllers;

use App\Repositories\BatchRepository;
use App\Repositories\RecordRepository;
use App\Repositories\IssueRepository;
use App\Support\View;
use App\Services\UploadBatchService;
use App\Services\BatchProcessingService;
use App\Services\LogService;

class BatchController
{
    public function index(): void
    {
        $batches = (new BatchRepository())->all();
        View::render('batches/index', ['batches' => $batches]);
    }

    public function show(int $id): void
    {
        $batchRepo = new BatchRepository();
        $recordRepo = new RecordRepository();
        $issueRepo = new IssueRepository();

        $batch = $batchRepo->find($id);
        if (!$batch) {
            http_response_code(404);
            echo 'Batch not found';
            return;
        }

        $issues = $issueRepo->forBatch($id);
        $records = $recordRepo->recordsForBatch($id, []);
        $remaining = $recordRepo->countByStatus($id, 'pending');

        View::render('batches/show', [
            'batch' => $batch,
            'issues' => $issues,
            'records' => $records,
            'remaining' => $remaining,
        ]);
    }

    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $service = new UploadBatchService(new \App\Services\UploadedBatchFile($_FILES['file'] ?? null));
            $result = $service->handle($_POST['name'] ?? null);
            if ($result['success']) {
                header('Location: /batches/'.$result['batch_id']);
                exit;
            }
            View::render('batches/upload', ['error' => $result['message']]);
            return;
        }
        View::render('batches/upload');
    }

    public function process(int $id): void
    {
        header('Content-Type: application/json');
        $service = new BatchProcessingService();
        $result = $service->process($id);
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function pause(int $id): void
    {
        header('Content-Type: application/json');
        $repo = new BatchRepository();
        $repo->updateStatus($id, 'halted', 'Pozastaveno uživatelem.');
        echo json_encode(['status' => 'halted']);
    }

    public function resume(int $id): void
    {
        header('Content-Type: application/json');
        $repo = new BatchRepository();
        $repo->updateStatus($id, 'queued', null);
        $repo->clearBackoff($id);
        echo json_encode(['status' => 'queued']);
    }

    public function logs(int $id): void
    {
        $logService = new LogService();
        header('Content-Type: text/plain; charset=utf-8');
        echo $logService->tail("batch-{$id}.log", 5000);
    }
}
