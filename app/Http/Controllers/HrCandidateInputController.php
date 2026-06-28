<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHrCandidateJobInputRequest;
use App\Http\Requests\StoreHrCandidateTalentPoolInputRequest;
use App\Services\HrCandidateInputService;
use Illuminate\Http\RedirectResponse;

class HrCandidateInputController extends Controller
{
    public function __construct(private readonly HrCandidateInputService $hrCandidateInputService) {}

    public function inputToJob(StoreHrCandidateJobInputRequest $request): RedirectResponse
    {
        $this->hrCandidateInputService->inputToJob($request->validated(), $request->user());

        return redirect()->route('pipeline.index')
            ->with('success', 'Kandidat berhasil ditambahkan ke pipeline.');
    }

    public function inputToTalentPool(StoreHrCandidateTalentPoolInputRequest $request): RedirectResponse
    {
        $this->hrCandidateInputService->inputToTalentPool($request->validated(), $request->user());

        return redirect()->to('/hr/talent-pool')
            ->with('success', 'Kandidat berhasil ditambahkan ke Talent Pool.');
    }
}
