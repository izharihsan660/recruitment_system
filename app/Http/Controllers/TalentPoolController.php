<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTalentPoolToJobRequest;
use App\Http\Requests\StoreTalentPoolRequest;
use App\Http\Requests\UpdateTalentPoolRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\TalentPoolResource;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\TalentPool;
use App\Services\TalentPoolService;
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

    public function store(StoreTalentPoolRequest $request): TalentPoolResource
    {
        $candidate = Candidate::query()->findOrFail($request->integer('candidate_id'));

        return new TalentPoolResource($this->talentPoolService->addManual($candidate, $request->validated(), $request->user()));
    }

    public function update(UpdateTalentPoolRequest $request, TalentPool $talentPool): TalentPoolResource
    {
        $talentPool->update($request->validated());

        return new TalentPoolResource($talentPool->refresh()->load('candidate'));
    }

    public function assignToJob(AssignTalentPoolToJobRequest $request, TalentPool $talentPool): ApplicationResource
    {
        $job = JobPosting::query()->findOrFail($request->integer('job_posting_id'));

        return new ApplicationResource($this->talentPoolService->assignToJob($talentPool, $job, $request->user()));
    }
}
