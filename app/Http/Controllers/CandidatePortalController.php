<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyJobRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\JobPostingResource;
use App\Models\Application;
use App\Models\JobPosting;
use App\Services\ApplicationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CandidatePortalController extends Controller
{
    public function __construct(private readonly ApplicationService $applicationService) {}

    public function jobs(): AnonymousResourceCollection
    {
        return JobPostingResource::collection(JobPosting::query()->with(['entity', 'department'])->where('status', 'open')->latest()->paginate());
    }

    public function job(JobPosting $jobPosting): JobPostingResource
    {
        abort_unless($jobPosting->status === 'open', 404);

        return new JobPostingResource($jobPosting->load(['entity', 'department']));
    }

    public function apply(ApplyJobRequest $request, JobPosting $jobPosting): ApplicationResource
    {
        $application = $this->applicationService->apply($jobPosting, $request->user('candidate'), $request->boolean('consent'));

        return new ApplicationResource($application->load('jobPosting'));
    }

    public function withdraw(JobPosting $jobPosting): Response
    {
        $application = Application::query()
            ->whereBelongsTo(request()->user('candidate'))
            ->whereBelongsTo($jobPosting)
            ->firstOrFail();

        $this->applicationService->withdraw($application, request()->user('candidate'));

        return response()->noContent();
    }

    public function applications(): AnonymousResourceCollection
    {
        return ApplicationResource::collection(request()->user('candidate')->applications()->with('jobPosting')->latest()->paginate());
    }

    public function application(Application $application): ApplicationResource
    {
        abort_unless($application->candidate_id === request()->user('candidate')->id, 404);

        return new ApplicationResource($application->load(['jobPosting', 'documents']));
    }
}
