<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitHiringDecisionRequest;
use App\Models\Application;
use App\Services\HiringDecisionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class HiringDecisionController extends Controller
{
    public function __construct(private readonly HiringDecisionService $hiringDecisionService) {}

    public function show(Application $application): Response
    {
        return Inertia::render('Pipeline/HiringDecision', ['application' => $application->load(['candidate', 'jobPosting.department', 'mcuSimperRecord', 'hiringDecision'])]);
    }

    public function store(SubmitHiringDecisionRequest $request, Application $application): RedirectResponse
    {
        $this->hiringDecisionService->submit($application, $request->validated(), $request->user());

        return redirect('/pipeline')->with('success', 'Keputusan hiring berhasil disimpan.');
    }
}
