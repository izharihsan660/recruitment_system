<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\SubjectTextNotification;
use App\Services\ProbationService;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProbationTest extends TestCase
{
    use RefreshDatabase;

    public function test_probation_record_otomatis_terbuat(): void
    {
        $record = app(ProbationService::class)->create($this->employee());

        $this->assertSame('in_progress', $record->status);
    }

    public function test_hiring_manager_submit_evaluasi_day30_tanpa_outcome_status_update(): void
    {
        $employee = $this->employee();
        $record = app(ProbationService::class)->create($employee);
        $manager = $this->user(Roles::HiringManager, $employee->department_id);

        app(ProbationService::class)->submitEvaluation($record, ['milestone' => 'day30', 'performance_notes' => 'Baik'], $manager);

        $this->assertSame('day60_review', $record->refresh()->status);
    }

    public function test_milestone_60_hanya_bisa_setelah_day30_selesai(): void
    {
        $employee = $this->employee();
        $record = app(ProbationService::class)->create($employee);
        $manager = $this->user(Roles::HiringManager, $employee->department_id);

        $this->expectException(ValidationException::class);

        app(ProbationService::class)->submitEvaluation($record, ['milestone' => 'day60', 'performance_notes' => 'Baik'], $manager);
    }

    public function test_hr_submit_outcome_extended_increment_dan_maksimal_satu_kali(): void
    {
        $record = app(ProbationService::class)->create($this->employee());
        $hr = $this->user(Roles::HrRecruiter);

        app(ProbationService::class)->submitOutcome($record, 'extended', $hr, now()->addMonth()->toDateString());
        $this->assertSame(1, $record->refresh()->extension_count);

        $this->expectException(ValidationException::class);
        app(ProbationService::class)->submitOutcome($record->refresh(), 'extended', $hr, now()->addMonths(2)->toDateString());
    }

    public function test_day90_extended_membuat_milestone_extended_lalu_final_permanent(): void
    {
        $employee = $this->employee();
        $record = app(ProbationService::class)->create($employee);
        $manager = $this->user(Roles::HiringManager, $employee->department_id);

        app(ProbationService::class)->submitEvaluation($record, ['milestone' => 'day30', 'performance_notes' => 'Baik'], $manager);
        app(ProbationService::class)->submitEvaluation($record->refresh(), ['milestone' => 'day60', 'performance_notes' => 'Baik'], $manager);
        app(ProbationService::class)->submitEvaluation($record->refresh(), [
            'milestone' => 'day90',
            'performance_notes' => 'Perlu tambahan waktu',
            'recommendation' => 'extended',
            'extended_start_date' => now()->addDay()->toDateString(),
            'extended_end_date' => now()->addMonth()->toDateString(),
        ], $manager);

        $this->assertSame('extended', $record->refresh()->status);
        $this->assertSame(1, $record->extension_count);

        app(ProbationService::class)->submitEvaluation($record->refresh(), ['milestone' => 'extended', 'performance_notes' => 'Sudah siap', 'recommendation' => 'permanent'], $manager);

        $this->assertDatabaseHas('probation_records', ['id' => $record->id, 'final_outcome' => 'permanent', 'status' => 'permanent']);
    }

    public function test_hr_submit_outcome_permanent_set_final_outcome(): void
    {
        $record = app(ProbationService::class)->create($this->employee());

        app(ProbationService::class)->submitOutcome($record, 'permanent', $this->user(Roles::HrRecruiter));

        $this->assertDatabaseHas('probation_records', ['id' => $record->id, 'final_outcome' => 'permanent', 'status' => 'permanent']);
    }

    public function test_h7_reminder_dikirim_ke_hiring_manager_dan_hr(): void
    {
        Notification::fake();
        $employee = $this->employee();
        $record = app(ProbationService::class)->create($employee);
        $record->update(['day30_due' => now()->addDays(7)->toDateString()]);
        $manager = $this->user(Roles::HiringManager, $employee->department_id);
        $hrRecruiter = $this->user(Roles::HrRecruiter);
        $hrManager = $this->user(Roles::HrManager);

        app(ProbationService::class)->sendH7Reminders();

        Notification::assertSentTo([$manager, $hrRecruiter, $hrManager], SubjectTextNotification::class);
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

    private function user(string $role, ?int $departmentId = null): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true, 'department_id' => $departmentId]);
        $user->assignRole($role);

        return $user;
    }
}
