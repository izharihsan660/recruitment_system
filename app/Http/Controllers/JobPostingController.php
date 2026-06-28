<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJobPostingRequest;
use App\Http\Requests\UpdateJobPostingRequest;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use App\Models\RecruitmentRequest;
use App\Services\JobPostingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class JobPostingController extends Controller
{
    public function __construct(private readonly JobPostingService $jobPostingService) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', JobPosting::class);

        return JobPostingResource::collection(JobPosting::query()->with(['entity', 'department', 'recruitmentRequest'])->latest()->paginate());
    }

    public function store(StoreJobPostingRequest $request): RedirectResponse
    {
        $fpk = RecruitmentRequest::query()->findOrFail($request->integer('recruitment_request_id'));
        $this->jobPostingService->createFromFpk($fpk, $request->validated(), $request->user());

        return redirect()->route('job-postings.index')
            ->with('success', 'Job Posting berhasil dibuat.');
    }

    public function show(JobPosting $jobPosting): JobPostingResource
    {
        Gate::authorize('view', $jobPosting);

        return new JobPostingResource($jobPosting->load(['entity', 'department', 'recruitmentRequest']));
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting): RedirectResponse
    {
        $this->jobPostingService->update($jobPosting, $request->validated());

        return redirect()->back()->with('success', 'Job Posting berhasil diperbarui.');
    }

    public function open(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);
        $this->jobPostingService->open($jobPosting, request()->user());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function close(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);
        $this->jobPostingService->close($jobPosting, request()->user());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function cancel(JobPosting $jobPosting): RedirectResponse
    {
        Gate::authorize('update', $jobPosting);
        $this->jobPostingService->cancel($jobPosting, request()->user());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }
}
