<section class="card">
    <h1>Přehled problémů</h1>
    <form method="GET" class="flex" style="gap:12px;">
        <div>
            <label>ID batche</label>
            <input type="number" name="batch" value="<?= htmlspecialchars($batchId ?? '') ?>" placeholder="např. 12" />
        </div>
        <div style="align-self:flex-end;">
            <button class="button" type="submit">Filtrovat</button>
        </div>
    </form>
    <?php if (!$batchId): ?>
        <p class="stat-label">Zadejte ID batche pro zobrazení agregací.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Batch</th>
                <th>Tým</th>
                <th>Kategorie</th>
                <th>Root cause</th>
                <th>Akční</th>
                <th>Počet</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($issues as $issue): ?>
                <tr>
                    <td><?= htmlspecialchars($issue['batch_id']) ?></td>
                    <td><?= htmlspecialchars($issue['team_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($issue['category_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($issue['root_cause_name'] ?? '-') ?></td>
                    <td><?= $issue['actionable_flag'] ? '<span class="tag success">ANO</span>' : '<span class="tag">NE</span>' ?></td>
                    <td><?= (int) $issue['total_records'] ?></td>
                    <td><a class="button-secondary" href="/issues/<?= $issue['id'] ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</section>
