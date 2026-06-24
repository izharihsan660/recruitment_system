<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Employee;
use App\Models\User;
use App\Services\ProbationService;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_hiring_manager_submit_evaluasi_day30_status_update(): void
    {
        $employee = $this->employee();
        $record = app(ProbationService::class)->create($employee);
        $manager = $this->user(Roles::HiringManager, $employee->department_id);

        app(ProbationService::class)->submitEvaluation($record, ['milestone' => 'day30', 'performance_notes' => 'Baik', 'recommendation' => 'permanent'], $manager);

        $this->assertSame('day60_review', $record->refresh()->status);
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

    public function test_hr_submit_outcome_permanent_set_final_outcome(): void
    {
        $record = app(ProbationService::class)->create($this->employee());

        app(ProbationService::class)->submitOutcome($record, 'permanent', $this->user(Roles::HrRecruiter));

        $this->assertDatabaseHas('probation_records', ['id' => $record->id, 'final_outcome' => 'permanent', 'status' => 'permanent']);
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
