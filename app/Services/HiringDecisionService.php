<?php

namespace App\Services;

use App\Models\Application;
use App\Models\HiringDecision;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HiringDecisionService
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function submit(Application $app, array $data, User $actor): HiringDecision
    {
        if ($app->status !== 'hiring_decision') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage Hiring Decision.']);
        }

        if (! $actor->hasAnyRole([Roles::HrManager, Roles::HrRecruiter])) {
            throw ValidationException::withMessages(['actor' => 'Hanya HR Manager atau HR Recruiter yang dapat submit keputusan.']);
        }

        if ($data['decision'] === 'rejected' && blank($data['reason'] ?? null)) {
            throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi jika ditolak.']);
        }

        return DB::transaction(function () use ($app, $data, $actor): HiringDecision {
            $decision = HiringDecision::query()->updateOrCreate(['application_id' => $app->id], [
                'decision' => $data['decision'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            if ($decision->decision === 'approved') {
                $this->pipelineService->moveToNextStage($app->refresh(), $actor);
            } else {
                $this->pipelineService->reject($app->refresh(), $actor, $decision->reason, true);
            }

            return $decision;
        });
    }
}
