<?php

namespace App\Services;

use App\Models\JobPosting;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobPostingService
{
    public function createFromFpk(RecruitmentRequest $fpk, array $data, User $actor): JobPosting
    {
        return DB::transaction(function () use ($fpk, $data): JobPosting {
            if ($fpk->status !== 'approved') {
                throw ValidationException::withMessages([
                    'recruitment_request_id' => 'Job posting hanya bisa dibuat dari FPK approved.',
                ]);
            }

            if ($fpk->jobPostings()->whereIn('status', ['draft', 'open'])->exists()) {
                throw ValidationException::withMessages([
                    'recruitment_request_id' => 'FPK sudah memiliki job posting aktif.',
                ]);
            }

            return JobPosting::query()->create([
                ...$data,
                'recruitment_request_id' => $fpk->id,
                'entity_id' => $fpk->entity_id,
                'department_id' => $fpk->department_id,
                'employment_status' => $fpk->employment_status,
                'position_name' => $data['position_name'] ?? $fpk->position_name,
                'work_location' => $data['work_location'] ?? $fpk->work_location,
                'job_description' => $data['job_description'] ?? $fpk->job_description,
                'status' => 'draft',
            ]);
        });
    }

    public function update(JobPosting $job, array $data): JobPosting
    {
        if ($job->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Job posting hanya bisa diedit saat draft.',
            ]);
        }

        unset($data['entity_id'], $data['department_id'], $data['employment_status'], $data['recruitment_request_id']);
        $job->update($data);

        return $job;
    }

    public function open(JobPosting $job, User $actor): void
    {
        $job->update([
            'status' => 'open',
            'opened_at' => now(),
            'closed_at' => null,
        ]);
    }

    public function close(JobPosting $job, User $actor): void
    {
        $job->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function cancel(JobPosting $job, User $actor): void
    {
        if ($job->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Job posting hanya bisa dibatalkan saat draft.',
            ]);
        }

        $job->update(['status' => 'cancelled']);
    }
}
