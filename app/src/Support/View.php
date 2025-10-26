<?php

namespace App\Support;

class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__.'/../Views/'.$template.'.php';
        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo 'View not found';
            return;
        }
        include __DIR__.'/../Views/layouts/app.php';
    }
}
