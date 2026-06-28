<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Application;
use App\Models\Employee;
use App\Models\User;
use App\Services\ActiveEmployeeService;
use App\Services\PreboardingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private readonly ActiveEmployeeService $activeEmployeeService) {}

    public function index(): Response
    {
        $employees = Employee::query()
            ->with(['department', 'entity', 'preboardingChecklist.items', 'probationRecord'])
            ->where('status', 'active')
            ->latest()
            ->paginate(10);

        $employees->getCollection()->transform(function (Employee $employee): Employee {
            $items = $employee->preboardingChecklist?->items ?? collect();
            $employee->preboarding_progress = [
                'done' => $items->where('status', 'done')->count(),
                'total' => $items->count(),
            ];

            return $employee;
        });

        return Inertia::render('Employees/Index', ['employees' => $employees]);
    }

    public function show(Employee $employee): Response
    {
        $employee->load([
            'department',
            'entity',
            'preboardingChecklist.items.pic',
            'probationRecord.evaluations',
            'application.candidate',
        ]);

        $checklist = $employee->preboardingChecklist
            ?? app(PreboardingService::class)->createFromTemplate($employee)->load('items.pic');

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
            'checklist' => $checklist,
            'users' => User::query()->where('is_active', true)->get(['id', 'name']),
            'probation' => $employee->probationRecord?->load('evaluations'),
        ]);
    }

    public function activate(Application $application): Response
    {
        return Inertia::render('Pipeline/Activate', ['application' => $application->load(['candidate', 'jobPosting.department', 'jobPosting.entity', 'pkwtContract'])]);
    }

    public function store(ActivateEmployeeRequest $request, Application $application): RedirectResponse
    {
        $employee = $this->activeEmployeeService->activate($application, $request->validated(), $request->user());

        return redirect('/hr/employees/'.$employee->id)->with('success', 'Karyawan berhasil diaktifkan.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return back()->with('success', 'Data karyawan berhasil diperbarui.');
    }
}
