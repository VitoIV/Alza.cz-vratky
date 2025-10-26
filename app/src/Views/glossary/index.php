<section class="card">
    <h1>Slovníček frází</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Fráze</th>
                <th>Jazyk</th>
                <th>Tým</th>
                <th>Kategorie</th>
                <th>Root cause</th>
                <th>Akční</th>
                <th>Poznámka</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= htmlspecialchars($entry['phrase']) ?></td>
                    <td><?= htmlspecialchars($entry['language']) ?></td>
                    <td><?= htmlspecialchars($entry['team_name']) ?></td>
                    <td><?= htmlspecialchars($entry['category_name']) ?></td>
                    <td><?= htmlspecialchars($entry['root_cause_name']) ?></td>
                    <td><?= $entry['actionable_flag'] ? 'ANO' : 'NE' ?></td>
                    <td><?= htmlspecialchars($entry['notes']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h2>Nový záznam</h2>
    <form method="POST" action="/glossary">
        <div class="grid">
            <div class="form-group">
                <label>Fráze</label>
                <textarea name="phrase" required></textarea>
            </div>
            <div class="form-group">
                <label>Jazyk</label>
                <input type="text" name="language" value="cs" />
            </div>
            <div class="form-group">
                <label>Práh shody</label>
                <input type="number" step="0.01" name="threshold" value="0.9" />
            </div>
            <div class="form-group">
                <label>Tým</label>
                <select name="team_id">
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Kategorie</label>
                <select name="category_id">
                    <?php foreach ($categories as $items): ?>
                        <?php foreach ($items as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Root cause</label>
                <select name="root_cause_id">
                    <?php foreach ($causes as $items): ?>
                        <?php foreach ($items as $cause): ?>
                            <option value="<?= $cause['id'] ?>"><?= htmlspecialchars($cause['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Poznámka</label>
                <textarea name="notes"></textarea>
            </div>
        </div>
        <label><input type="checkbox" name="actionable_flag" checked /> Akční</label>
        <label><input type="checkbox" name="active" checked /> Aktivní</label>
        <button class="button" type="submit">Přidat</button>
    </form>
</section>
