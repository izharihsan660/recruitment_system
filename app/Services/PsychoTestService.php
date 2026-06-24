<?php

namespace App\Services;

use App\Models\Application;
use App\Models\PsychoTest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PsychoTestService
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function schedule(Application $application, array $data, User $actor): PsychoTest
    {
        if ($application->status !== 'test_psikotes') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage test psikotes.']);
        }

        return PsychoTest::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'test_type' => $data['test_type'],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'conducted_by' => $actor->id,
            ],
        );
    }

    public function submitResult(PsychoTest $test, array $data, User $actor): void
    {
        if ($test->application->status !== 'test_psikotes') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage test psikotes.']);
        }

        if (($data['decision'] ?? null) === 'failed' && blank($data['rejection_reason'] ?? null)) {
            throw ValidationException::withMessages(['rejection_reason' => 'Alasan penolakan wajib diisi.']);
        }

        DB::transaction(function () use ($test, $data, $actor): void {
            $test->update([
                'decision' => $data['decision'],
                'notes' => $data['notes'] ?? $test->notes,
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'conducted_by' => $actor->id,
            ]);

            if ($test->decision === 'passed') {
                $this->pipelineService->moveToNextStage($test->application->refresh(), $actor);
            }

            if ($test->decision === 'failed') {
                $this->pipelineService->reject($test->application->refresh(), $actor, (string) $test->rejection_reason);
            }
        });
    }
}
