<?php

namespace App\Services;

use App\Mail\CandidatePortalCredentialsMail;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\TalentPool;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HrCandidateInputService
{
    public function __construct(private readonly TalentPoolService $talentPoolService) {}

    public function inputToJob(array $data, User $hr): Application
    {
        return DB::transaction(function () use ($data, $hr): Application {
            $candidate = $this->findOrCreateCandidate($data);
            $this->updateCandidateProfile($candidate, $data);

            return Application::query()->create([
                'job_posting_id' => $data['job_posting_id'],
                'candidate_id' => $candidate->id,
                'source' => 'hr_input',
                'source_id' => $data['source_id'] ?? null,
                'referral_name' => $data['referral_name'] ?? null,
                'referral_department' => $data['referral_department'] ?? null,
                'referral_phone' => $data['referral_phone'] ?? null,
                'referral_relation' => $data['referral_relation'] ?? null,
                'referral_notes' => $data['referral_notes'] ?? null,
                'input_by' => $hr->id,
                'status' => 'applied',
                'consent' => true,
                'consent_at' => now(),
                'consent_by' => $hr->id,
            ]);
        });
    }

    public function inputToTalentPool(array $data, User $hr): TalentPool
    {
        return DB::transaction(function () use ($data, $hr): TalentPool {
            $candidate = $this->findOrCreateCandidate($data);
            $this->updateCandidateProfile($candidate, $data);

            return $this->talentPoolService->addManual($candidate, [
                'status' => $data['status'] ?? 'active',
                'tags' => $data['tags'] ?? null,
                'notes' => $data['notes'] ?? null,
            ], $hr);
        });
    }

    private function findOrCreateCandidate(array $data): Candidate
    {
        $candidate = Candidate::query()->where('email', $data['email'])->first();

        if ($candidate !== null) {
            return $candidate;
        }

        $temporaryPassword = Str::password(12);
        $candidate = Candidate::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => $temporaryPassword,
            'email_verified_at' => now(),
        ]);

        Mail::to($candidate->email)->send(new CandidatePortalCredentialsMail($candidate, $temporaryPassword));

        return $candidate;
    }

    private function updateCandidateProfile(Candidate $candidate, array $data): void
    {
        $cvPath = $candidate->cv_path;
        $cvOriginalName = $candidate->cv_original_name;

        if (($data['cv'] ?? null) instanceof UploadedFile) {
            if ($candidate->cv_path) {
                Storage::disk('public')->delete($candidate->cv_path);
            }

            $cvPath = $data['cv']->store('cvs', 'public');
            $cvOriginalName = $data['cv']->getClientOriginalName();
        }

        $candidate->update([
            'cv_path' => $cvPath,
            'cv_original_name' => $cvOriginalName,
            'education' => [
                'level' => $data['education_level'],
                'major' => $data['education_major'] ?? null,
                'institution' => $data['education_institution'] ?? null,
            ],
            'experience' => [
                'company' => $data['experience_company'] ?? null,
                'position' => $data['experience_position'] ?? null,
                'years' => $data['experience_years'] ?? null,
            ],
        ]);
    }
}
