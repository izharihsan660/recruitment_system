<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\TalentPool;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TalentPoolService
{
    public function addFromRejection(Application $application, User $hr, bool $override = false): ?TalentPool
    {
        if ($override || ! $application->consent) {
            return null;
        }

        return DB::transaction(function () use ($application, $hr): TalentPool {
            $talentPool = TalentPool::query()->where('candidate_id', $application->candidate_id)->first();

            if ($talentPool !== null) {
                $talentPool->update([
                    'notes' => trim(($talentPool->notes ? $talentPool->notes.PHP_EOL : '').'Rejected at '.$application->rejection_stage.': '.$application->rejection_reason),
                    'source_application_id' => $talentPool->source_application_id ?? $application->id,
                ]);

                return $talentPool;
            }

            return TalentPool::query()->create([
                'candidate_id' => $application->candidate_id,
                'status' => 'active',
                'notes' => 'Rejected at '.$application->rejection_stage.': '.$application->rejection_reason,
                'source_application_id' => $application->id,
                'added_by' => $hr->id,
                'added_at' => now(),
            ]);
        });
    }

    public function addManual(Candidate $candidate, array $data, User $hr): TalentPool
    {
        return TalentPool::query()->updateOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'status' => $data['status'] ?? 'active',
                'tags' => $data['tags'] ?? null,
                'notes' => $data['notes'] ?? null,
                'added_by' => $hr->id,
                'added_at' => now(),
            ]
        );
    }

    public function assignToJob(TalentPool $talentPool, JobPosting $job, User $hr): Application
    {
        if ($talentPool->status === 'do_not_contact') {
            throw ValidationException::withMessages(['status' => 'Kandidat do not contact tidak bisa diassign ke lowongan.']);
        }

        return Application::query()->create([
            'job_posting_id' => $job->id,
            'candidate_id' => $talentPool->candidate_id,
            'source' => 'talent_pool',
            'status' => 'applied',
            'input_by' => $hr->id,
            'consent' => true,
            'consent_at' => now(),
            'consent_by' => $hr->id,
        ]);
    }
}
