<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Employee;
use App\Models\PreboardingItem;
use App\Models\User;
use App\Notifications\SubjectTextNotification;
use App\Services\PreboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PreboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_checklist_otomatis_terbuat_dari_template(): void
    {
        $checklist = app(PreboardingService::class)->createFromTemplate($this->employee());

        $this->assertCount(6, $checklist->items);
    }

    public function test_hr_assign_pic_mengirim_notifikasi(): void
    {
        Notification::fake();
        $checklist = app(PreboardingService::class)->createFromTemplate($this->employee());
        $pic = User::factory()->create(['is_active' => true]);

        app(PreboardingService::class)->assignPic($checklist->items->first(), $pic);

        Notification::assertSentTo($pic, SubjectTextNotification::class);
    }

    public function test_pic_complete_item_status_done_dan_non_pic_ditolak(): void
    {
        $checklist = app(PreboardingService::class)->createFromTemplate($this->employee());
        $item = $checklist->items->first();
        $pic = User::factory()->create(['is_active' => true]);
        app(PreboardingService::class)->assignPic($item, $pic);

        app(PreboardingService::class)->completeItem($item->refresh(), $pic);
        $this->assertSame('done', $item->refresh()->status);

        $this->expectException(ValidationException::class);
        app(PreboardingService::class)->completeItem(PreboardingItem::query()->create(['checklist_id' => $checklist->id, 'title' => 'Custom', 'assigned_to' => $pic->id]), User::factory()->create());
    }

    private function employee(): Employee
    {
        $application = Application::factory()->create(['status' => 'hired']);
        $application->load('candidate', 'jobPosting');

        return Employee::query()->create([
            'application_id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'entity_id' => $application->jobPosting->entity_id,
            'department_id' => $application->jobPosting->department_id,
            'employee_id' => fake()->unique()->bothify('EMP-###'),
            'full_name' => $application->candidate->name,
            'email' => $application->candidate->email,
            'position_name' => $application->jobPosting->position_name,
            'contract_type' => 'contract',
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'activated_by' => User::factory()->create(['is_active' => true])->id,
            'activated_at' => now(),
        ]);
    }
}
