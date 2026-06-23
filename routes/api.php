<?php

use App\Http\Controllers\Api\RedCrossController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok', 'service' => 'redcross-api']);

Route::middleware('supabase.auth')->group(function (): void {
    Route::get('/me', [RedCrossController::class, 'me']);
    Route::get('/app-data', [RedCrossController::class, 'appData']);
    Route::put('/app-data', [RedCrossController::class, 'saveAppData']);
    Route::get('/dashboard/summary', [RedCrossController::class, 'dashboard']);

    Route::get('/donors', [RedCrossController::class, 'donors']);
    Route::post('/donors', [RedCrossController::class, 'storeDonor']);

    Route::get('/centers', [RedCrossController::class, 'centers']);

    Route::get('/appointments', [RedCrossController::class, 'appointments']);
    Route::post('/appointments', [RedCrossController::class, 'storeAppointment']);

    Route::get('/blood-requests', [RedCrossController::class, 'bloodRequests']);
    Route::post('/blood-requests', [RedCrossController::class, 'storeBloodRequest']);

    Route::get('/inventory', [RedCrossController::class, 'inventory']);
    Route::post('/inventory', [RedCrossController::class, 'storeInventory']);

    Route::get('/campaigns', [RedCrossController::class, 'campaigns']);
    Route::post('/campaigns', [RedCrossController::class, 'storeCampaign']);
    Route::get('/notifications', [RedCrossController::class, 'notifications']);
    Route::post('/notifications', [RedCrossController::class, 'storeNotification']);
    Route::get('/reports/summary', [RedCrossController::class, 'dashboard']);
    Route::get('/audit-logs', [RedCrossController::class, 'auditLogs']);
    Route::post('/audit-logs', [RedCrossController::class, 'storeAuditLog']);
    Route::post('/storage/documents', [RedCrossController::class, 'storeDocument']);
});
