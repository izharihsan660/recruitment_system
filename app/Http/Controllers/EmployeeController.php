<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Application;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\User;
use App\Services\ActiveEmployeeService;
use App\Services\PreboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private readonly ActiveEmployeeService $activeEmployeeService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'department_id', 'entity_id', 'status']);

        $employees = Employee::query()
            ->with(['department', 'entity', 'preboardingChecklist.items', 'probationRecord'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->when($filters['department_id'] ?? null, fn ($query, string $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['entity_id'] ?? null, fn ($query, string $entityId) => $query->where('entity_id', $entityId))
            ->when(($filters['status'] ?? 'active') !== 'all', fn ($query) => $query->where('status', $filters['status'] ?? 'active'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $employees->getCollection()->transform(function (Employee $employee): Employee {
            $items = $employee->preboardingChecklist?->items ?? collect();
            $employee->preboarding_progress = [
                'done' => $items->where('status', 'done')->count(),
                'total' => $items->count(),
            ];

            return $employee;
        });

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'department_id' => $filters['department_id'] ?? '',
                'entity_id' => $filters['entity_id'] ?? '',
                'status' => $filters['status'] ?? 'active',
            ],
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'entities' => Entity::query()->orderBy('name')->get(['id', 'name']),
        ]);
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
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
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

        return redirect('/hr/employees/'.$employee->id)->with('success', 'Data karyawan berhasil diperbarui.');
    }
}
