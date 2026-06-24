<?php

use App\Http\Controllers\Admin\ApprovalChainController;
use App\Http\Controllers\Admin\CompanySignerController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EntityController;
use App\Http\Controllers\Admin\GraphApiConfigController;
use App\Http\Controllers\Admin\SmtpSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\FpkController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Support\Roles;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'active', 'verified'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('fpk')->name('fpk.')->group(function () {
        Route::get('/', [FpkController::class, 'index'])->name('index');
        Route::post('/', [FpkController::class, 'store'])->name('store');
        Route::get('/{fpk}', [FpkController::class, 'show'])->name('show');
        Route::put('/{fpk}', [FpkController::class, 'update'])->name('update');
        Route::post('/{fpk}/submit', [FpkController::class, 'submit'])->name('submit');
        Route::post('/{fpk}/approve', [FpkController::class, 'approve'])->name('approve');
        Route::post('/{fpk}/reject', [FpkController::class, 'reject'])->name('reject');
        Route::post('/{fpk}/need-revision', [FpkController::class, 'needRevision'])->name('need-revision');
        Route::post('/{fpk}/close', [FpkController::class, 'close'])->name('close');
        Route::get('/{fpk}/approvals', [FpkController::class, 'approvals'])->name('approvals');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:'.Roles::Admin])
    ->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('entities', EntityController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('approval-chains', ApprovalChainController::class);
        Route::apiResource('company-signers', CompanySignerController::class);
        Route::post('smtp-settings/{smtp_setting}/test-connection', [SmtpSettingController::class, 'testConnection'])
            ->name('smtp-settings.test-connection');
        Route::apiResource('smtp-settings', SmtpSettingController::class);
        Route::post('graph-api-configs/{graph_api_config}/test-connection', [GraphApiConfigController::class, 'testConnection'])
            ->name('graph-api-configs.test-connection');
        Route::apiResource('graph-api-configs', GraphApiConfigController::class);
    });

require __DIR__.'/auth.php';
