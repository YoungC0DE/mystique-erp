<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Volumes Docker no Windows montam as chaves com permissão 777;
        // o Passport recusa emitir tokens nesse caso. Em local/testes, ignoramos a checagem.
        if ($this->app->environment(['local', 'testing'])) {
            Passport::$validateKeyPermissions = false;
        }

        Passport::enablePasswordGrant();

        Passport::tokensExpireIn(
            Carbon::now()->addSeconds(config('auth_tokens.access_token_ttl'))
        );

        Passport::refreshTokensExpireIn(
            Carbon::now()->addSeconds(config('auth_tokens.refresh_token_ttl'))
        );

        Passport::personalAccessTokensExpireIn(
            Carbon::now()->addSeconds(config('auth_tokens.access_token_ttl'))
        );
    }
}
