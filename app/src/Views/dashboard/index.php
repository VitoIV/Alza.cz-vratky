<section class="card">
    <div class="flex-between">
        <h1>Řídicí panel</h1>
        <a class="button" href="/batches/upload">Nahrát nový batch</a>
    </div>
    <div class="grid">
        <div class="card">
            <div class="stat-value"><?= number_format((int)($stats['actionable_total'] ?? 0)) ?></div>
            <div class="stat-label">Akčních záznamů</div>
        </div>
        <div class="card">
            <div class="stat-value"><?= number_format((int)($stats['non_actionable_total'] ?? 0)) ?></div>
            <div class="stat-label">Neakčních záznamů</div>
        </div>
        <div class="card">
            <div class="stat-value"><?= number_format((int)($stats['prompt_tokens'] ?? 0)) ?></div>
            <div class="stat-label">Prompt tokenů</div>
        </div>
        <div class="card">
            <div class="stat-value"><?= number_format((int)($stats['completion_tokens'] ?? 0)) ?></div>
            <div class="stat-label">Completion tokenů</div>
        </div>
    </div>
</section>
<section class="card">
    <h2>Batche</h2>
    <p class="stat-label">Spusťte zpracování z detailu batche tlačítkem "Start". Pauza je dostupná kdykoliv.</p>
    <table class="table">
        <thead>
            <tr>
                <th>Název</th>
                <th>Stav</th>
                <th>Progres</th>
                <th>Záznamů</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($batches as $batch): ?>
                <tr>
                    <td><?= htmlspecialchars($batch['name']) ?></td>
                    <td><?= htmlspecialchars($batch['status']) ?></td>
                    <td>
                        <?php $progress = $batch['total_records'] > 0 ? round(($batch['processed_records'] / $batch['total_records']) * 100, 1) : 0; ?>
                        <div class="progress-bar"><span style="width: <?= $progress ?>%"></span></div>
                        <small><?= $progress ?> %</small>
                    </td>
                    <td><?= $batch['processed_records'] ?>/<?= $batch['total_records'] ?></td>
                    <td><a class="button-secondary" href="/batches/<?= $batch['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="card">
    <h2>Top root causes</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Root cause</th>
                <th>Počet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topRootCauses as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['root_cause_name'] ?? 'Nedefinováno') ?></td>
                    <td><?= (int) $row['total'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
