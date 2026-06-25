<?php

use App\Http\Controllers\Api\DatabaseConnectionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KanbanController;
use App\Http\Controllers\Api\ModuleFieldController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\ModuleRecordNoteController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::put('me', [ProfileController::class, 'update']);
    Route::put('me/password', [ProfileController::class, 'updatePassword']);

    Route::apiResource('connections', DatabaseConnectionController::class);
    Route::post('connections/validate', [DatabaseConnectionController::class, 'validateConnection']);
    Route::post('connections/{connection}/test', [DatabaseConnectionController::class, 'test']);
    Route::get('connections/{connection}/columns', [DatabaseConnectionController::class, 'columns']);

    Route::get('permissions', [PermissionController::class, 'index']);

    Route::apiResource('roles', RoleController::class);
    Route::apiResource('users', UserController::class);

    Route::get('me/modules', [ModuleController::class, 'allowed']);
    Route::put('modules/{module}/layout', [ModuleController::class, 'updateLayout']);
    Route::apiResource('modules', ModuleController::class);
    Route::apiResource('modules.fields', ModuleFieldController::class)->scoped()->shallow();

    Route::get('records/{record}/audits', [RecordController::class, 'audits']);
    Route::apiResource('modules.records', RecordController::class)->scoped()->shallow();

    Route::get('modules/{module}/kanban', [KanbanController::class, 'board']);
    Route::get('modules/{module}/records/{recordKey}/note', [ModuleRecordNoteController::class, 'show']);
    Route::put('modules/{module}/records/{recordKey}/note', [ModuleRecordNoteController::class, 'update']);
    Route::post('modules/{module}/records/{externalId}/move', [KanbanController::class, 'moveIntegrated']);
    Route::put('records/{record}/move', [KanbanController::class, 'move']);

    Route::get('reports/{report}/run', [ReportController::class, 'run']);
    Route::apiResource('reports', ReportController::class);

    Route::post('broadcasting/auth', fn (Request $request) => Broadcast::auth($request));
});
