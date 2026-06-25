<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin
                            {--name= : Nome do Admin}
                            {--email= : E-mail do Admin}
                            {--password= : Senha do Admin}';

    protected $description = 'Cria o usuário Admin da instalação (single-tenant).';

    public function handle(): int
    {
        if (! $this->passportIsReady()) {
            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Nome');
        $email = $this->option('email') ?: $this->ask('E-mail');
        $password = $this->option('password') ?: $this->secret('Senha');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', Rule::unique('users', 'email')],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info("Admin criado com sucesso: {$user->email}");

        return self::SUCCESS;
    }

    private function passportIsReady(): bool
    {
        if (! is_file(storage_path('oauth-private.key')) || ! is_file(storage_path('oauth-public.key'))) {
            $this->error('Chaves OAuth do Passport não encontradas.');
            $this->line('Execute: php artisan passport:keys');

            return false;
        }

        $hasPasswordClient = DB::table('oauth_clients')
            ->where('revoked', false)
            ->where('grant_types', 'like', '%password%')
            ->exists();

        if (! $hasPasswordClient) {
            $this->error('Password grant client do Passport não encontrado.');
            $this->line('Execute: php artisan app:ensure-passport-password-client');
            $this->line('Depois copie PASSPORT_PASSWORD_CLIENT_ID e PASSPORT_PASSWORD_CLIENT_SECRET para o .env.');

            return false;
        }

        if (empty(config('auth_tokens.password_client_id')) || empty(config('auth_tokens.password_client_secret'))) {
            $this->error('PASSPORT_PASSWORD_CLIENT_ID ou PASSPORT_PASSWORD_CLIENT_SECRET não estão no .env.');
            $this->line('Execute: php artisan app:ensure-passport-password-client');

            return false;
        }

        return true;
    }
}
