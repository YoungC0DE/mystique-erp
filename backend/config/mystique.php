<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registro público
    |--------------------------------------------------------------------------
    |
    | Quando habilitado, permite POST /api/auth/register.
    | Em produção, mantenha false e use app:create-admin para o primeiro Admin.
    | Se habilitado em instalação vazia, o primeiro registrado vira Admin.
    |
    */

    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Callback de etapa (módulos integrados)
    |--------------------------------------------------------------------------
    */

    'stage_callback' => [
        'timeout' => (int) env('STAGE_CALLBACK_TIMEOUT', 15),
        'connect_timeout' => (int) env('STAGE_CALLBACK_CONNECT_TIMEOUT', 5),
    ],

];
