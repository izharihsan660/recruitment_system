<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchedulePsychoTestRequest;
use App\Http\Requests\SubmitPsychoTestResultRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\PsychoTestResource;
use App\Models\Application;
use App\Services\PsychoTestService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PsychoTestController extends Controller
{
    public function __construct(private readonly PsychoTestService $psychoTestService) {}

    public function show(Application $application): Response
    {
        $application->load(['candidate', 'jobPosting', 'psychoTest.conductor']);

        return Inertia::render('Pipeline/PsychoTest', [
            'application' => new ApplicationResource($application),
            'psychoTest' => $application->psychoTest ? new PsychoTestResource($application->psychoTest) : null,
            'notRequired' => $application->jobPosting?->test_required === false,
        ]);
    }

    public function schedule(SchedulePsychoTestRequest $request, Application $application): RedirectResponse
    {
        $this->psychoTestService->schedule($application, $request->validated(), $request->user());

        return back()->with('success', 'Jadwal psikotes berhasil disimpan.');
    }

    public function result(SubmitPsychoTestResultRequest $request, Application $application): RedirectResponse
    {
        $test = $application->psychoTest()->firstOrFail();
        $this->psychoTestService->submitResult($test, $request->validated(), $request->user());

        return redirect()->route('pipeline.index')->with('success', 'Hasil psikotes berhasil disimpan.');
    }
}
