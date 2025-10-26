<section class="card">
    <div class="flex-between">
        <h1>Batch fronta</h1>
        <a class="button" href="/batches/upload">Nový batch</a>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Název</th>
                <th>Soubor</th>
                <th>Stav</th>
                <th>Progress</th>
                <th>Vytvořeno</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
                <?php $progress = $batch['total_records'] > 0 ? round(($batch['processed_records'] / $batch['total_records']) * 100, 1) : 0; ?>
                <tr>
                    <td><?= htmlspecialchars($batch['name']) ?></td>
                    <td><?= htmlspecialchars($batch['filename']) ?></td>
                    <td>
                        <span class="badge"><?= htmlspecialchars($batch['status']) ?></span>
                        <?php if (!empty($batch['status_message'])): ?>
                            <div class="stat-label"><?= htmlspecialchars($batch['status_message']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="progress-bar"><span style="width: <?= $progress ?>%"></span></div>
                        <small><?= $batch['processed_records'] ?>/<?= $batch['total_records'] ?> (<?= $progress ?>%)</small>
                    </td>
                    <td><?= htmlspecialchars($batch['created_at'] ?? '') ?></td>
                    <td>
                        <a class="button-secondary" href="/batches/<?= $batch['id'] ?>">Detail</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
