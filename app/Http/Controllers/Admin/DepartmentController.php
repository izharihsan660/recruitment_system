<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departmentService) {}

    public function index(): AnonymousResourceCollection
    {
        return DepartmentResource::collection(Department::query()->with('entity')->latest()->paginate());
    }

    public function store(StoreDepartmentRequest $request): DepartmentResource
    {
        return new DepartmentResource($this->departmentService->create($request->validated())->load('entity'));
    }

    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource($department->load('entity'));
    }

    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        return new DepartmentResource($this->departmentService->update($department, $request->validated())->load('entity'));
    }

    public function destroy(Department $department): Response
    {
        $this->departmentService->delete($department);

        return response()->noContent();
    }
}
