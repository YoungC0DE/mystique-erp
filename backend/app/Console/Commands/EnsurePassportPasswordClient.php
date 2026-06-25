<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;

class EnsurePassportPasswordClient extends Command
{
    protected $signature = 'app:ensure-passport-password-client
                            {--name=Mystique Password Grant : Nome do client OAuth}
                            {--provider=users : Provider de autenticação}';

    protected $description = 'Cria (ou recria) o password grant client do Passport e exibe as credenciais para o .env';

    public function handle(ClientRepository $clients): int
    {
        $client = $clients->createPasswordGrantClient(
            $this->option('name'),
            $this->option('provider'),
            confidential: true,
        );

        $this->newLine();
        $this->info('Password grant client criado com sucesso.');
        $this->newLine();
        $this->line('Adicione/atualize no backend/.env:');
        $this->newLine();
        $this->line("PASSPORT_PASSWORD_CLIENT_ID={$client->getKey()}");
        $this->line("PASSPORT_PASSWORD_CLIENT_SECRET={$client->plainSecret}");
        $this->newLine();
        $this->warn('O secret não será exibido novamente. Salve agora no .env.');
        $this->line('Depois rode: php artisan config:clear');

        return self::SUCCESS;
    }
}
