<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitBackgroundCheckRequest;
use App\Models\Application;
use App\Services\BackgroundCheckService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BackgroundCheckController extends Controller
{
    public function __construct(private readonly BackgroundCheckService $backgroundCheckService) {}

    public function show(Application $application): Response
    {
        return Inertia::render('Pipeline/BackgroundCheck', [
            'application' => $application->load(['candidate', 'jobPosting.department', 'backgroundCheck']),
        ]);
    }

    public function store(SubmitBackgroundCheckRequest $request, Application $application): RedirectResponse
    {
        $this->backgroundCheckService->submit($application, $request->validated(), $request->user());

        return back()->with('success', 'Background check berhasil disimpan.');
    }

    public function update(SubmitBackgroundCheckRequest $request, Application $application): RedirectResponse
    {
        $this->backgroundCheckService->submit($application, $request->validated(), $request->user());

        return back()->with('success', 'Background check berhasil diperbarui.');
    }
}
