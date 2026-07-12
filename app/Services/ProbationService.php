<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ProbationEvaluation;
use App\Models\ProbationRecord;
use App\Models\User;
use App\Notifications\SubjectTextNotification;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ProbationService
{
    public function create(Employee $employee): ProbationRecord
    {
        $start = Carbon::parse($employee->start_date);

        return ProbationRecord::query()->firstOrCreate(['employee_id' => $employee->id], [
            'start_date' => $start,
            'day30_due' => $start->copy()->addDays(30),
            'day60_due' => $start->copy()->addDays(60),
            'day90_due' => $start->copy()->addDays(90),
            'status' => 'in_progress',
        ]);
    }

    public function submitEvaluation(ProbationRecord $probation, array $data, User $actor): ProbationEvaluation
    {
        $probation->loadMissing('employee');
        if (! $this->canSubmitEvaluation($actor, $probation, $data['milestone'])) {
            throw ValidationException::withMessages(['actor' => 'Hanya hiring manager departemen terkait yang dapat evaluasi.']);
        }

        $allowedMilestone = $this->currentMilestone($probation);
        if ($data['milestone'] !== $allowedMilestone) {
            throw ValidationException::withMessages(['milestone' => 'Milestone evaluasi tidak sesuai status probation.']);
        }

        $recommendation = $data['recommendation'] ?? null;

        $evaluation = $probation->evaluations()->create([
            'milestone' => $data['milestone'],
            'evaluator_id' => $actor->id,
            'performance_notes' => $data['performance_notes'],
            'recommendation' => $recommendation ?? 'permanent',
            'evaluated_at' => now(),
        ]);

        match ($data['milestone']) {
            'day30' => $probation->update(['status' => 'day60_review']),
            'day60' => $probation->update(['status' => 'day90_review']),
            'day90', 'extended' => $this->applyOutcome($probation, $recommendation, $data),
        };

        return $evaluation;
    }

    public function submitOutcome(ProbationRecord $probation, string $outcome, User $actor, ?string $extendedUntil = null): void
    {
        if (! $actor->hasAnyRole([Roles::Admin, Roles::HrManager, Roles::HrRecruiter])) {
            throw ValidationException::withMessages(['actor' => 'Hanya HR yang dapat submit outcome probation.']);
        }

        $this->applyOutcome($probation, $outcome, ['extended_end_date' => $extendedUntil]);
    }

    /**
     * @param  array{extended_start_date?: string|null, extended_end_date?: string|null}  $data
     */
    private function applyOutcome(ProbationRecord $probation, ?string $outcome, array $data = []): void
    {
        if (! $outcome) {
            throw ValidationException::withMessages(['recommendation' => 'Outcome wajib dipilih.']);
        }

        if ($outcome === 'extended') {
            if ($probation->extension_count >= 1) {
                throw ValidationException::withMessages(['outcome' => 'Probation hanya boleh diperpanjang satu kali.']);
            }

            if ($probation->status === 'extended') {
                throw ValidationException::withMessages(['outcome' => 'Probation extended tidak bisa diperpanjang lagi.']);
            }

            $probation->update([
                'extended_start_date' => $data['extended_start_date'] ?? now()->toDateString(),
                'extended_until' => $data['extended_end_date'] ?? now()->addDays(30)->toDateString(),
                'extension_count' => $probation->extension_count + 1,
                'status' => 'extended',
            ]);

            return;
        }

        $probation->update(['final_outcome' => $outcome, 'status' => $outcome]);
    }

    public function sendH7Reminders(): void
    {
        ProbationRecord::query()->with('employee.department.users')->whereIn('status', ['in_progress', 'day30_review', 'day60_review', 'day90_review', 'extended'])->get()->each(function (ProbationRecord $record): void {
            if (! in_array(now()->addDays(7)->toDateString(), [$record->day30_due->toDateString(), $record->day60_due->toDateString(), $record->day90_due->toDateString(), optional($record->extended_until)->toDateString()], true)) {
                return;
            }
            $hiringManagers = $record->employee->department->users
                ->filter(fn (User $user): bool => $user->hasRole(Roles::HiringManager));
            $hrUsers = User::role([Roles::HrManager, Roles::HrRecruiter], 'web')->get();

            $hiringManagers->merge($hrUsers)->unique('id')->each(
                fn (User $user) => $user->notify(new SubjectTextNotification('Reminder Probation H-7', 'Probation '.$record->employee->full_name.' jatuh tempo dalam 7 hari.'))
            );
        });
    }

    private function currentMilestone(ProbationRecord $probation): string
    {
        return match ($probation->status) {
            'in_progress', 'day30_review' => 'day30',
            'day60_review' => 'day60',
            'day90_review' => 'day90',
            'extended' => 'extended',
            default => throw ValidationException::withMessages(['status' => 'Status probation tidak dapat dievaluasi.']),
        };
    }

    private function canSubmitEvaluation(User $actor, ProbationRecord $probation, string $milestone): bool
    {
        if ($actor->hasRole(Roles::Admin)) {
            return true;
        }

        if ($actor->hasRole(Roles::HiringManager) && (int) $actor->department_id === (int) $probation->employee->department_id) {
            return true;
        }

        return in_array($milestone, ['day90', 'extended'], true) && $actor->hasAnyRole([Roles::HrManager, Roles::HrRecruiter]);
    }
}
