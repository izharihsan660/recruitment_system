<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitProbationEvaluationRequest;
use App\Http\Requests\SubmitProbationOutcomeRequest;
use App\Models\Employee;
use App\Models\ProbationRecord;
use App\Services\ProbationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProbationController extends Controller
{
    public function __construct(private readonly ProbationService $probationService) {}

    public function index(): Response
    {
        return Inertia::render('Probation/Index', ['employees' => Employee::query()->with(['department', 'probationRecord'])->where('status', 'active')->latest()->paginate(10)]);
    }

    public function show(Employee $employee): Response
    {
        return Inertia::render('Probation/Show', ['employee' => $employee->load(['department']), 'probation' => $employee->probationRecord?->load('evaluations') ?? $this->probationService->create($employee)->load('evaluations')]);
    }

    public function evaluate(SubmitProbationEvaluationRequest $request, ProbationRecord $probation): RedirectResponse
    {
        $this->probationService->submitEvaluation($probation, $request->validated(), $request->user());

        return back()->with('success', 'Evaluasi probation berhasil disimpan.');
    }

    public function outcome(SubmitProbationOutcomeRequest $request, ProbationRecord $probation): RedirectResponse
    {
        $this->probationService->submitOutcome($probation, $request->validated('outcome'), $request->validated('extended_until'));

        return back()->with('success', 'Outcome probation berhasil disimpan.');
    }
}
