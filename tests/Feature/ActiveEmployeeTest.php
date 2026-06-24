<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Employee;
use App\Models\PkwtContract;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveEmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_aktivasi_employee_dengan_pkwt_signed_archived_berhasil_dan_membuat_preboarding_probation(): void
    {
        $application = $this->hiredApplicationWithPkwt('archived');

        $this->actingAs($this->hrUser())->post("/hr/employees/{$application->id}/activate", ['employee_id' => 'EMP-001', 'start_date' => now()->toDateString()])->assertRedirect();

        $employee = Employee::query()->firstOrFail();
        $this->assertSame('active', $employee->status);
        $this->assertDatabaseHas('preboarding_checklists', ['employee_id' => $employee->id]);
        $this->assertDatabaseHas('probation_records', ['employee_id' => $employee->id]);
    }

    public function test_aktivasi_tanpa_pkwt_archived_ditolak(): void
    {
        $application = $this->hiredApplicationWithPkwt('pending');

        $this->actingAs($this->hrUser())->post("/hr/employees/{$application->id}/activate", ['employee_id' => 'EMP-002', 'start_date' => now()->toDateString()])->assertSessionHasErrors('pkwt');
    }

    public function test_employee_id_duplikat_ditolak(): void
    {
        Employee::query()->create($this->employeePayload(['employee_id' => 'EMP-003']));
        $application = $this->hiredApplicationWithPkwt('archived');

        $this->actingAs($this->hrUser())->post("/hr/employees/{$application->id}/activate", ['employee_id' => 'EMP-003', 'start_date' => now()->toDateString()])->assertSessionHasErrors('employee_id');
    }

    private function hiredApplicationWithPkwt(string $archiveStatus): Application
    {
        $application = Application::factory()->create(['status' => 'hired']);
        $application->load('candidate', 'jobPosting');
        PkwtContract::query()->create([
            'application_id' => $application->id,
            'entity_id' => $application->jobPosting->entity_id,
            'company_signer_id' => User::factory()->create(['is_active' => true])->id,
            'candidate_id' => $application->candidate_id,
            'position_name' => $application->jobPosting->position_name,
            'department' => 'HR',
            'work_location' => 'Site A',
            'contract_type' => 'contract',
            'start_date' => now()->toDateString(),
            'status' => 'signed',
            'archive_status' => $archiveStatus,
        ]);

        return $application;
    }

    private function employeePayload(array $overrides = []): array
    {
        $application = Application::factory()->create(['status' => 'hired']);
        $application->load('candidate', 'jobPosting');

        return array_merge([
            'application_id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'entity_id' => $application->jobPosting->entity_id,
            'department_id' => $application->jobPosting->department_id,
            'employee_id' => 'EMP-X',
            'full_name' => $application->candidate->name,
            'email' => $application->candidate->email,
            'position_name' => $application->jobPosting->position_name,
            'contract_type' => 'contract',
            'start_date' => now()->toDateString(),
            'status' => 'active',
            'activated_by' => User::factory()->create(['is_active' => true])->id,
            'activated_at' => now(),
        ], $overrides);
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }
}
