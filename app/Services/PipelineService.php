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

            $application->loadMissing([
                'jobPosting', 'screening', 'psychoTest', 'hrInterview', 'userInterview', 'backgroundCheck',
                'offeringLetter', 'mcuSimperRecord', 'hiringDecision', 'pkwtContract',
            ]);
            $this->validateStageGate($application, $fromStage);

            $toStage = self::STAGES[$stageIndex + 1];

            $notes = null;

            if ($toStage === 'test_psikotes' && ! $application->jobPosting->test_required) {
                $toStage = 'interview_hr';
                $notes = 'Test psikotes tidak diperlukan untuk posisi ini.';
            }

            $application->update(['status' => $toStage]);
            PipelineLog::query()->create([
                'application_id' => $application->id,
                'from_stage' => $fromStage,
                'to_stage' => $toStage,
                'actor_id' => $actor->id,
                'notes' => $notes,
            ]);
        });
    }

    private function validateStageGate(Application $application, string $fromStage): void
    {
        match ($fromStage) {
            'applied' => null,
            'screening' => $this->validateScreeningGate($application),
            'test_psikotes' => $this->validatePsychoTestGate($application),
            'interview_hr' => $this->validateHrInterviewGate($application),
            'interview_user' => $this->validateUserInterviewGate($application),
            'background_check' => $this->validateBackgroundCheckGate($application),
            'offering' => $this->validateOfferingGate($application),
            'mcu_simper' => $this->validateMcuSimperGate($application),
            'hiring_decision' => $this->validateHiringDecisionGate($application),
            'pkwt' => $this->validatePkwtGate($application),
            default => null,
        };
    }

    private function validateScreeningGate(Application $application): void
    {
        if (! $application->screening || $application->screening->decision === null || $application->screening->decision === 'pending_info') {
            throw ValidationException::withMessages(['status' => 'Form screening harus diisi dan keputusan screening harus final sebelum kandidat dipindahkan.']);
        }

        if ($application->screening->decision === 'failed') {
            throw ValidationException::withMessages(['status' => 'Kandidat gagal screening, gunakan Reject.']);
        }
    }

    private function validatePsychoTestGate(Application $application): void
    {
        if (! $application->psychoTest || $application->psychoTest->decision !== 'passed') {
            throw ValidationException::withMessages(['status' => 'Form psikotes harus diisi dan hasil psikotes harus lulus sebelum kandidat dipindahkan.']);
        }
    }

    private function validateHrInterviewGate(Application $application): void
    {
        if (! $application->hrInterview || $application->hrInterview->recommendation === null) {
            throw ValidationException::withMessages(['status' => 'Form interview HR harus diisi sebelum kandidat dipindahkan.']);
        }

        if ($application->hrInterview->recommendation === 'not_recommended') {
            throw ValidationException::withMessages(['status' => 'Kandidat tidak direkomendasikan pada interview HR, gunakan Reject.']);
        }
    }

    private function validateUserInterviewGate(Application $application): void
    {
        if (! $application->userInterview || $application->userInterview->recommendation === null) {
            throw ValidationException::withMessages(['status' => 'Form interview user harus diisi sebelum kandidat dipindahkan.']);
        }

        if ($application->userInterview->recommendation === 'rejected') {
            throw ValidationException::withMessages(['status' => 'Kandidat ditolak pada interview user, gunakan Reject.']);
        }
    }

    private function validateBackgroundCheckGate(Application $application): void
    {
        if (! $application->backgroundCheck || $application->backgroundCheck->decision !== 'clear') {
            throw ValidationException::withMessages(['status' => 'Background check harus selesai dengan hasil clear sebelum kandidat dipindahkan.']);
        }
    }

    private function validateOfferingGate(Application $application): void
    {
        if (! $application->offeringLetter || $application->offeringLetter->status !== 'signed') {
            throw ValidationException::withMessages(['status' => 'Offering letter harus sudah ditandatangani sebelum kandidat dipindahkan.']);
        }
    }

    private function validateMcuSimperGate(Application $application): void
    {
        if (! $application->mcuSimperRecord) {
            throw ValidationException::withMessages(['status' => 'Form MCU/SIMPER harus diisi sebelum kandidat dipindahkan.']);
        }

        if ($application->mcuSimperRecord->mcu_required && $application->mcuSimperRecord->mcu_status !== 'passed') {
            throw ValidationException::withMessages(['status' => 'MCU wajib lulus sebelum kandidat dipindahkan.']);
        }

        if ($application->mcuSimperRecord->simper_required && $application->mcuSimperRecord->simper_status !== 'passed') {
            throw ValidationException::withMessages(['status' => 'SIMPER wajib lulus sebelum kandidat dipindahkan.']);
        }
    }

    private function validateHiringDecisionGate(Application $application): void
    {
        if (! $application->hiringDecision || $application->hiringDecision->decision !== 'approved') {
            throw ValidationException::withMessages(['status' => 'Hiring decision harus disetujui sebelum kandidat dipindahkan.']);
        }
    }

    private function validatePkwtGate(Application $application): void
    {
        if (! $application->pkwtContract || $application->pkwtContract->status !== 'signed') {
            throw ValidationException::withMessages(['status' => 'PKWT harus sudah ditandatangani sebelum kandidat dipindahkan.']);
        }
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
