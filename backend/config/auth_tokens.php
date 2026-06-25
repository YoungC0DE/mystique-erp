<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Password Grant Client
    |--------------------------------------------------------------------------
    |
    | Credenciais do client "password grant" do Passport, usado internamente
    | pelo AuthService para emitir Access Token + Refresh Token no login.
    | Gere com: php artisan passport:client --password
    |
    */

    'password_client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
    'password_client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes (segundos)
    |--------------------------------------------------------------------------
    */

    'access_token_ttl' => (int) env('ACCESS_TOKEN_TTL', 3600),
    'refresh_token_ttl' => (int) env('REFRESH_TOKEN_TTL', 2592000),

];
