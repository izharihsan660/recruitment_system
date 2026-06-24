<?php

use App\Http\Controllers\Admin\ApprovalChainController;
use App\Http\Controllers\Admin\CompanyProfileController;
use App\Http\Controllers\Admin\CompanySignerController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EntityController;
use App\Http\Controllers\Admin\GraphApiConfigController;
use App\Http\Controllers\Admin\SmtpSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CandidateAuthController;
use App\Http\Controllers\CandidateDocumentController;
use App\Http\Controllers\CandidatePortalController;
use App\Http\Controllers\FpkController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PortalController;
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

    Route::get('/job-postings', [JobPostingController::class, 'index'])->name('job-postings.index');
    Route::post('/job-postings', [JobPostingController::class, 'store'])->name('job-postings.store');
    Route::get('/job-postings/{job_posting}', [JobPostingController::class, 'show'])->name('job-postings.show');
    Route::put('/job-postings/{job_posting}', [JobPostingController::class, 'update'])->name('job-postings.update');
    Route::post('/job-postings/{job_posting}/open', [JobPostingController::class, 'open'])->name('job-postings.open');
    Route::post('/job-postings/{job_posting}/close', [JobPostingController::class, 'close'])->name('job-postings.close');
    Route::post('/job-postings/{job_posting}/cancel', [JobPostingController::class, 'cancel'])->name('job-postings.cancel');
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
        Route::put('company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');
        Route::post('company-profile/hero-image', [CompanyProfileController::class, 'heroImage'])->name('company-profile.hero-image');
        Route::post('company-profile/gallery', [CompanyProfileController::class, 'gallery'])->name('company-profile.gallery');
        Route::delete('company-profile/gallery/{index}', [CompanyProfileController::class, 'deleteGallery'])->name('company-profile.gallery.delete');
    });

Route::prefix('candidate')->name('candidate.')->group(function () {
    Route::post('register', [CandidateAuthController::class, 'register'])->name('register');
    Route::post('login', [CandidateAuthController::class, 'login'])->name('login');

    Route::middleware('auth:candidate')->group(function () {
        Route::post('logout', [CandidateAuthController::class, 'logout'])->name('logout');
        Route::get('profile', [CandidateAuthController::class, 'profile'])->name('profile');
        Route::put('profile', [CandidateAuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('cv', [CandidateAuthController::class, 'uploadCv'])->name('cv.store');
        Route::get('jobs', [CandidatePortalController::class, 'jobs'])->name('jobs.index');
        Route::get('jobs/{job_posting}', [CandidatePortalController::class, 'job'])->name('jobs.show');
        Route::post('jobs/{job_posting}/apply', [CandidatePortalController::class, 'apply'])->name('jobs.apply');
        Route::post('jobs/{job_posting}/withdraw', [CandidatePortalController::class, 'withdraw'])->name('jobs.withdraw');
        Route::get('applications', [CandidatePortalController::class, 'applications'])->name('applications.index');
        Route::get('applications/{application}', [CandidatePortalController::class, 'application'])->name('applications.show');
        Route::post('applications/{application}/documents', [CandidateDocumentController::class, 'store'])->name('applications.documents.store');
        Route::get('applications/{application}/documents', [CandidateDocumentController::class, 'index'])->name('applications.documents.index');
        Route::delete('applications/{application}/documents/{docId}', [CandidateDocumentController::class, 'destroy'])->name('applications.documents.destroy');
    });
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('company-profile', [PortalController::class, 'companyProfile'])->name('company-profile.show');
    Route::get('jobs', [PortalController::class, 'jobs'])->name('jobs.index');
    Route::get('jobs/{job_posting}', [PortalController::class, 'job'])->name('jobs.show');
});

require __DIR__.'/auth.php';
