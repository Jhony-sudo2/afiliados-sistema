<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    http_response_code(500);
    echo 'Dependencias no instaladas. Ejecuta composer install antes de usar el sistema.';
    exit;
}

require $autoload;

if (file_exists(dirname(__DIR__) . '/.env')) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

session_name(app_env('SESSION_NAME', 'sistema_territorial_session'));
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('America/Guatemala');
