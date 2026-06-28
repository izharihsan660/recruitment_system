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
        if (! $actor->hasRole(Roles::Admin) && (! $actor->hasRole(Roles::HiringManager) || (int) $actor->department_id !== (int) $probation->employee->department_id)) {
            throw ValidationException::withMessages(['actor' => 'Hanya hiring manager departemen terkait yang dapat evaluasi.']);
        }

        $allowedMilestone = $this->currentMilestone($probation);
        if ($data['milestone'] !== $allowedMilestone) {
            throw ValidationException::withMessages(['milestone' => 'Milestone evaluasi tidak sesuai status probation.']);
        }

        $evaluation = $probation->evaluations()->create([
            'milestone' => $data['milestone'],
            'evaluator_id' => $actor->id,
            'performance_notes' => $data['performance_notes'],
            'recommendation' => $data['recommendation'],
            'evaluated_at' => now(),
        ]);

        $probation->update(['status' => match ($data['milestone']) {
            'day30' => 'day60_review',
            'day60' => 'day90_review',
            'day90', 'extended' => $probation->status,
        }]);

        return $evaluation;
    }

    public function submitOutcome(ProbationRecord $probation, string $outcome, User $actor, ?string $extendedUntil = null): void
    {
        if (! $actor->hasAnyRole([Roles::Admin, Roles::HrManager, Roles::HrRecruiter])) {
            throw ValidationException::withMessages(['actor' => 'Hanya HR yang dapat submit outcome probation.']);
        }

        if ($outcome === 'extended') {
            if ($probation->extension_count >= 1) {
                throw ValidationException::withMessages(['outcome' => 'Probation hanya boleh diperpanjang satu kali.']);
            }
            $probation->update(['extended_until' => $extendedUntil ?? now()->addDays(30), 'extension_count' => $probation->extension_count + 1, 'status' => 'extended']);

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
            $record->employee->department->users->each(fn (User $user) => $user->notify(new SubjectTextNotification('Reminder Probation H-7', 'Probation '.$record->employee->full_name.' jatuh tempo dalam 7 hari.')));
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
}
