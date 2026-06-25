<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareTestDatabase extends Command
{
    protected $signature = 'app:prepare-test-database {--database=mystique_test : Nome do banco de testes}';

    protected $description = 'Cria o banco de dados isolado utilizado pela suíte de testes.';

    public function handle(): int
    {
        $database = (string) $this->option('database');

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        // Conecta ao servidor sem selecionar um database para poder criá-lo.
        config(["database.connections.{$connection}.database" => null]);
        DB::purge($connection);

        DB::connection($connection)->statement(
            "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        config(["database.connections.{$connection}.database" => $config['database']]);
        DB::purge($connection);

        $this->info("Banco de testes '{$database}' pronto.");

        return self::SUCCESS;
    }
}
