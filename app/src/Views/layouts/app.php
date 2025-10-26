<?php
use App\Support\Config;
$viewPath = __DIR__.'/../'.$template.'.php';
$config = Config::all();
?><!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Intelligence Suite</title>
    <link rel="stylesheet" href="/resources/css/app.css?v=1">
    <script defer src="/resources/js/app.js?v=1"></script>
</head>
<body class="theme-dark">
    <aside class="sidebar">
        <div class="sidebar-brand">Return Intelligence Suite</div>
        <nav>
            <a href="/">Dashboard</a>
            <a href="/batches">Batch fronta</a>
            <a href="/issues">Přehled problémů</a>
            <a href="/taxonomy">Taxonomie</a>
            <a href="/glossary">Slovníček</a>
            <a href="/proposals">Návrhy</a>
            <a href="/logs">Terminál</a>
        </nav>
    </aside>
    <main class="content">
        <?php include $viewPath; ?>
    </main>
</body>
</html>
