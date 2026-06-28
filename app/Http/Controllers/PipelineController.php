<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectPipelineRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Services\PipelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PipelineController extends Controller
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Application::query()->with(['candidate', 'jobPosting', 'screening', 'psychoTest', 'hrInterview', 'userInterview', 'backgroundCheck', 'offeringLetter', 'pkwtContract'])->latest();

        if ($request->filled('job_posting_id')) {
            $query->where('job_posting_id', $request->integer('job_posting_id'));
        }

        if ($request->filled('source_id')) {
            $query->where('source_id', $request->integer('source_id'));
        }

        if ($request->filled('stage')) {
            $query->where('status', $request->string('stage')->toString());
        }

        if ($request->filled('department_id')) {
            $query->whereHas('jobPosting', fn ($jobPostingQuery) => $jobPostingQuery->where('department_id', $request->integer('department_id')));
        }

        if ($request->filled('recruiter_id')) {
            $query->where('input_by', $request->integer('recruiter_id'));
        }

        return ApplicationResource::collection($query->paginate());
    }

    public function show(Application $pipeline): array
    {
        return (new ApplicationResource($pipeline->load(['candidate', 'jobPosting', 'pipelineLogs', 'screening', 'psychoTest', 'hrInterview', 'userInterview', 'backgroundCheck', 'offeringLetter', 'pkwtContract'])))->resolve();
    }

    public function move(Application $pipeline): RedirectResponse
    {
        $this->pipelineService->moveToNextStage($pipeline, request()->user());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function reject(RejectPipelineRequest $request, Application $pipeline): RedirectResponse
    {
        $this->pipelineService->reject($pipeline, $request->user(), $request->string('reason')->toString(), $request->boolean('skip_talent_pool'));

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function withdraw(Application $pipeline): RedirectResponse
    {
        $this->pipelineService->withdraw($pipeline);

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }
}
