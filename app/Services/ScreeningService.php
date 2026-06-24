<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Screening;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScreeningService
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function submit(Application $application, array $data, User $actor): Screening
    {
        if ($application->status !== 'screening') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage screening.']);
        }

        if (($data['decision'] ?? null) === 'failed' && blank($data['rejection_reason'] ?? null)) {
            throw ValidationException::withMessages(['rejection_reason' => 'Alasan penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($application, $data, $actor): Screening {
            $screening = Screening::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'education_match' => $data['education_match'],
                    'experience_match' => $data['experience_match'],
                    'document_complete' => $data['document_complete'],
                    'notes' => $data['notes'] ?? null,
                    'decision' => $data['decision'],
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                ],
            );

            if ($screening->decision === 'passed') {
                $this->pipelineService->moveToNextStage($application->refresh(), $actor);
            }

            if ($screening->decision === 'failed') {
                $this->pipelineService->reject($application->refresh(), $actor, (string) $screening->rejection_reason);
            }

            return $screening->refresh();
        });
    }
}
