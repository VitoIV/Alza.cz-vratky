<section class="card" data-batch-processing data-batch-id="<?= (int) $batch['id'] ?>" data-total="<?= (int) $batch['total_records'] ?>" data-remaining="<?= (int) $remaining ?>" data-status="<?= htmlspecialchars($batch['status']) ?>">
    <div class="flex-between">
        <div>
            <h1><?= htmlspecialchars($batch['name']) ?></h1>
            <div class="stat-label">Status: <span id="batch-status-text"><?= htmlspecialchars($batch['status']) ?></span></div>
            <div class="stat-label">Zbývá záznamů: <span id="batch-remaining-text"><?= (int) $remaining ?></span> / <?= (int) $batch['total_records'] ?></div>
            <?php if (!empty($batch['status_message'])): ?>
                <div class="alert" id="batch-status-message"><?= htmlspecialchars($batch['status_message']) ?></div>
            <?php else: ?>
                <div class="alert" id="batch-status-message" style="display:none;"></div>
            <?php endif; ?>
        </div>
        <div class="flex" style="gap:12px;">
            <button class="button" type="button" id="batch-start-btn">Start</button>
            <button class="button-secondary" type="button" id="batch-pause-btn">Pauza</button>
            <a class="button-secondary" href="/batches/<?= $batch['id'] ?>/logs" target="_blank">Log</a>
        </div>
    </div>
    <?php $progress = $batch['total_records'] > 0 ? round(($batch['processed_records'] / $batch['total_records']) * 100, 1) : 0; ?>
    <div class="progress-bar"><span id="batch-progress-bar" style="width: <?= $progress ?>%"></span></div>
    <div class="stat-label">Zpracováno <span id="batch-processed-text"><?= $batch['processed_records'] ?></span>/<?= $batch['total_records'] ?> (<?= $progress ?>%)</div>
</section>
<section class="card">
    <h2>Agregované problémy</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Tým</th>
                <th>Kategorie</th>
                <th>Root cause</th>
                <th>Akce</th>
                <th>Počet</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($issues as $issue): ?>
                <tr>
                    <td><?= htmlspecialchars($issue['team_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($issue['category_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($issue['root_cause_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($issue['recommended_action'] ?? '-') ?></td>
                    <td><?= (int) $issue['total_records'] ?></td>
                    <td><a class="button-secondary" href="/issues/<?= $issue['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="card">
    <h2>Poslední klasifikace</h2>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>RMA</th>
                <th>Produkt</th>
                <th>Text zákazníka</th>
                <th>Tým</th>
                <th>Kategorie</th>
                <th>Root cause</th>
                <th>Akční</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?= $record['id'] ?></td>
                    <td><?= htmlspecialchars($record['rma']) ?></td>
                    <td><?= htmlspecialchars($record['product_name']) ?></td>
                    <td><?= htmlspecialchars($record['issue_text']) ?></td>
                    <td><?= htmlspecialchars($record['team_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($record['category_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($record['root_cause_name'] ?? '-') ?></td>
                    <td><?= $record['actionable_flag'] ? '<span class="tag success">ANO</span>' : '<span class="tag">NE</span>' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
