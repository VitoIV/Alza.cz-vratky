<?php

namespace App\Controllers;

use App\Support\View;

class LogController
{
    public function index(): void
    {
        View::render('logs/index');
    }

    public function feed(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Logy jsou dostupné přímo v detailu jednotlivých batchů (tlačítko Log).";
    }
}
