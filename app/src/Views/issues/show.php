<section class="card">
    <h1>Detail problému</h1>
    <div class="grid">
        <div class="card">
            <div class="stat-label">Tým</div>
            <div class="stat-value"><?= htmlspecialchars($issue['team_name'] ?? '-') ?></div>
        </div>
        <div class="card">
            <div class="stat-label">Kategorie</div>
            <div class="stat-value"><?= htmlspecialchars($issue['category_name'] ?? '-') ?></div>
        </div>
        <div class="card">
            <div class="stat-label">Root cause</div>
            <div class="stat-value"><?= htmlspecialchars($issue['root_cause_name'] ?? '-') ?></div>
        </div>
        <div class="card">
            <div class="stat-label">Počet záznamů</div>
            <div class="stat-value"><?= (int) $issue['total_records'] ?></div>
        </div>
    </div>
    <h2>Doporučená akce</h2>
    <p><?= nl2br(htmlspecialchars($issue['recommended_action'] ?? '')) ?></p>
</section>
<section class="card">
    <h2>Konkrétní případy</h2>
    <table class="table">
        <thead>
            <tr>
                <th>RMA</th>
                <th>Produkt</th>
                <th>Text</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($issue['records'] as $record): ?>
                <tr>
                    <td><?= htmlspecialchars($record['rma']) ?></td>
                    <td><?= htmlspecialchars($record['product_name']) ?></td>
                    <td><?= htmlspecialchars($record['issue_text']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
