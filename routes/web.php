<?php

use App\Http\Controllers\Admin\ApprovalChainController;
use App\Http\Controllers\Admin\CandidateSourceController;
use App\Http\Controllers\Admin\CompanyProfileController;
use App\Http\Controllers\Admin\CompanySignerController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DocusealConfigController;
use App\Http\Controllers\Admin\EntityController;
use App\Http\Controllers\Admin\GraphApiConfigController;
use App\Http\Controllers\Admin\SmtpSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\BackgroundCheckController;
use App\Http\Controllers\CandidateAuthController;
use App\Http\Controllers\CandidateDocumentController;
use App\Http\Controllers\CandidatePortalController;
use App\Http\Controllers\DocuSealWebhookController;
use App\Http\Controllers\EmailIntakeController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FpkController;
use App\Http\Controllers\HiringDecisionController;
use App\Http\Controllers\HrCandidateInputController;
use App\Http\Controllers\HrInterviewController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\McuSimperController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfferingLetterController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PkwtController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PreboardingController;
use App\Http\Controllers\ProbationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PsychoTestController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\TalentPoolController;
use App\Http\Controllers\UserInterviewController;
use App\Http\Resources\ApplicationResource;
use App\Models\Application as CandidateApplication;
use App\Models\ApprovalChain;
use App\Models\CandidateSource;
use App\Models\CompanyProfile;
use App\Models\CompanySigner;
use App\Models\Department;
use App\Models\DocusealConfig;
use App\Models\EmailIntake;
use App\Models\Entity;
use App\Models\GraphApiConfig;
use App\Models\JobPosting;
use App\Models\ProbationRecord;
use App\Models\RecruitmentRequest;
use App\Models\SmtpSetting;
use App\Models\TalentPool;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

