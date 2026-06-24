<?php

namespace App\Services;

use App\Models\Application;
use App\Models\McuSimperRecord;
use App\Models\User;
use App\Notifications\SubjectTextNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class McuSimperService
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function create(Application $app, User $actor): McuSimperRecord
    {
        if ($app->status !== 'mcu_simper') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage MCU/SIMPER.']);
        }

        $app->loadMissing('jobPosting');

        return McuSimperRecord::query()->firstOrCreate(
            ['application_id' => $app->id],
            [
                'mcu_required' => $app->jobPosting->mcu_required,
                'mcu_status' => $app->jobPosting->mcu_required ? null : 'not_required',
                'simper_required' => $app->jobPosting->simper_required,
                'simper_status' => $app->jobPosting->simper_required ? null : 'not_required',
                'recorded_by' => $actor->id,
            ]
        );
    }

    public function scheduleMcu(McuSimperRecord $record, array $data, User $actor): void
    {
        $this->ensureRequired($record->mcu_required, 'MCU tidak diperlukan.');
        $record->update(['mcu_scheduled_at' => $data['mcu_scheduled_at'], 'mcu_location' => $data['mcu_location'], 'mcu_status' => 'pending', 'recorded_by' => $actor->id]);
        $this->notifyCandidate($record, 'Jadwal MCU', $data['mcu_location'], $data['mcu_scheduled_at'], 'Silakan berpuasa sesuai instruksi klinik dan membawa identitas diri.');
    }

    public function scheduleSimper(McuSimperRecord $record, array $data, User $actor): void
    {
        $this->ensureRequired($record->simper_required, 'SIMPER tidak diperlukan.');
        $record->update(['simper_scheduled_at' => $data['simper_scheduled_at'], 'simper_location' => $data['simper_location'], 'simper_status' => 'pending', 'recorded_by' => $actor->id]);
        $this->notifyCandidate($record, 'Jadwal SIMPER', $data['simper_location'], $data['simper_scheduled_at'], 'Silakan membawa identitas diri, SIM, dan dokumen pendukung operator.');
    }

    public function submitMcuResult(McuSimperRecord $record, array $data, User $actor): void
    {
        $this->submitResult($record, $data, $actor, 'mcu');
    }

    public function submitSimperResult(McuSimperRecord $record, array $data, User $actor): void
    {
        $this->submitResult($record, $data, $actor, 'simper');
    }

    public function checkCanProceed(McuSimperRecord $record): bool
    {
        return in_array($record->mcu_status, ['not_required', 'passed'], true)
            && in_array($record->simper_status, ['not_required', 'passed'], true);
    }

    public function proceedToHiringDecision(McuSimperRecord $record, User $actor): void
    {
        if (! $this->checkCanProceed($record)) {
            throw ValidationException::withMessages(['status' => 'MCU dan SIMPER wajib lulus atau tidak diperlukan.']);
        }

        $this->pipelineService->moveToNextStage($record->application, $actor);
    }

    private function submitResult(McuSimperRecord $record, array $data, User $actor, string $type): void
    {
        $this->ensureRequired((bool) $record->{$type.'_required'}, strtoupper($type).' tidak diperlukan.');
        if ($data['status'] === 'failed' && blank($data['rejection_reason'] ?? null)) {
            throw ValidationException::withMessages(['rejection_reason' => 'Alasan penolakan wajib diisi jika tidak lulus.']);
        }

        DB::transaction(function () use ($record, $data, $actor, $type): void {
            $path = $data['result_file'] instanceof UploadedFile
                ? $data['result_file']->store('documents/'.$type, 'local')
                : null;

            $record->update([
                $type.'_result_path' => $path,
                $type.'_status' => $data['status'],
                $type.'_notes' => $data['notes'] ?? null,
                'rejection_reason' => $data['rejection_reason'] ?? $record->rejection_reason,
                'recorded_by' => $actor->id,
            ]);

            if ($data['status'] === 'failed') {
                $this->pipelineService->reject($record->application, $actor, $data['rejection_reason'], true);
            }
        });
    }

    private function notifyCandidate(McuSimperRecord $record, string $label, string $location, string $date, string $instruction): void
    {
        $record->loadMissing('application.candidate', 'application.jobPosting');
        $position = $record->application->jobPosting->position_name;
        $record->application->candidate->notify(new SubjectTextNotification($label.' — '.$position, "Tempat: {$location}. Tanggal: {$date}. {$instruction}"));
    }

    private function ensureRequired(bool $required, string $message): void
    {
        if (! $required) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }
}
