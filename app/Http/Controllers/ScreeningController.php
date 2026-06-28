<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScreeningRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\ScreeningResource;
use App\Models\Application;
use App\Services\ScreeningService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScreeningController extends Controller
{
    public function __construct(private readonly ScreeningService $screeningService) {}

    public function show(Application $application): Response
    {
        $application->load(['candidate', 'jobPosting', 'screening.reviewer']);

        return Inertia::render('Pipeline/Screening', [
            'application' => (new ApplicationResource($application))->resolve(),
            'screening' => $application->screening ? new ScreeningResource($application->screening) : null,
        ]);
    }

    public function store(StoreScreeningRequest $request, Application $application): RedirectResponse
    {
        $this->screeningService->submit($application, $request->validated(), $request->user());

        return redirect()->route('pipeline.index')->with('success', 'Screening berhasil disimpan.');
    }

    public function update(StoreScreeningRequest $request, Application $application): RedirectResponse
    {
        abort_unless($application->screening?->decision === 'pending_info', 422, 'Screening hanya bisa diupdate saat pending info.');

        $this->screeningService->submit($application, $request->validated(), $request->user());

        return redirect()->route('pipeline.index')->with('success', 'Screening berhasil diperbarui.');
    }
}