Route::post('/webhooks/docuseal', DocuSealWebhookController::class)->name('webhooks.docuseal');

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
            'probation_berjalan' => ProbationRecord::query()->whereIn('status', ['in_progress', 'day30_review', 'day60_review', 'day90_review', 'extended'])->count(),
            'probation_jatuh_tempo' => ProbationRecord::query()->whereIn('status', ['in_progress', 'day30_review', 'day60_review', 'day90_review', 'extended'])->where(function ($query) {
                $date = now()->addDays(7)->toDateString();
                $query->whereDate('day30_due', '<=', $date)->orWhereDate('day60_due', '<=', $date)->orWhereDate('day90_due', '<=', $date)->orWhereDate('extended_until', '<=', $date);
            })->count(),
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
        Route::post('/webhooks/docuseal', DocuSealWebhookController::class)->name('webhooks.docuseal');

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
        'approvedFpk' => RecruitmentRequest::query()->where('status', 'approved')->latest()->get(['id', 'position_name', 'entity_id', 'department_id', 'work_location', 'job_description', 'requirements', 'min_education', 'min_experience', 'required_skills']),
    ]))->name('job-postings.create');
    Route::get('/job-postings/{job_posting}/edit', fn (JobPosting $jobPosting) => Inertia::render('JobPostings/Form', [
        'mode' => 'edit',
        'jobPosting' => $jobPosting->load(['entity', 'department', 'recruitmentRequest']),
        'approvedFpk' => RecruitmentRequest::query()->where('status', 'approved')->latest()->get(['id', 'position_name', 'entity_id', 'department_id', 'work_location', 'job_description', 'requirements', 'min_education', 'min_experience', 'required_skills']),
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
            'applications' => CandidateApplication::query()
                ->with([
                    'candidate',
                    'jobPosting',
                    'screening',
                    'psychoTest',
                    'hrInterview',
                    'userInterview',
                    'backgroundCheck',
                    'offeringLetter',
                    'pkwtContract',
                    'pipelineLogs',
                ])
                ->whereNotIn('status', ['rejected', 'withdrawn'])
                ->latest()
                ->get()
                ->map(fn ($application) => (new ApplicationResource($application))->resolve())
                ->values()
                ->toArray(),
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
            'approvalChains' => ApprovalChain::query()->whereNotNull('approver_user_id')->with(['department.entity', 'approverUser.roles'])->withCount('approvalRecords')->orderBy('department_id')->latest('id')->get()->map(fn (ApprovalChain $chain): array => [
                'id' => $chain->id,
                'department_id' => $chain->department_id,
                'type' => $chain->type,
                'approver_user_id' => $chain->approver_user_id,
                'approver_role' => $chain->approver_role,
                'approver_user' => $chain->approverUser,
                'department' => $chain->department,
                'has_records' => $chain->approval_records_count > 0,
            ]),
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
        Route::get('users', fn () => Inertia::render('Admin/Users/Index', [
            'users' => User::query()->with(['department.entity', 'roles'])->latest()->paginate(10),
            'departments' => Department::query()->with('entity')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'entity_id']),
            'roles' => Role::query()->orderBy('name')->pluck('name')->values(),
        ]))->name('users.index');
        Route::get('smtp', fn () => Inertia::render('Admin/Configurations/Smtp', [
            'smtpSettings' => SmtpSetting::query()->latest()->get()->map(fn (SmtpSetting $setting): array => [
                'id' => $setting->id,
                'host' => $setting->host,
                'port' => $setting->port,
                'username' => $setting->username,
                'encryption' => $setting->encryption,
                'from_address' => $setting->from_address,
                'from_name' => $setting->from_name,
                'is_active' => $setting->is_active,
                'has_password' => filled($setting->password),
            ]),
        ]));
        Route::get('graph-api', fn () => Inertia::render('Admin/Configurations/GraphApi', [
            'graphApiConfigs' => GraphApiConfig::query()->latest()->get(),
        ]));
        Route::get('docuseal', fn () => Inertia::render('Admin/Configurations/Docuseal', [
            'docusealConfigs' => DocusealConfig::query()->latest()->get()->map(fn (DocusealConfig $config): array => [
                'id' => $config->id,
                'api_url' => $config->api_url,
                'offering_template_id' => $config->offering_template_id,
                'pkwt_template_id' => $config->pkwt_template_id,
                'is_active' => $config->is_active,
                'has_api_key' => filled($config->api_key),
                'has_webhook_secret' => filled($config->webhook_secret),
            ]),
        ]));
        Route::get('cms', fn () => Inertia::render('Admin/Configurations/Cms', [
            'companyProfile' => CompanyProfile::query()->first(),
        ]));

        Route::apiResource('users', UserController::class)->except(['index', 'show']);
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
        Route::post('docuseal-configs/{docuseal_config}/test-connection', [DocusealConfigController::class, 'testConnection'])
            ->name('docuseal-configs.test-connection');
        Route::apiResource('docuseal-configs', DocusealConfigController::class);
        Route::put('company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');
        Route::post('company-profile/hero-image', [CompanyProfileController::class, 'heroImage'])->name('company-profile.hero-image');
        Route::post('company-profile/gallery', [CompanyProfileController::class, 'gallery'])->name('company-profile.gallery');
        Route::delete('company-profile/gallery/{index}', [CompanyProfileController::class, 'deleteGallery'])->name('company-profile.gallery.delete');
    });

