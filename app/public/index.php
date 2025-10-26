<?php

use App\Support\Router;
use App\Controllers\DashboardController;
use App\Controllers\BatchController;
use App\Controllers\IssueController;
use App\Controllers\TaxonomyController;
use App\Controllers\GlossaryController;
use App\Controllers\ProposalController;
use App\Controllers\LogController;

$container = require __DIR__.'/../bootstrap.php';

$router = new Router();

$router->get('/', [new DashboardController(), 'index']);
$router->get('/batches', [new BatchController(), 'index']);
$router->get('/batches/upload', [new BatchController(), 'upload']);
$router->post('/batches/upload', [new BatchController(), 'upload']);
$router->get('/batches/(\d+)', function ($id) {
    (new BatchController())->show((int) $id);
});
$router->post('/batches/(\d+)/process', function ($id) {
    (new BatchController())->process((int) $id);
});
$router->post('/batches/(\d+)/pause', function ($id) {
    (new BatchController())->pause((int) $id);
});
$router->post('/batches/(\d+)/resume', function ($id) {
    (new BatchController())->resume((int) $id);
});
$router->get('/batches/(\d+)/logs', function ($id) {
    (new BatchController())->logs((int) $id);
});

$router->get('/issues', [new IssueController(), 'index']);
$router->get('/issues/(\d+)', function ($id) {
    (new IssueController())->show((int) $id);
});

$router->get('/taxonomy', [new TaxonomyController(), 'index']);
$router->post('/taxonomy', [new TaxonomyController(), 'store']);
$router->post('/taxonomy/team/(\d+)/update', function ($id) {
    (new TaxonomyController())->update('team', (int) $id);
});
$router->post('/taxonomy/category/(\d+)/update', function ($id) {
    (new TaxonomyController())->update('category', (int) $id);
});
$router->post('/taxonomy/root_cause/(\d+)/update', function ($id) {
    (new TaxonomyController())->update('root_cause', (int) $id);
});

$router->get('/glossary', [new GlossaryController(), 'index']);
$router->post('/glossary', [new GlossaryController(), 'store']);
$router->post('/glossary/(\d+)/update', function ($id) {
    (new GlossaryController())->update((int) $id);
});
$router->post('/glossary/(\d+)/delete', function ($id) {
    (new GlossaryController())->delete((int) $id);
});

$router->get('/proposals', [new ProposalController(), 'index']);
$router->post('/proposals/(\d+)/resolve', function ($id) {
    (new ProposalController())->resolve((int) $id);
});

$router->get('/logs', [new LogController(), 'index']);
$router->get('/logs/feed', [new LogController(), 'feed']);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($method, $uri);
