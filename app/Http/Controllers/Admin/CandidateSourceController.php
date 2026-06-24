<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCandidateSourceRequest;
use App\Http\Requests\Admin\UpdateCandidateSourceRequest;
use App\Http\Resources\CandidateSourceResource;
use App\Models\CandidateSource;
use App\Services\CandidateSourceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CandidateSourceController extends Controller
{
    public function __construct(private readonly CandidateSourceService $candidateSourceService) {}

    public function index(): AnonymousResourceCollection
    {
        return CandidateSourceResource::collection(CandidateSource::query()->latest()->paginate());
    }

    public function store(StoreCandidateSourceRequest $request): CandidateSourceResource
    {
        return new CandidateSourceResource($this->candidateSourceService->create($request->validated()));
    }

    public function show(CandidateSource $candidateSource): CandidateSourceResource
    {
        return new CandidateSourceResource($candidateSource);
    }

    public function update(UpdateCandidateSourceRequest $request, CandidateSource $candidateSource): CandidateSourceResource
    {
        return new CandidateSourceResource($this->candidateSourceService->update($candidateSource, $request->validated()));
    }

    public function destroy(CandidateSource $candidateSource): Response
    {
        $this->candidateSourceService->delete($candidateSource);

        return response()->noContent();
    }
}
