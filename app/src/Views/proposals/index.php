<section class="card">
    <h1>Návrhy z GPT</h1>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Typ</th>
                <th>Název návrhu</th>
                <th>Popis</th>
                <th>Důvěra</th>
                <th>Text zákazníka</th>
                <th>Akce</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($proposals as $proposal): ?>
                <tr>
                    <td><?= $proposal['id'] ?></td>
                    <td><?= htmlspecialchars($proposal['type']) ?></td>
                    <td><?= htmlspecialchars($proposal['suggestion_name']) ?></td>
                    <td><?= htmlspecialchars($proposal['suggestion_description']) ?></td>
                    <td><?= number_format((float) $proposal['confidence'], 2) ?></td>
                    <td><strong><?= htmlspecialchars($proposal['product_name']) ?>:</strong> <?= htmlspecialchars($proposal['issue_text']) ?></td>
                    <td>
                        <form method="POST" action="/proposals/<?= $proposal['id'] ?>/resolve" style="display:flex; flex-direction:column; gap:8px;">
                            <input type="hidden" name="type" value="<?= htmlspecialchars($proposal['type']) ?>" />
                            <input type="hidden" name="key" value="<?= htmlspecialchars($proposal['suggestion_key']) ?>" />
                            <input type="hidden" name="name" value="<?= htmlspecialchars($proposal['suggestion_name']) ?>" />
                            <textarea name="description" placeholder="Popis" required><?= htmlspecialchars($proposal['suggestion_description']) ?></textarea>
                            <textarea name="prompt_definition" placeholder="Prompt definice" required><?= htmlspecialchars($proposal['suggestion_prompt_definition']) ?></textarea>
                            <select name="team_id">
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="category_id">
                                <?php foreach ($categories as $items): ?>
                                    <?php foreach ($items as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="flex" style="gap:8px;">
                                <button class="button" type="submit" name="action" value="approve">Schválit</button>
                                <button class="button-secondary" type="submit" name="action" value="reject">Zamítnout</button>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
