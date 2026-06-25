<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departmentService) {}

    public function index(): AnonymousResourceCollection
    {
        return DepartmentResource::collection(Department::query()->with('entity')->latest()->paginate());
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->departmentService->create($request->validated());

        return redirect()->back()->with('success', 'Departemen berhasil dibuat.');
    }

    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource($department->load('entity'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departmentService->update($department, $request->validated());

        return redirect()->back()->with('success', 'Departemen berhasil diperbarui.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->departmentService->delete($department);

        return redirect()->back()->with('success', 'Departemen berhasil dihapus.');
    }
}
