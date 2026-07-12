<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\EmailIntake;
use App\Models\JobPosting;
use App\Models\PipelineLog;
use App\Models\TalentPool;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailIntakeReviewService
{
    public function __construct(private readonly TalentPoolService $talentPoolService) {}

    public function assignToJob(EmailIntake $intake, JobPosting $job, User $hr, bool $consent): Application
    {
        if (! $consent) {
            throw ValidationException::withMessages(['consent' => 'Consent diperlukan untuk assign ke lowongan.']);
        }

        if ($job->status !== 'open') {
            throw ValidationException::withMessages(['job_posting_id' => 'Lowongan harus berstatus open.']);
        }

        return DB::transaction(function () use ($intake, $job, $hr, $consent): Application {
            $candidate = $this->findOrCreateCandidate($intake, $hr);

            $application = Application::query()->create([
                'job_posting_id' => $job->id,
                'candidate_id' => $candidate->id,
                'source' => 'email_intake',
                'status' => 'screening',
                'input_by' => $hr->id,
                'consent' => $consent,
                'consent_at' => $consent ? now() : null,
                'consent_by' => $consent ? $hr->id : null,
            ]);

            PipelineLog::query()->create([
                'application_id' => $application->id,
                'from_stage' => null,
                'to_stage' => 'screening',
                'actor_id' => $hr->id,
                'notes' => 'Kandidat di-assign manual dari Email Applicant Inbox.',
            ]);

            $intake->update([
                'status' => 'assigned_to_job',
                'reviewed_by' => $hr->id,
                'reviewed_at' => now(),
                'candidate_id' => $candidate->id,
            ]);

            return $application;
        });
    }

    public function moveToTalentPool(EmailIntake $intake, User $hr, bool $consent, ?string $notes): TalentPool
    {
        if (! $consent) {
            throw ValidationException::withMessages(['consent' => 'Consent diperlukan untuk masuk talent pool.']);
        }

        if (blank($notes)) {
            throw ValidationException::withMessages(['notes' => 'Alasan wajib diisi untuk masuk talent pool.']);
        }

        return DB::transaction(function () use ($intake, $hr, $notes): TalentPool {
            $candidate = $this->findOrCreateCandidate($intake, $hr);

            $talentPool = $this->talentPoolService->addManual($candidate, ['notes' => $notes], $hr);

            $intake->update([
                'status' => 'moved_to_talent_pool',
                'reviewed_by' => $hr->id,
                'reviewed_at' => now(),
                'candidate_id' => $candidate->id,
            ]);

            return $talentPool;
        });
    }

    public function reject(EmailIntake $intake, User $hr, string $reason): void
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Alasan reject wajib diisi.']);
        }

        $intake->update([
            'status' => 'rejected',
            'reviewed_by' => $hr->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function ignore(EmailIntake $intake, User $hr): void
    {
        $intake->update(['status' => 'ignored', 'reviewed_by' => $hr->id, 'reviewed_at' => now()]);
    }

    public function markSpam(EmailIntake $intake, User $hr): void
    {
        $intake->update(['status' => 'spam', 'reviewed_by' => $hr->id, 'reviewed_at' => now()]);
    }

    private function findOrCreateCandidate(EmailIntake $intake, User $hr): Candidate
    {
        $candidate = Candidate::query()->where('email', $intake->sender_email)->first();

        if ($candidate !== null) {
            return $candidate;
        }

        $temporaryPassword = Str::password(12);

        return Candidate::query()->create([
            'name' => $intake->sender_name,
            'email' => $intake->sender_email,
            'password' => $temporaryPassword,
            'email_verified_at' => now(),
        ]);
    }
}
