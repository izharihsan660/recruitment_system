<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTalentPoolToJobRequest;
use App\Http\Requests\StoreTalentPoolRequest;
use App\Http\Requests\UpdateTalentPoolRequest;
use App\Http\Resources\TalentPoolResource;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\TalentPool;
use App\Services\TalentPoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TalentPoolController extends Controller
{
    public function __construct(private readonly TalentPoolService $talentPoolService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TalentPool::query()->with('candidate')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('tags')) {
            $query->whereJsonContains('tags', $request->string('tags')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->whereHas('candidate', fn ($candidateQuery) => $candidateQuery
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        return TalentPoolResource::collection($query->paginate());
    }

    public function show(TalentPool $talentPool): TalentPoolResource
    {
        return new TalentPoolResource($talentPool->load(['candidate', 'sourceApplication']));
    }

    public function store(StoreTalentPoolRequest $request): RedirectResponse
    {
        $candidate = Candidate::query()->findOrFail($request->integer('candidate_id'));

        $this->talentPoolService->addManual($candidate, $request->validated(), $request->user());

        return redirect()->back()->with('success', 'Talent Pool berhasil dibuat.');
    }

    public function update(UpdateTalentPoolRequest $request, TalentPool $talentPool): RedirectResponse
    {
        $talentPool->update($request->validated());

        return redirect()->back()->with('success', 'Talent Pool berhasil diperbarui.');
    }

    public function assignToJob(AssignTalentPoolToJobRequest $request, TalentPool $talentPool): RedirectResponse
    {
        $job = JobPosting::query()->findOrFail($request->integer('job_posting_id'));

        $this->talentPoolService->assignToJob($talentPool, $job, $request->user());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }
}
