<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateLoginRequest;
use App\Http\Requests\CandidateRegisterRequest;
use App\Http\Requests\UpdateCandidateProfileRequest;
use App\Http\Requests\UploadCandidateCvRequest;
use App\Http\Resources\CandidateResource;
use App\Services\CandidateAuthService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CandidateAuthController extends Controller
{
    public function __construct(private readonly CandidateAuthService $candidateAuthService) {}

    public function register(CandidateRegisterRequest $request): CandidateResource
    {
        $candidate = $this->candidateAuthService->register($request->validated());
        Auth::guard('candidate')->login($candidate);

        return new CandidateResource($candidate);
    }

    public function login(CandidateLoginRequest $request): CandidateResource
    {
        if (! Auth::guard('candidate')->attempt($request->validated())) {
            throw ValidationException::withMessages(['email' => 'Email atau password tidak valid.']);
        }

        $request->session()->regenerate();

        return new CandidateResource(Auth::guard('candidate')->user());
    }

    public function logout(): Response
    {
        Auth::guard('candidate')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return response()->noContent();
    }

    public function profile(): CandidateResource
    {
        return new CandidateResource(Auth::guard('candidate')->user());
    }

    public function updateProfile(UpdateCandidateProfileRequest $request): CandidateResource
    {
        $candidate = $this->candidateAuthService->updateProfile($request->user('candidate'), $request->validated());

        return new CandidateResource($candidate);
    }

    public function uploadCv(UploadCandidateCvRequest $request): CandidateResource
    {
        $candidate = $this->candidateAuthService->replaceCv($request->user('candidate'), $request->file('cv'));

        return new CandidateResource($candidate);
    }
}
