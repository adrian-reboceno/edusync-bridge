<?php

use Academic\Student\Infrastructure\Http\Controllers\UserAnalyticsController;
use Auth\AuditLog\Infrastructure\Http\Controllers\AuditLogController;
use Auth\Role\Infrastructure\Http\Controllers\RoleController;
use Auth\User\Infrastructure\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('2fa/verify', [AuthController::class, 'verify2fa'])->middleware('throttle:5,1');
    Route::post('2fa/setup',  [AuthController::class, 'setup2fa']);
    Route::post('2fa/enable', [AuthController::class, 'enable2fa']);

    Route::middleware('rbac2')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('session/role', [AuthController::class, 'switchRole']);
        Route::get('me',             [AuthController::class, 'me']);
    });
});

Route::prefix('v1/users')->middleware('rbac2')->group(function (): void {
    Route::post('{id}/roles', [RoleController::class, 'assign'])->middleware('rbac2:auth.roles.assign');
    Route::delete('{id}/roles/{roleId}', [RoleController::class, 'revoke'])->middleware('rbac2:auth.roles.revoke');
    Route::post('{id}/unlock', [AuthController::class, 'unlock'])->middleware('rbac2:auth.users.unlock');
});

Route::prefix('v1')->middleware('rbac2')->group(function (): void {
    Route::get('roles', [RoleController::class, 'index'])->middleware('rbac2:auth.roles.view');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('rbac2:auth.audit.view');
    Route::get('audit-logs/export', [AuditLogController::class, 'export'])->middleware('rbac2:auth.audit.export');
});

Route::prefix('v1/analytics')->middleware('rbac2')->group(function (): void {
    Route::get('users/summary', [UserAnalyticsController::class, 'summary'])
        ->middleware('rbac2:reports.sync.view');
    Route::get('users/daily-access', [UserAnalyticsController::class, 'dailyAccess'])
        ->middleware('rbac2:reports.sync.view');
    Route::get('users/{neoId}', [UserAnalyticsController::class, 'show'])
        ->whereNumber('neoId')
        ->middleware('rbac2:reports.sync.view');
    Route::get('users', [UserAnalyticsController::class, 'index'])
        ->middleware('rbac2:reports.sync.view');
});
