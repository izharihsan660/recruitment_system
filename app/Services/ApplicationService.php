<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function apply(JobPosting $job, Candidate $candidate, bool $consent): Application
    {
        return DB::transaction(function () use ($job, $candidate, $consent): Application {
            if ($job->status !== 'open') {
                throw ValidationException::withMessages(['job' => 'Lowongan tidak sedang dibuka.']);
            }

            if (! $consent) {
                throw ValidationException::withMessages(['consent' => 'Consent wajib dicentang sebelum apply.']);
            }

            if (! $candidate->hasCv()) {
                throw ValidationException::withMessages(['cv' => 'CV wajib diunggah sebelum apply.']);
            }

            if ($job->applications()->whereBelongsTo($candidate)->exists()) {
                throw ValidationException::withMessages(['job' => 'Kandidat sudah apply lowongan ini.']);
            }

            return Application::query()->create([
                'job_posting_id' => $job->id,
                'candidate_id' => $candidate->id,
                'source' => 'portal',
                'status' => 'applied',
                'consent' => true,
                'consent_at' => now(),
            ]);
        });
    }

    public function withdraw(Application $application, Candidate $candidate): void
    {
        if ($application->candidate_id !== $candidate->id) {
            abort(404);
        }

        if (! in_array($application->status, ['rejected', 'withdrawn'], true)) {
            $application->update([
                'status' => 'withdrawn',
                'withdrawn_at' => now(),
            ]);
        }
    }
}
