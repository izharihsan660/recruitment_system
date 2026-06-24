<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PreboardingChecklist;
use App\Models\PreboardingItem;
use App\Models\User;
use App\Notifications\SubjectTextNotification;
use Illuminate\Validation\ValidationException;

class PreboardingService
{
    public const DEFAULT_ITEMS = [
        'Welcome Email & Intro Perusahaan',
        'Pengumpulan Dokumen Final',
        'Info Hari Pertama (jam, lokasi, agenda)',
        'IT & Equipment (akun, laptop, alat kerja)',
        'Buddy Assign & Welcome Pack',
        'Orientasi & Training',
    ];

    public function createFromTemplate(Employee $employee): PreboardingChecklist
    {
        $checklist = PreboardingChecklist::query()->firstOrCreate(
            ['employee_id' => $employee->id],
            ['status' => 'not_started', 'first_day' => $employee->start_date]
        );

        if ($checklist->items()->doesntExist()) {
            foreach (self::DEFAULT_ITEMS as $title) {
                $checklist->items()->create(['title' => $title, 'status' => 'pending']);
            }
        }

        return $checklist->load('items');
    }

    public function addItem(PreboardingChecklist $checklist, array $data): PreboardingItem
    {
        return $checklist->items()->create($data + ['status' => 'pending']);
    }

    public function removeItem(PreboardingItem $item): void
    {
        $checklist = $item->checklist;
        $item->delete();
        $this->syncChecklistStatus($checklist->refresh());
    }

    public function assignPic(PreboardingItem $item, User $pic): void
    {
        $item->update(['assigned_to' => $pic->id, 'status' => 'in_progress']);
        $pic->notify(new SubjectTextNotification('Tugas Pre-boarding', 'Anda ditugaskan sebagai PIC: '.$item->title));
        $this->syncChecklistStatus($item->checklist->refresh());
    }

    public function completeItem(PreboardingItem $item, User $actor): void
    {
        if ((int) $item->assigned_to !== (int) $actor->id) {
            throw ValidationException::withMessages(['actor' => 'Hanya PIC yang di-assign dapat menyelesaikan item.']);
        }

        $item->update(['status' => 'done', 'completed_at' => now()]);
        $this->syncChecklistStatus($item->checklist->refresh());
    }

    public function sendH7Reminders(): void
    {
        PreboardingChecklist::query()->with('items.pic')->whereDate('first_day', now()->addDays(7)->toDateString())->each(function (PreboardingChecklist $checklist): void {
            $checklist->items->where('status', '!=', 'done')->each(function (PreboardingItem $item): void {
                $item->pic?->notify(new SubjectTextNotification('Reminder Pre-boarding H-7', 'Mohon selesaikan item: '.$item->title));
            });
        });
    }

    private function syncChecklistStatus(PreboardingChecklist $checklist): void
    {
        $items = $checklist->items()->get();
        $status = $items->every(fn (PreboardingItem $item): bool => $item->status === 'done') ? 'completed' : ($items->contains(fn (PreboardingItem $item): bool => in_array($item->status, ['in_progress', 'done'], true)) ? 'in_progress' : 'not_started');
        $checklist->update(['status' => $status]);
    }
}
