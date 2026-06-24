<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanySignerRequest;
use App\Http\Requests\Admin\UpdateCompanySignerRequest;
use App\Http\Resources\CompanySignerResource;
use App\Models\CompanySigner;
use App\Services\CompanySignerService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CompanySignerController extends Controller
{
    public function __construct(private readonly CompanySignerService $companySignerService) {}

    public function index(): AnonymousResourceCollection
    {
        return CompanySignerResource::collection(CompanySigner::query()->with(['entity', 'user.roles'])->latest()->paginate());
    }

    public function store(StoreCompanySignerRequest $request): CompanySignerResource
    {
        return new CompanySignerResource($this->companySignerService->create($request->validated())->load(['entity', 'user.roles']));
    }

    public function show(CompanySigner $companySigner): CompanySignerResource
    {
        return new CompanySignerResource($companySigner->load(['entity', 'user.roles']));
    }

    public function update(UpdateCompanySignerRequest $request, CompanySigner $companySigner): CompanySignerResource
    {
        return new CompanySignerResource($this->companySignerService->update($companySigner, $request->validated())->load(['entity', 'user.roles']));
    }

    public function destroy(CompanySigner $companySigner): Response
    {
        $this->companySignerService->delete($companySigner);

        return response()->noContent();
    }
}
