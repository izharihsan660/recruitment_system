<?php

namespace App\Services;

use App\Models\Application;
use App\Models\BackgroundCheck;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BackgroundCheckService
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function submit(Application $app, array $data, User $actor): BackgroundCheck
    {
        if ($app->status !== 'background_check') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage Background Check.']);
        }

        if (($data['decision'] ?? null) === 'failed' && blank($data['rejection_reason'] ?? null)) {
            throw ValidationException::withMessages(['rejection_reason' => 'Alasan wajib diisi jika keputusan gagal.']);
        }

        return DB::transaction(function () use ($app, $data, $actor): BackgroundCheck {
            $backgroundCheck = BackgroundCheck::query()->updateOrCreate(
                ['application_id' => $app->id],
                [
                    'ktp_verified' => (bool) ($data['ktp_verified'] ?? false),
                    'ijazah_verified' => (bool) ($data['ijazah_verified'] ?? false),
                    'certificate_verified' => (bool) ($data['certificate_verified'] ?? false),
                    'reference_verified' => $data['reference_verified'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'decision' => $data['decision'],
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]
            );

            if ($backgroundCheck->decision === 'clear') {
                $this->pipelineService->moveToNextStage($app->refresh(), $actor);
            }

            if ($backgroundCheck->decision === 'failed') {
                $this->pipelineService->reject($app->refresh(), $actor, $backgroundCheck->rejection_reason, true);
            }

            return $backgroundCheck;
        });
    }
}
