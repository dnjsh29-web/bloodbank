<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'landing'])->name('landing');

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/resend-confirmation', [AuthController::class, 'resendConfirmation'])->name('confirmation.resend');
Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.forgot');
Route::get('/password/reset', [AuthController::class, 'showPasswordReset'])->name('password.reset');
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('session.auth')->name('logout');

Route::middleware('session.auth')->group(function (): void {
    Route::post('/notifications/{notification}/read', [PortalController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [PortalController::class, 'markNotificationsRead'])->name('notifications.readAll');
});

Route::middleware(['session.auth', 'role:donor'])->group(function (): void {
    Route::get('/donor', [PortalController::class, 'donor'])->name('donor.shell');
    Route::get('/donor/panel/{section}', [PortalController::class, 'donorPanel'])->name('donor.panel');
    Route::get('/donor/dashboard', fn () => redirect('/donor#dashboard'))->name('donor.dashboard');
    Route::get('/donor/schedule', fn () => redirect('/donor#schedule'))->name('donor.schedule');
    Route::post('/donor/schedule', [PortalController::class, 'saveAppointment'])->name('donor.schedule.save');
    Route::get('/donor/history', fn () => redirect('/donor#history'))->name('donor.history');
    Route::get('/donor/personal-info', fn () => redirect('/donor#personal-info'))->name('donor.profile');
    Route::post('/donor/personal-info', [PortalController::class, 'updateProfile'])->name('donor.profile.save');
    Route::get('/donor/account-security', fn () => redirect('/donor#account-security'))->name('donor.security');
    Route::post('/donor/account-security', [PortalController::class, 'updateSecurity'])->name('donor.security.save');
    Route::get('/blood-request', [PortalController::class, 'bloodRequest'])->name('blood.request');
    Route::post('/blood-request', [PortalController::class, 'saveBloodRequest'])->name('blood.request.save');
});

Route::middleware(['session.auth', 'role:admin,super_admin'])->group(function (): void {
    Route::get('/admin/panel/{section}', [PortalController::class, 'adminPanel'])->name('admin.panel');
    Route::get('/admin/{section?}', [PortalController::class, 'admin'])->name('admin.section');
    Route::post('/account-profile', [PortalController::class, 'updateStaffProfile'])->name('staff.profile.save');
    Route::post('/admin/donors', [PortalController::class, 'storeDonor'])->name('admin.donors.store');
    Route::post('/admin/inventory', [PortalController::class, 'storeInventory'])->name('admin.inventory.store');
    Route::post('/admin/campaigns', [PortalController::class, 'storeCampaign'])->name('admin.campaigns.store');
    Route::post('/admin/appointments/{appointment}/review', [PortalController::class, 'reviewAppointment'])->name('admin.appointments.review');
    Route::post('/admin/audit-action', [PortalController::class, 'auditAction'])->name('admin.audit.action');
    Route::get('/reports/{type}', [ReportController::class, 'download'])->name('reports.download');
});

Route::middleware(['session.auth', 'role:super_admin'])->group(function (): void {
    Route::get('/super-admin/panel/{section}', [PortalController::class, 'superPanel'])->name('super.panel');
    Route::get('/super-admin/{section?}', [PortalController::class, 'super'])->name('super.section');
    Route::post('/super-admin/appointments/{appointment}/review', [PortalController::class, 'reviewAppointment'])->name('super.appointments.review');
    Route::post('/super-admin/audit-action', [PortalController::class, 'auditAction'])->name('super.audit.action');
});
