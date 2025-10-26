<section class="card">
    <h1>Nahrát nový batch</h1>
    <?php if (!empty($error)): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Název batch</label>
            <input type="text" name="name" placeholder="Např. 2025-10-24 odchozí" />
        </div>
        <div class="form-group">
            <label>Soubor .xlsx</label>
            <input type="file" name="file" accept=".xlsx" required />
        </div>
        <button class="button" type="submit">Spustit zpracování</button>
    </form>
</section>
