<?php

require_once __DIR__.'/src/Support/autoload.php';

use App\Support\Config;
use App\Support\Container;

$defaultConfig = dirname(__DIR__).'/config/app.dist.php';
$customConfig = dirname(__DIR__).'/config/app.php';
Config::load($defaultConfig, $customConfig);

$container = new Container();
$container->instance('config', Config::all());

return $container;
