<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanySignerRequest;
use App\Http\Requests\Admin\UpdateCompanySignerRequest;
use App\Http\Resources\CompanySignerResource;
use App\Models\CompanySigner;
use App\Services\CompanySignerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanySignerController extends Controller
{
    public function __construct(private readonly CompanySignerService $companySignerService) {}

    public function index(): AnonymousResourceCollection
    {
        return CompanySignerResource::collection(CompanySigner::query()->with(['entity', 'user.roles'])->latest()->paginate());
    }

    public function store(StoreCompanySignerRequest $request): RedirectResponse
    {
        $this->companySignerService->create($request->validated());

        return redirect()->back()->with('success', 'Penanda tangan perusahaan berhasil dibuat.');
    }

    public function show(CompanySigner $companySigner): CompanySignerResource
    {
        return new CompanySignerResource($companySigner->load(['entity', 'user.roles']));
    }

    public function update(UpdateCompanySignerRequest $request, CompanySigner $companySigner): RedirectResponse
    {
        $this->companySignerService->update($companySigner, $request->validated());

        return redirect()->back()->with('success', 'Penanda tangan perusahaan berhasil diperbarui.');
    }

    public function destroy(CompanySigner $companySigner): RedirectResponse
    {
        $this->companySignerService->delete($companySigner);

        return redirect()->back()->with('success', 'Penanda tangan perusahaan berhasil dihapus.');
    }
}
