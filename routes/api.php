<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\MetricController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\AlertRuleController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WebsiteController;
use App\Http\Controllers\StatusPageController;

Route::get('/status/snapshot', [StatusPageController::class, 'snapshot']);
Route::post('/status/targets', [StatusPageController::class, 'store'])->middleware('throttle:12,1');
Route::post('/status/targets/{statusMonitor}/refresh', [StatusPageController::class, 'refresh'])->middleware('throttle:30,1');

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/health', [DashboardController::class, 'health']);

    Route::apiResource('servers', ServerController::class);
    Route::post('/servers/{server}/test-connection', [ServerController::class, 'testConnection']);

    Route::get('/servers/{server}/metrics', [MetricController::class, 'index']);
    Route::get('/servers/{server}/metrics/latest', [MetricController::class, 'latest']);
    Route::get('/servers/{server}/metrics/history', [MetricController::class, 'history']);

    Route::get('/servers/{server}/services', [ServiceController::class, 'index']);
    Route::get('/servers/{server}/services/{service}', [ServiceController::class, 'show']);
    Route::get('/servers/{server}/services/{service}/history', [ServiceController::class, 'history']);
    Route::post('/servers/{server}/services/{service}/restart', [ServiceController::class, 'restart'])->middleware('role:superadmin,admin,operator');
    Route::post('/servers/{server}/services/{service}/start', [ServiceController::class, 'start'])->middleware('role:superadmin,admin,operator');
    Route::post('/servers/{server}/services/{service}/stop', [ServiceController::class, 'stop'])->middleware('role:superadmin,admin,operator');

    Route::get('/servers/{server}/websites', [WebsiteController::class, 'index']);
    Route::post('/servers/{server}/websites', [WebsiteController::class, 'store']);
    Route::get('/websites/{website}', [WebsiteController::class, 'show']);
    Route::put('/websites/{website}', [WebsiteController::class, 'update']);
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy']);
    Route::post('/websites/{website}/check', [WebsiteController::class, 'check']);
    Route::get('/websites/{website}/history', [WebsiteController::class, 'history']);

    Route::get('/alerts', [AlertController::class, 'index']);
    Route::get('/alerts/{alert}', [AlertController::class, 'show']);
    Route::post('/alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge']);
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);

    Route::apiResource('alert-rules', AlertRuleController::class)->except(['show']);
    Route::post('/alert-rules/{alert_rule}/toggle', [AlertRuleController::class, 'toggle']);

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('role:superadmin,admin');
    Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show'])->middleware('role:superadmin,admin');

    Route::get('/settings', [SettingsController::class, 'index'])->middleware('role:superadmin,admin');
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('role:superadmin');

    Route::apiResource('users', UserController::class)->middleware('role:superadmin,admin');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('role:superadmin,admin');
});
