<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleHrInterviewRequest;
use App\Http\Requests\SubmitHrInterviewScorecardRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\HrInterviewResource;
use App\Http\Resources\UserResource;
use App\Models\Application;
use App\Models\User;
use App\Services\HrInterviewService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HrInterviewController extends Controller
{
    public function __construct(private readonly HrInterviewService $hrInterviewService) {}

    public function show(Application $application): Response
    {
        $application->load(['candidate', 'jobPosting', 'hrInterview.interviewer']);

        return Inertia::render('Pipeline/InterviewHR', [
            'application' => (new ApplicationResource($application))->resolve(),
            'interview' => $application->hrInterview ? (new HrInterviewResource($application->hrInterview))->resolve() : null,
            'interviewers' => UserResource::collection(User::role([Roles::HrRecruiter, Roles::HrManager])->where('is_active', true)->orderBy('name')->get())->resolve(),
        ]);
    }

    public function schedule(ScheduleHrInterviewRequest $request, Application $application): RedirectResponse
    {
        $this->hrInterviewService->schedule($application, $request->validated(), $request->user());

        return back()->with('success', 'Jadwal interview HR berhasil disimpan.');
    }

    public function scorecard(SubmitHrInterviewScorecardRequest $request, Application $application): RedirectResponse
    {
        $interview = $application->hrInterview()->firstOrFail();
        $this->hrInterviewService->submitScorecard($interview, $request->validated(), $request->user());

        return redirect()->route('pipeline.index')->with('success', 'Scorecard interview HR berhasil disimpan.');
    }
}
