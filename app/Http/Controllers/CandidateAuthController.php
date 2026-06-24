<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateLoginRequest;
use App\Http\Requests\CandidateRegisterRequest;
use App\Http\Requests\UpdateCandidateProfileRequest;
use App\Http\Requests\UploadCandidateCvRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\CandidateResource;
use App\Services\CandidateAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;

class CandidateAuthController extends Controller
{
    public function __construct(private readonly CandidateAuthService $candidateAuthService) {}

    public function showRegister(): View
    {
        return view('candidate.auth.register');
    }

    public function showLogin(): View
    {
        return view('candidate.auth.login');
    }

    public function showForgotPassword(): View
    {
        return view('candidate.auth.forgot-password');
    }

    public function showResetPassword(string $token, Request $request): View
    {
        return view('candidate.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('candidates')->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('candidates')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($candidate, string $password): void {
                $candidate->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('candidate.login.form')->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function register(CandidateRegisterRequest $request): RedirectResponse|JsonResponse
    {
        $candidate = $this->candidateAuthService->register($request->validated());
        Auth::guard('candidate')->login($candidate);
        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['data' => CandidateResource::make($candidate)->resolve()], 201);
        }

        return redirect()->intended('/candidate/dashboard');
    }

    public function login(CandidateLoginRequest $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->only(['email', 'password']);
        $remember = $request->boolean('remember');

        if (! Auth::guard('candidate')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages(['email' => 'Email atau password tidak valid.']);
        }

        $request->session()->regenerate();

        if ($request->expectsJson()) {
            return response()->json(['data' => CandidateResource::make(Auth::guard('candidate')->user())->resolve()]);
        }

        return redirect()->intended('/candidate/dashboard');
    }

    public function logout(): RedirectResponse|JsonResponse
    {
        Auth::guard('candidate')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        if (request()->expectsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('portal.home');
    }

    public function dashboard(): Response
    {
        $candidate = Auth::guard('candidate')->user();
        $applications = $candidate->applications()->with(['jobPosting.entity', 'jobPosting.department'])->latest()->get();

        return Inertia::render('Candidate/Dashboard', [
            'candidate' => CandidateResource::make($candidate),
            'summary' => [
                'active' => $applications->whereNotIn('status', ['rejected', 'withdrawn', 'hired'])->count(),
                'processed' => $applications->whereIn('status', ['screening', 'test_psikotes', 'interview_hr', 'interview_user', 'background_check', 'offering', 'mcu_simper', 'hiring_decision', 'pkwt'])->count(),
                'accepted' => $applications->where('status', 'hired')->count(),
            ],
            'latestApplications' => ApplicationResource::collection($applications->take(5))->resolve(),
        ]);
    }

    public function profile(): Response|JsonResponse
    {
        if (request()->expectsJson()) {
            return response()->json(['data' => CandidateResource::make(Auth::guard('candidate')->user())->resolve()]);
        }

        return Inertia::render('Candidate/Profile', [
            'candidate' => CandidateResource::make(Auth::guard('candidate')->user()),
        ]);
    }

    public function updateProfile(UpdateCandidateProfileRequest $request): RedirectResponse|JsonResponse
    {
        $candidate = $this->candidateAuthService->updateProfile($request->user('candidate'), $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['data' => CandidateResource::make($candidate)->resolve()]);
        }

        return back()->with('success', 'Profil berhasil disimpan.');
    }

    public function uploadCv(UploadCandidateCvRequest $request): RedirectResponse|JsonResponse
    {
        $candidate = $this->candidateAuthService->replaceCv($request->user('candidate'), $request->file('cv'));

        if ($request->expectsJson()) {
            return response()->json(['data' => CandidateResource::make($candidate)->resolve()]);
        }

        return back()->with('success', 'CV berhasil diunggah.');
    }
}
