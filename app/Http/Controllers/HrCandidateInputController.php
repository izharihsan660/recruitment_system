<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHrCandidateJobInputRequest;
use App\Http\Requests\StoreHrCandidateTalentPoolInputRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\TalentPoolResource;
use App\Services\HrCandidateInputService;

class HrCandidateInputController extends Controller
{
    public function __construct(private readonly HrCandidateInputService $hrCandidateInputService) {}

    public function inputToJob(StoreHrCandidateJobInputRequest $request): ApplicationResource
    {
        return new ApplicationResource($this->hrCandidateInputService->inputToJob($request->validated(), $request->user()));
    }

    public function inputToTalentPool(StoreHrCandidateTalentPoolInputRequest $request): TalentPoolResource
    {
        return new TalentPoolResource($this->hrCandidateInputService->inputToTalentPool($request->validated(), $request->user()));
    }
}
