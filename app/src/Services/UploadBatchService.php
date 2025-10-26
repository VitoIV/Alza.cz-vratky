<?php

namespace App\Services;

use App\Repositories\BatchRepository;
use App\Repositories\RecordRepository;
use App\Support\LanguageGuesser;
use App\Support\TextNormalizer;
use RuntimeException;

class UploadedBatchFile
{
    private ?array $file;

    public function __construct(?array $file)
    {
        $this->file = $file;
    }

    public function isValid(): bool
    {
        return $this->file && ($this->file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    public function moveTo(string $destination): void
    {
        if (!move_uploaded_file($this->file['tmp_name'], $destination)) {
            throw new RuntimeException('Nelze uložit nahraný soubor.');
        }
    }
}

class UploadBatchService
{
    private UploadedBatchFile $file;

    public function __construct(UploadedBatchFile $file)
    {
        $this->file = $file;
    }

    public function handle(?string $name = null): array
    {
        if (!$this->file->isValid()) {
            return ['success' => false, 'message' => 'Soubor nebyl nahrán správně.'];
        }

        $name = $name ?: 'Batch '.date('Y-m-d H:i');
        $storagePath = __DIR__.'/../../storage/uploads';
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0777, true);
        }
        $filename = uniqid('batch_', true).'.xlsx';
        $fullPath = $storagePath.'/'.$filename;

        try {
            $this->file->moveTo($fullPath);
            $rows = XlsxReader::load($fullPath);
        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        if (empty($rows)) {
            return ['success' => false, 'message' => 'Soubor neobsahuje žádná data.'];
        }

        $batchRepo = new BatchRepository();
        $recordRepo = new RecordRepository();

        $batchId = $batchRepo->create([
            'name' => $name,
            'filename' => $filename,
            'status' => 'queued',
            'total_records' => count($rows),
        ]);

        $prepared = [];
        foreach ($rows as $row) {
            $issueText = trim($row['Popis závady'] ?? '');
            $language = LanguageGuesser::guess($issueText) ?? 'unknown';
            $row['language'] = $language;
            $row['hash_key'] = TextNormalizer::hash($issueText);
            $prepared[] = $row;
        }

        $recordRepo->createMany($batchId, $prepared);
        $batchRepo->updateStatus($batchId, 'queued');

        return ['success' => true, 'batch_id' => $batchId];
    }
}
