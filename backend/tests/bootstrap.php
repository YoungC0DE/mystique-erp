<?php

/**
 * Bootstrap da suíte PHPUnit.
 *
 * Ajusta DB_HOST conforme o ambiente de execução:
 * - Dentro do container Docker → host "mysql" (rede interna)
 * - No host (WSL/Windows com porta 3306 exposta) → 127.0.0.1
 */
require __DIR__.'/../vendor/autoload.php';

$runningInDocker = file_exists('/.dockerenv');

$dbHost = $runningInDocker ? 'mysql' : '127.0.0.1';

putenv("DB_HOST={$dbHost}");
$_ENV['DB_HOST'] = $dbHost;
$_SERVER['DB_HOST'] = $dbHost;
