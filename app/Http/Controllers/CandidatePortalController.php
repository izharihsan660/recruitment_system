<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplyJobRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\JobPosting;
use App\Services\ApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CandidatePortalController extends Controller
{
    public function __construct(private readonly ApplicationService $applicationService) {}

    public function applyForm(JobPosting $jobPosting): View
    {
        abort_unless($jobPosting->status === 'open', 404);

        $candidate = request()->user('candidate');
        $existingApplication = $jobPosting->applications()->whereBelongsTo($candidate)->first();

        return view('candidate.apply', [
            'job' => $jobPosting->load(['entity', 'department']),
            'hasCv' => $candidate->hasCv(),
            'existingApplicationId' => $existingApplication?->id,
        ]);
    }

    public function apply(ApplyJobRequest $request, JobPosting $jobPosting): RedirectResponse|JsonResponse
    {
        $application = $this->applicationService->apply($jobPosting, $request->user('candidate'), $request->boolean('consent'));

        if ($request->expectsJson()) {
            return response()->json([
                'data' => ApplicationResource::make($application->load('jobPosting'))->resolve(),
            ], 201);
        }

        return redirect()->route('candidate.applications.show', $application)->with('success', 'Lamaran berhasil dikirim.');
    }

    public function withdraw(JobPosting $jobPosting): RedirectResponse
    {
        $application = Application::query()
            ->whereBelongsTo(request()->user('candidate'))
            ->whereBelongsTo($jobPosting)
            ->firstOrFail();

        $this->applicationService->withdraw($application, request()->user('candidate'));

        return back()->with('success', 'Lamaran berhasil dibatalkan.');
    }

    public function applications(): View
    {
        $status = request('status');

        $applications = request()->user('candidate')->applications()
            ->with(['jobPosting.entity', 'jobPosting.department'])
            ->when($status, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('candidate.applications.index', [
            'applications' => $applications,
            'filters' => ['status' => $status],
        ]);
    }

    public function application(Application $application): View
    {
        abort_unless($application->candidate_id === request()->user('candidate')->id, 404);

        return view('candidate.applications.show', [
            'application' => $application->load(['jobPosting.entity', 'jobPosting.department', 'documents', 'pipelineLogs.actor']),
        ]);
    }
}
