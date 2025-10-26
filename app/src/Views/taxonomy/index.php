<section class="card">
    <h1>Taxonomie</h1>
    <div class="tabs">
        <a href="#teams" class="active">Týmy</a>
        <a href="#categories">Kategorie</a>
        <a href="#causes">Root causes</a>
    </div>
    <h2 id="teams">Týmy</h2>
    <div class="grid">
        <?php foreach ($teams as $team): ?>
            <div class="card">
                <h3><?= htmlspecialchars($team['name']) ?> (<?= htmlspecialchars($team['key']) ?>)</h3>
                <p><?= nl2br(htmlspecialchars($team['description'])) ?></p>
                <details>
                    <summary>Prompt definice</summary>
                    <pre><?= htmlspecialchars($team['prompt_definition']) ?></pre>
                </details>
                <form method="POST" action="/taxonomy/team/<?= $team['id'] ?>/update">
                    <div class="form-group">
                        <label>Název</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($team['name']) ?>" />
                    </div>
                    <div class="form-group">
                        <label>Popis</label>
                        <textarea name="description"><?= htmlspecialchars($team['description']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Prompt definice</label>
                        <textarea name="prompt_definition"><?= htmlspecialchars($team['prompt_definition']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Pozice</label>
                        <input type="number" name="position" value="<?= (int) $team['position'] ?>" />
                    </div>
                    <button class="button" type="submit">Uložit</button>
                </form>
            </div>
        <?php endforeach; ?>
        <div class="card">
            <h3>Nový tým</h3>
            <form method="POST" action="/taxonomy">
                <input type="hidden" name="entity_type" value="team" />
                <div class="form-group">
                    <label>Klíč</label>
                    <input type="text" name="key" required />
                </div>
                <div class="form-group">
                    <label>Název</label>
                    <input type="text" name="name" required />
                </div>
                <div class="form-group">
                    <label>Popis</label>
                    <textarea name="description"></textarea>
                </div>
                <div class="form-group">
                    <label>Prompt definice</label>
                    <textarea name="prompt_definition"></textarea>
                </div>
                <button class="button" type="submit">Vytvořit</button>
            </form>
        </div>
    </div>
    <h2 id="categories">Kategorie</h2>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Tým</th>
                    <th>Název</th>
                    <th>Klíč</th>
                    <th>Aktivní</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $teamId => $items): ?>
                    <?php foreach ($items as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($teams[array_search($teamId, array_column($teams, 'id'))]['name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td><?= htmlspecialchars($category['key']) ?></td>
                            <td><?= $category['active'] ? 'ANO' : 'NE' ?></td>
                            <td>
                                <form method="POST" action="/taxonomy/category/<?= $category['id'] ?>/update">
                                    <input type="hidden" name="team_id" value="<?= (int) $category['team_id'] ?>" />
                                    <div class="form-group">
                                        <input type="text" name="name" value="<?= htmlspecialchars($category['name']) ?>" />
                                    </div>
                                    <div class="form-group">
                                        <textarea name="description"><?= htmlspecialchars($category['description']) ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <textarea name="prompt_definition"><?= htmlspecialchars($category['prompt_definition']) ?></textarea>
                                    </div>
                                    <label><input type="checkbox" name="active" <?= $category['active'] ? 'checked' : '' ?> /> Aktivní</label>
                                    <button class="button" type="submit">Uložit</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Nová kategorie</h3>
        <form method="POST" action="/taxonomy">
            <input type="hidden" name="entity_type" value="category" />
            <div class="form-group">
                <label>Tým</label>
                <select name="team_id">
                    <?php foreach ($teams as $team): ?>
                        <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Klíč</label>
                <input type="text" name="key" required />
            </div>
            <div class="form-group">
                <label>Název</label>
                <input type="text" name="name" required />
            </div>
            <div class="form-group">
                <label>Popis</label>
                <textarea name="description"></textarea>
            </div>
            <div class="form-group">
                <label>Prompt definice</label>
                <textarea name="prompt_definition"></textarea>
            </div>
            <button class="button" type="submit">Vytvořit</button>
        </form>
    </div>
    <h2 id="causes">Root causes</h2>
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Kategorie</th>
                    <th>Klíč</th>
                    <th>Název</th>
                    <th>Aktivní</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($causes as $categoryId => $items): ?>
                    <?php foreach ($items as $cause): ?>
                        <tr>
                            <td><?= htmlspecialchars($cause['category_id']) ?></td>
                            <td><?= htmlspecialchars($cause['key']) ?></td>
                            <td><?= htmlspecialchars($cause['name']) ?></td>
                            <td><?= $cause['active'] ? 'ANO' : 'NE' ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3>Nový root cause</h3>
        <form method="POST" action="/taxonomy">
            <input type="hidden" name="entity_type" value="root_cause" />
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
                    <?php foreach ($categories as $teamId => $items): ?>
                        <?php foreach ($items as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Klíč</label>
                <input type="text" name="key" required />
            </div>
            <div class="form-group">
                <label>Název</label>
                <input type="text" name="name" required />
            </div>
            <div class="form-group">
                <label>Popis</label>
                <textarea name="description"></textarea>
            </div>
            <div class="form-group">
                <label>Prompt definice</label>
                <textarea name="prompt_definition"></textarea>
            </div>
            <button class="button" type="submit">Vytvořit</button>
        </form>
    </div>
</section>
