<?php

namespace App\Services;

use App\Models\Application;
use App\Models\PipelineLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PipelineService
{
    public const STAGES = [
        'applied', 'screening', 'test_psikotes', 'interview_hr', 'interview_user', 'background_check',
        'offering', 'mcu_simper', 'hiring_decision', 'pkwt', 'hired',
    ];

    public function __construct(private readonly TalentPoolService $talentPoolService) {}

    public function moveToNextStage(Application $application, User $actor): void
    {
        DB::transaction(function () use ($application, $actor): void {
            $fromStage = $application->status;
            $stageIndex = array_search($fromStage, self::STAGES, true);

            if ($stageIndex === false || $fromStage === 'hired') {
                throw ValidationException::withMessages(['status' => 'Stage aplikasi tidak valid untuk dipindahkan.']);
            }

            $toStage = self::STAGES[$stageIndex + 1];
            $application->loadMissing('jobPosting');

            if ($toStage === 'test_psikotes' && ! $application->jobPosting->test_required) {
                $toStage = 'interview_hr';
            }

            $application->update(['status' => $toStage]);
            PipelineLog::query()->create([
                'application_id' => $application->id,
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
                'actor_id' => $actor->id,
            ]);
        });
    }

    public function reject(Application $application, User $actor, string $reason, bool $skipTalentPool = false): void
    {
        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Alasan reject wajib diisi.']);
        }

        DB::transaction(function () use ($application, $actor, $reason, $skipTalentPool): void {
            $fromStage = $application->status;
            $application->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'rejection_stage' => $fromStage,
            ]);

            PipelineLog::query()->create([
                'application_id' => $application->id,
                'from_stage' => $fromStage,
                'to_stage' => 'rejected',
                'actor_id' => $actor->id,
                'notes' => $reason,
            ]);

            $this->talentPoolService->addFromRejection($application->refresh(), $actor, $skipTalentPool);
        });
    }

    public function withdraw(Application $application): void
    {
        $application->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);
    }
}
