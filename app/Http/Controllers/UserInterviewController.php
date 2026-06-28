<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleUserInterviewRequest;
use App\Http\Requests\SubmitUserInterviewScorecardRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\UserInterviewResource;
use App\Http\Resources\UserResource;
use App\Models\Application;
use App\Models\User;
use App\Services\UserInterviewService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserInterviewController extends Controller
{
    public function __construct(private readonly UserInterviewService $userInterviewService) {}

    public function show(Application $application): Response
    {
        $application->load(['candidate', 'jobPosting', 'userInterview.interviewer']);

        return Inertia::render('Pipeline/InterviewUser', [
            'application' => (new ApplicationResource($application))->resolve(),
            'interview' => $application->userInterview ? (new UserInterviewResource($application->userInterview))->resolve() : null,
            'interviewers' => UserResource::collection(User::role(Roles::HiringManager)->where('is_active', true)->orderBy('name')->get())->resolve(),
            'canSchedule' => request()->user()->hasAnyRole([Roles::HrRecruiter, Roles::HrManager]),
            'canScorecard' => $application->userInterview && (int) $application->userInterview->interviewer_id === (int) request()->user()->id,
        ]);
    }

    public function schedule(ScheduleUserInterviewRequest $request, Application $application): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole([Roles::HrRecruiter, Roles::HrManager]), 403);
        $this->userInterviewService->schedule($application, $request->validated(), $request->user());

        return back()->with('success', 'Jadwal interview user berhasil disimpan.');
    }

    public function reschedule(ScheduleUserInterviewRequest $request, Application $application): RedirectResponse
    {
        return $this->schedule($request, $application);
    }

    public function scorecard(SubmitUserInterviewScorecardRequest $request, Application $application): RedirectResponse
    {
        $interview = $application->userInterview()->firstOrFail();
        $this->userInterviewService->submitScorecard($interview, $request->validated(), $request->user());

        return redirect()->route('pipeline.index')->with('success', 'Scorecard interview user berhasil disimpan.');
    }
}
