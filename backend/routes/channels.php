<?php

use App\Models\Module;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('module.{moduleUuid}', function ($user, string $moduleUuid) {
    return Module::where('uuid', $moduleUuid)->exists();
});
