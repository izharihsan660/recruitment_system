<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Application;
use App\Models\Employee;
use App\Services\ActiveEmployeeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(private readonly ActiveEmployeeService $activeEmployeeService) {}

    public function index(): Response
    {
        return Inertia::render('Employees/Index', ['employees' => Employee::query()->with(['department', 'entity'])->latest()->paginate(10)]);
    }

    public function show(Employee $employee): Response
    {
        return Inertia::render('Employees/Show', ['employee' => $employee->load(['candidate', 'department', 'entity', 'preboardingChecklist', 'probationRecord'])]);
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