Route::middleware(['auth', 'active', 'role:'.Roles::Admin.'|'.Roles::HrRecruiter.'|'.Roles::HrManager])->group(function () {
    Route::get('/hr/candidates/input', fn () => Inertia::render('Hr/Candidates/Input', [
        'jobPostings' => JobPosting::query()->where('status', 'open')->orderBy('position_name')->get(['id', 'position_name']),
        'candidateSources' => CandidateSource::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
    ]));

    Route::prefix('hr/candidates')->group(function () {
        Route::post('input-to-job', [HrCandidateInputController::class, 'inputToJob']);
        Route::post('input-to-talent-pool', [HrCandidateInputController::class, 'inputToTalentPool']);
    });

    Route::prefix('hr/talent-pool')->group(function () {
        Route::post('/webhooks/docuseal', DocuSealWebhookController::class)->name('webhooks.docuseal');

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
        Route::post('/webhooks/docuseal', DocuSealWebhookController::class)->name('webhooks.docuseal');

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

    Route::prefix('hr/screening')->group(function () {
        Route::get('{application}', [ScreeningController::class, 'show'])->name('screening.show');
        Route::post('{application}', [ScreeningController::class, 'store'])->name('screening.store');
        Route::put('{application}', [ScreeningController::class, 'update'])->name('screening.update');
    });

    Route::prefix('hr/psycho-test')->group(function () {
        Route::get('{application}', [PsychoTestController::class, 'show'])->name('psycho-test.show');
        Route::post('{application}/schedule', [PsychoTestController::class, 'schedule'])->name('psycho-test.schedule');
        Route::post('{application}/result', [PsychoTestController::class, 'result'])->name('psycho-test.result');
    });

    Route::prefix('hr/background-check')->group(function () {
        Route::get('{application}', [BackgroundCheckController::class, 'show'])->name('background-check.show');
        Route::post('{application}', [BackgroundCheckController::class, 'store'])->name('background-check.store');
        Route::put('{application}', [BackgroundCheckController::class, 'update'])->name('background-check.update');
    });

    Route::prefix('hr/offering')->group(function () {
        Route::get('{application}', [OfferingLetterController::class, 'show'])->name('offering.show');
        Route::post('{application}', [OfferingLetterController::class, 'store'])->name('offering.store');
        Route::put('{application}', [OfferingLetterController::class, 'update'])->name('offering.update');
        Route::post('{application}/send', [OfferingLetterController::class, 'send'])->name('offering.send');
        Route::post('{application}/revise', [OfferingLetterController::class, 'revise'])->name('offering.revise');
        Route::get('{application}/preview', [OfferingLetterController::class, 'preview'])->name('offering.preview');
    });

    Route::prefix('hr/pkwt')->group(function () {
        Route::get('{application}', [PkwtController::class, 'show'])->name('pkwt.show');
        Route::post('{application}', [PkwtController::class, 'store'])->name('pkwt.store');
        Route::put('{application}', [PkwtController::class, 'update'])->name('pkwt.update');
        Route::post('{application}/send', [PkwtController::class, 'send'])->name('pkwt.send');
        Route::get('{application}/preview', [PkwtController::class, 'preview'])->name('pkwt.preview');
    });

    Route::prefix('hr/mcu-simper')->group(function () {
        Route::get('{application}', [McuSimperController::class, 'show'])->name('mcu-simper.show');
        Route::post('{application}', [McuSimperController::class, 'store'])->name('mcu-simper.store');
        Route::post('{application}/schedule-mcu', [McuSimperController::class, 'scheduleMcu'])->name('mcu-simper.schedule-mcu');
        Route::post('{application}/schedule-simper', [McuSimperController::class, 'scheduleSimper'])->name('mcu-simper.schedule-simper');
        Route::post('{application}/result-mcu', [McuSimperController::class, 'resultMcu'])->name('mcu-simper.result-mcu');
        Route::post('{application}/result-simper', [McuSimperController::class, 'resultSimper'])->name('mcu-simper.result-simper');
        Route::post('{application}/proceed', [McuSimperController::class, 'proceed'])->name('mcu-simper.proceed');
    });

    Route::prefix('hr/hiring-decision')->group(function () {
        Route::get('{application}', [HiringDecisionController::class, 'show'])->name('hiring-decision.show');
        Route::post('{application}', [HiringDecisionController::class, 'store'])->name('hiring-decision.store');
    });

    Route::prefix('hr/employees')->group(function () {
        Route::get('', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::get('{application}/activate', [EmployeeController::class, 'activate'])->name('employees.activate');
        Route::post('{application}/activate', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    });

    Route::prefix('hr/preboarding')->group(function () {
        Route::get('', [PreboardingController::class, 'index'])->name('preboarding.index');
        Route::get('{employee}', [PreboardingController::class, 'show'])->name('preboarding.show');
        Route::post('{checklist}/items', [PreboardingController::class, 'storeItem'])->name('preboarding.items.store');
        Route::delete('items/{item}', [PreboardingController::class, 'destroyItem'])->name('preboarding.items.destroy');
        Route::post('items/{item}/assign', [PreboardingController::class, 'assign'])->name('preboarding.items.assign');
        Route::post('items/{item}/complete', [PreboardingController::class, 'complete'])->name('preboarding.items.complete');
    });

    Route::prefix('hr/probation')->group(function () {
        Route::get('', [ProbationController::class, 'index'])->name('probation.index');
        Route::get('{employee}', [ProbationController::class, 'show'])->name('probation.show');
        Route::post('{probation}/evaluate', [ProbationController::class, 'evaluate'])->name('probation.evaluate');
        Route::post('{probation}/outcome', [ProbationController::class, 'outcome'])->name('probation.outcome');
    });

    Route::prefix('hr/interview-hr')->group(function () {
        Route::get('{application}', [HrInterviewController::class, 'show'])->name('interview-hr.show');
        Route::post('{application}/schedule', [HrInterviewController::class, 'schedule'])->name('interview-hr.schedule');
        Route::post('{application}/scorecard', [HrInterviewController::class, 'scorecard'])->name('interview-hr.scorecard');
    });

});

Route::middleware(['auth', 'active', 'role:'.Roles::Admin.'|'.Roles::HrRecruiter.'|'.Roles::HrManager.'|'.Roles::HiringManager])
    ->prefix('hr/interview-user')
    ->group(function () {
        Route::get('{application}', [UserInterviewController::class, 'show'])->name('interview-user.show');
        Route::post('{application}/schedule', [UserInterviewController::class, 'schedule'])->name('interview-user.schedule');
        Route::put('{application}/reschedule', [UserInterviewController::class, 'reschedule'])->name('interview-user.reschedule');
        Route::post('{application}/scorecard', [UserInterviewController::class, 'scorecard'])->name('interview-user.scorecard');
    });

Route::prefix('candidate')->name('candidate.')->group(function () {
    Route::get('register', [CandidateAuthController::class, 'showRegister'])->middleware('guest:candidate')->name('register.form');
    Route::post('register', [CandidateAuthController::class, 'register'])->name('register');
    Route::get('login', [CandidateAuthController::class, 'showLogin'])->middleware('guest:candidate')->name('login.form');
    Route::post('login', [CandidateAuthController::class, 'login'])->name('login');
    Route::get('forgot-password', [CandidateAuthController::class, 'showForgotPassword'])->middleware('guest:candidate')->name('password.request');
    Route::post('forgot-password', [CandidateAuthController::class, 'sendResetLink'])->middleware('guest:candidate')->name('password.email');
    Route::get('reset-password/{token}', [CandidateAuthController::class, 'showResetPassword'])->middleware('guest:candidate')->name('password.reset');
    Route::post('reset-password', [CandidateAuthController::class, 'resetPassword'])->middleware('guest:candidate')->name('password.update');

    Route::middleware('auth:candidate')->group(function () {
        Route::post('logout', [CandidateAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [CandidateAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('profile', [CandidateAuthController::class, 'profile'])->name('profile');
        Route::put('profile', [CandidateAuthController::class, 'updateProfile'])->name('profile.update');
        Route::post('cv', [CandidateAuthController::class, 'uploadCv'])->name('cv.store');
        Route::get('jobs/{job_posting}/apply', [CandidatePortalController::class, 'applyForm'])->name('jobs.apply.form');
        Route::post('jobs/{job_posting}/apply', [CandidatePortalController::class, 'apply'])->name('jobs.apply');
        Route::post('jobs/{job_posting}/withdraw', [CandidatePortalController::class, 'withdraw'])->name('jobs.withdraw');
        Route::get('applications', [CandidatePortalController::class, 'applications'])->name('applications.index');
        Route::get('applications/{application}', [CandidatePortalController::class, 'application'])->name('applications.show');
        Route::post('applications/{application}/documents', [CandidateDocumentController::class, 'store'])->name('applications.documents.store');
        Route::delete('applications/{application}/documents/{docId}', [CandidateDocumentController::class, 'destroy'])->name('applications.documents.destroy');
    });
});

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [PortalController::class, 'home'])->name('home');
    Route::get('jobs', [PortalController::class, 'jobs'])->name('jobs.index');
    Route::get('jobs/{job_posting}', [PortalController::class, 'job'])->name('jobs.show');
});

require __DIR__.'/auth.php';
