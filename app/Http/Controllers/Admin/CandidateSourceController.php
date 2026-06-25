<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCandidateSourceRequest;
use App\Http\Requests\Admin\UpdateCandidateSourceRequest;
use App\Http\Resources\CandidateSourceResource;
use App\Models\CandidateSource;
use App\Services\CandidateSourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CandidateSourceController extends Controller
{
    public function __construct(private readonly CandidateSourceService $candidateSourceService) {}

    public function index(): AnonymousResourceCollection
    {
        return CandidateSourceResource::collection(CandidateSource::query()->latest()->paginate());
    }

    public function store(StoreCandidateSourceRequest $request): RedirectResponse
    {
        $this->candidateSourceService->create($request->validated());

        return redirect()->back()->with('success', 'Sumber kandidat berhasil dibuat.');
    }

    public function show(CandidateSource $candidateSource): CandidateSourceResource
    {
        return new CandidateSourceResource($candidateSource);
    }

    public function update(UpdateCandidateSourceRequest $request, CandidateSource $candidateSource): RedirectResponse
    {
        $this->candidateSourceService->update($candidateSource, $request->validated());

        return redirect()->back()->with('success', 'Sumber kandidat berhasil diperbarui.');
    }

    public function destroy(CandidateSource $candidateSource): RedirectResponse
    {
        $this->candidateSourceService->delete($candidateSource);

        return redirect()->back()->with('success', 'Sumber kandidat berhasil dihapus.');
    }
}
