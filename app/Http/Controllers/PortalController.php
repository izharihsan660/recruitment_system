<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobPostingResource;
use App\Models\Department;
use App\Models\JobPosting;
use App\Services\CompanyProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function home(CompanyProfileService $companyProfileService): View
    {
        return view('portal.home', [
            'companyProfile' => $companyProfileService->current(),
            'jobs' => JobPosting::query()
                ->with(['entity', 'department'])
                ->where('status', 'open')
                ->latest('opened_at')
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }

    public function jobs(Request $request, CompanyProfileService $companyProfileService): View|AnonymousResourceCollection
    {
        if ($request->expectsJson()) {
            return JobPostingResource::collection(JobPosting::query()->with(['entity', 'department'])->where('status', 'open')->latest()->paginate());
        }

        $filters = $request->only(['search', 'department_id', 'employment_status', 'work_location']);

        $jobs = JobPosting::query()
            ->with(['entity', 'department'])
            ->where('status', 'open')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('position_name', 'like', "%{$search}%")
                        ->orWhere('work_location', 'like', "%{$search}%");
                });
            })
            ->when($filters['department_id'] ?? null, fn ($query, string $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['employment_status'] ?? null, fn ($query, string $status) => $query->where('employment_status', $status))
            ->when($filters['work_location'] ?? null, fn ($query, string $location) => $query->where('work_location', $location))
            ->latest('opened_at')
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('portal.jobs.index', [
            'companyProfile' => $companyProfileService->current(),
            'jobs' => $jobs,
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employmentStatuses' => JobPosting::query()->whereNotNull('employment_status')->distinct()->orderBy('employment_status')->pluck('employment_status'),
            'locations' => JobPosting::query()->where('status', 'open')->whereNotNull('work_location')->distinct()->orderBy('work_location')->pluck('work_location'),
            'filters' => $filters,
        ]);
    }

    public function job(JobPosting $jobPosting, CompanyProfileService $companyProfileService): View|JobPostingResource
    {
        abort_unless($jobPosting->status === 'open', 404);

        if (request()->expectsJson()) {
            return JobPostingResource::make($jobPosting->load(['entity', 'department']));
        }

        return view('portal.jobs.show', [
            'companyProfile' => $companyProfileService->current(),
            'job' => $jobPosting->load(['entity', 'department']),
        ]);
    }
}
