<?php

use App\Http\Controllers\Admin\ApprovalChainController;
use App\Http\Controllers\Admin\CandidateSourceController;
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
use App\Http\Controllers\EmailIntakeController;
use App\Http\Controllers\FpkController;
use App\Http\Controllers\HrCandidateInputController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TalentPoolController;
use App\Models\Application as CandidateApplication;
use App\Models\ApprovalChain;
use App\Models\CandidateSource;
use App\Models\CompanyProfile;
use App\Models\CompanySigner;
use App\Models\Department;
use App\Models\EmailIntake;
use App\Models\Entity;
use App\Models\GraphApiConfig;
use App\Models\JobPosting;
use App\Models\RecruitmentRequest;
use App\Models\SmtpSetting;
use App\Models\TalentPool;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
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

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    $isHr = $user?->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager]) === true;

    $fpkQuery = RecruitmentRequest::query();
    $pipelineQuery = CandidateApplication::query()->whereNotIn('status', ['rejected', 'withdrawn']);

    if (! $isHr && $user?->department_id) {
        $fpkQuery->where('department_id', $user->department_id);
        $pipelineQuery->whereHas('jobPosting', fn ($query) => $query->where('department_id', $user->department_id));
    }

    $kpis = $isHr
        ? [
            'total_fpk_aktif' => (clone $fpkQuery)->whereIn('status', ['draft', 'in_approval', 'approved', 'need_revision'])->count(),
            'menunggu_approval' => (clone $fpkQuery)->where('status', 'in_approval')->count(),
            'lowongan_aktif' => JobPosting::query()->where('status', 'open')->count(),
            'kandidat_pipeline' => (clone $pipelineQuery)->count(),
            'hired_bulan_ini' => CandidateApplication::query()->where('status', 'hired')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
            'ukuran_talent_pool' => TalentPool::query()->count(),
            'probation_berjalan' => 0,
            'probation_jatuh_tempo' => 0,
        ]
        : [
            'fpk_saya' => (clone $fpkQuery)->where('requester_id', $user?->id)->count(),
            'menunggu_approval_saya' => (clone $fpkQuery)->where('status', 'in_approval')->count(),
            'pipeline_dept_saya' => (clone $pipelineQuery)->count(),
            'hired_bulan_ini_dept_saya' => (clone $pipelineQuery)->where('status', 'hired')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count(),
        ];

    return Inertia::render('Dashboard', [
        'kpis' => $kpis,
    ]);
})->middleware(['auth', 'active', 'verified'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/fpk/create', fn () => Inertia::render('Fpk/Form', [
        'mode' => 'create',
        'fpk' => null,
        'entities' => Entity::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'short_name']),
        'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'entity_id']),
    ]))->name('fpk.create');
    Route::get('/fpk/{fpk}/edit', fn (RecruitmentRequest $fpk) => Inertia::render('Fpk/Form', [
        'mode' => 'edit',
        'fpk' => $fpk->load(['entity', 'department', 'approvalRecords.approver']),
        'entities' => Entity::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'short_name']),
        'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'entity_id']),
    ]))->name('fpk.edit');

    Route::prefix('fpk')->name('fpk.')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Fpk/Index', [
                'fpk' => RecruitmentRequest::query()->with(['entity', 'department', 'requester'])->latest()->paginate(10),
                'entities' => Entity::query()->orderBy('name')->get(['id', 'name', 'short_name']),
                'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            ]);
        })->name('index');
        Route::post('/', [FpkController::class, 'store'])->name('store');
        Route::get('/{fpk}', function (RecruitmentRequest $fpk) {
            return Inertia::render('Fpk/Show', [
                'fpk' => $fpk->load(['entity', 'department', 'requester', 'approvalRecords.approver']),
            ]);
        })->name('show');
        Route::put('/{fpk}', [FpkController::class, 'update'])->name('update');
        Route::post('/{fpk}/submit', [FpkController::class, 'submit'])->name('submit');
        Route::post('/{fpk}/approve', [FpkController::class, 'approve'])->name('approve');
        Route::post('/{fpk}/reject', [FpkController::class, 'reject'])->name('reject');
        Route::post('/{fpk}/need-revision', [FpkController::class, 'needRevision'])->name('need-revision');
        Route::post('/{fpk}/close', [FpkController::class, 'close'])->name('close');
        Route::get('/{fpk}/approvals', [FpkController::class, 'approvals'])->name('approvals');
    });

    Route::get('/notifications', function () {
        return Inertia::render('Notifications/Index', [
            'notifications' => request()->user()->notifications()->latest()->paginate(15),
        ]);
    })->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/job-postings', function () {
        return Inertia::render('JobPostings/Index', [
            'jobPostings' => JobPosting::query()->with(['entity', 'department', 'recruitmentRequest'])->latest()->paginate(10),
        ]);
    })->name('job-postings.index');
    Route::get('/job-postings/create', fn () => Inertia::render('JobPostings/Form', [
        'mode' => 'create',
        'jobPosting' => null,
        'approvedFpk' => RecruitmentRequest::query()->where('status', 'approved')->latest()->get(['id', 'position_name', 'entity_id', 'department_id']),
    ]))->name('job-postings.create');
    Route::get('/job-postings/{job_posting}/edit', fn (JobPosting $jobPosting) => Inertia::render('JobPostings/Form', [
        'mode' => 'edit',
        'jobPosting' => $jobPosting->load(['entity', 'department', 'recruitmentRequest']),
        'approvedFpk' => RecruitmentRequest::query()->where('status', 'approved')->latest()->get(['id', 'position_name', 'entity_id', 'department_id']),
    ]))->name('job-postings.edit');
    Route::post('/job-postings', [JobPostingController::class, 'store'])->name('job-postings.store');
    Route::get('/job-postings/{job_posting}', function (JobPosting $jobPosting) {
        $jobPosting->load(['entity', 'department', 'recruitmentRequest']);

        return Inertia::render('JobPostings/Show', [
            'jobPosting' => $jobPosting,
            'applications' => CandidateApplication::query()->where('job_posting_id', $jobPosting->id)->with('candidate')->latest()->get(),
        ]);
    })->name('job-postings.show');
    Route::put('/job-postings/{job_posting}', [JobPostingController::class, 'update'])->name('job-postings.update');
    Route::post('/job-postings/{job_posting}/open', [JobPostingController::class, 'open'])->name('job-postings.open');
    Route::post('/job-postings/{job_posting}/close', [JobPostingController::class, 'close'])->name('job-postings.close');
    Route::post('/job-postings/{job_posting}/cancel', [JobPostingController::class, 'cancel'])->name('job-postings.cancel');

    Route::get('/pipeline', function () {
        return Inertia::render('Pipeline/Index', [
            'applications' => CandidateApplication::query()->with(['candidate', 'jobPosting'])->latest()->get(),
            'jobPostings' => JobPosting::query()->where('status', 'open')->orderBy('position_name')->get(['id', 'position_name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'sources' => CandidateSource::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    })->name('pipeline.index');

    Route::get('/preboarding', fn () => Inertia::render('Preboarding/Index'))->name('preboarding.index');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'active', 'role:'.Roles::Admin])
    ->group(function () {
        Route::get('entities', fn () => Inertia::render('Admin/Entities/Index', [
            'entities' => Entity::query()->latest()->paginate(10),
        ]))->name('entities.index');
        Route::get('departments', fn () => Inertia::render('Admin/Departments/Index', [
            'departments' => Department::query()->with('entity')->latest()->paginate(10),
            'entities' => Entity::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]))->name('departments.index');
        Route::get('approval-chains', fn () => Inertia::render('Admin/ApprovalChains/Index', [
            'approvalChains' => ApprovalChain::query()->with(['department.entity', 'approverUser.roles'])->orderBy('department_id')->orderBy('level')->get(),
            'departments' => Department::query()->with('entity')->orderBy('name')->get(['id', 'name', 'entity_id']),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
        ]))->name('approval-chains.index');
        Route::get('company-signers', fn () => Inertia::render('Admin/CompanySigners/Index', [
            'companySigners' => CompanySigner::query()->with(['entity', 'user.roles'])->latest()->paginate(10),
            'entities' => Entity::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']),
        ]))->name('company-signers.index');
        Route::get('candidate-sources', fn () => Inertia::render('Admin/CandidateSources/Index', [
            'candidateSources' => CandidateSource::query()->latest()->paginate(10),
        ]))->name('candidate-sources.index');
        Route::get('smtp', fn () => Inertia::render('Admin/Configurations/Smtp', [
            'smtpSettings' => SmtpSetting::query()->latest()->get(),
        ]));
        Route::get('graph-api', fn () => Inertia::render('Admin/Configurations/GraphApi', [
            'graphApiConfigs' => GraphApiConfig::query()->latest()->get(),
        ]));
        Route::get('cms', fn () => Inertia::render('Admin/Configurations/Cms', [
            'companyProfile' => CompanyProfile::query()->first(),
        ]));

        Route::apiResource('users', UserController::class);
        Route::apiResource('entities', EntityController::class)->except(['index']);
        Route::apiResource('departments', DepartmentController::class)->except(['index']);
        Route::apiResource('approval-chains', ApprovalChainController::class)->except(['index']);
        Route::apiResource('candidate-sources', CandidateSourceController::class)->except(['index']);
        Route::apiResource('company-signers', CompanySignerController::class)->except(['index']);
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

Route::middleware(['auth', 'active', 'role:'.Roles::HrRecruiter.'|'.Roles::HrManager])->group(function () {
    Route::get('/hr/candidates/input', fn () => Inertia::render('Hr/Candidates/Input', [
        'jobPostings' => JobPosting::query()->where('status', 'open')->orderBy('position_name')->get(['id', 'position_name']),
        'candidateSources' => CandidateSource::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
    ]));

    Route::prefix('hr/candidates')->group(function () {
        Route::post('input-to-job', [HrCandidateInputController::class, 'inputToJob']);
        Route::post('input-to-talent-pool', [HrCandidateInputController::class, 'inputToTalentPool']);
    });

    Route::prefix('hr/talent-pool')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Hr/TalentPool/Index', [
                'talentPools' => TalentPool::query()->with('candidate')->latest()->paginate(10),
                'candidateSources' => CandidateSource::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            ]);
        });
        Route::get('{talentPool}', fn (TalentPool $talentPool) => Inertia::render('Hr/TalentPool/Show', [
            'talentPool' => $talentPool->load(['candidate', 'sourceApplication.jobPosting']),
            'jobPostings' => JobPosting::query()->where('status', 'open')->orderBy('position_name')->get(['id', 'position_name']),
        ]));
        Route::post('/', [TalentPoolController::class, 'store']);
        Route::put('{talentPool}', [TalentPoolController::class, 'update']);
        Route::post('{talentPool}/assign-to-job', [TalentPoolController::class, 'assignToJob']);
    });

    Route::prefix('hr/email-intake')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Hr/EmailIntake/Index', [
                'emails' => EmailIntake::query()->latest()->paginate(10),
                'jobPostings' => JobPosting::query()->where('status', 'open')->orderBy('position_name')->get(['id', 'position_name']),
            ]);
        });
        Route::get('{emailIntake}', fn (EmailIntake $emailIntake) => Inertia::render('Hr/EmailIntake/Show', [
            'email' => $emailIntake,
            'jobPostings' => JobPosting::query()->where('status', 'open')->orderBy('position_name')->get(['id', 'position_name']),
        ]));
        Route::post('fetch', [EmailIntakeController::class, 'fetch']);
        Route::post('{emailIntake}/assign-to-job', [EmailIntakeController::class, 'assignToJob']);
        Route::post('{emailIntake}/move-to-talent-pool', [EmailIntakeController::class, 'moveToTalentPool']);
        Route::post('{emailIntake}/reject', [EmailIntakeController::class, 'reject']);
        Route::post('{emailIntake}/ignore', [EmailIntakeController::class, 'ignore']);
        Route::post('{emailIntake}/spam', [EmailIntakeController::class, 'spam']);
    });

    Route::prefix('hr/pipeline')->group(function () {
        Route::get('/', [PipelineController::class, 'index']);
        Route::get('{pipeline}', [PipelineController::class, 'show']);
        Route::post('{pipeline}/move', [PipelineController::class, 'move']);
        Route::post('{pipeline}/reject', [PipelineController::class, 'reject']);
        Route::post('{pipeline}/withdraw', [PipelineController::class, 'withdraw']);
    });
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
