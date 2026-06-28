<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Entity;
use App\Models\PkwtContract;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
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

    public function test_employee_index_bisa_difilter_search_department_entity_dan_status(): void
    {
        $entity = Entity::factory()->create(['name' => 'PT Mineral Utama']);
        $department = Department::factory()->create(['entity_id' => $entity->id, 'name' => 'Human Capital']);
        $matched = Employee::query()->create($this->employeePayload([
            'entity_id' => $entity->id,
            'department_id' => $department->id,
            'employee_id' => 'EMP-FILTER-001',
            'full_name' => 'Budi Filter',
            'status' => 'inactive',
        ]));
        Employee::query()->create($this->employeePayload(['employee_id' => 'EMP-OTHER-001', 'full_name' => 'Siti Lain']));

        $this->actingAs($this->hrUser())
            ->get('/hr/employees?search=FILTER&department_id='.$department->id.'&entity_id='.$entity->id.'&status=inactive')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Employees/Index')
                ->where('employees.data.0.id', $matched->id)
                ->has('employees.data', 1)
                ->where('filters.search', 'FILTER')
                ->where('filters.department_id', (string) $department->id)
                ->where('filters.entity_id', (string) $entity->id)
                ->where('filters.status', 'inactive')
            );
    }

    public function test_employee_show_mengirim_pic_user_aktif_urut_nama(): void
    {
        $employee = Employee::query()->create($this->employeePayload());
        $zaki = User::factory()->create(['name' => 'Zaki PIC', 'is_active' => true]);
        $andi = User::factory()->create(['name' => 'Andi PIC', 'is_active' => true]);
        User::factory()->create(['name' => 'Nonaktif PIC', 'is_active' => false]);

        $this->actingAs($this->hrUser())
            ->get("/hr/employees/{$employee->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Employees/Show')
                ->where('users', fn ($users): bool => collect($users)
                    ->whereIn('id', [$andi->id, $zaki->id])
                    ->pluck('name')
                    ->values()
                    ->all() === ['Andi PIC', 'Zaki PIC'])
                ->missing('users.0.email')
            );
    }

    public function test_employee_update_hanya_mengubah_field_editable(): void
    {
        $employee = Employee::query()->create($this->employeePayload(['employee_id' => 'EMP-OLD']));

        $this->actingAs($this->hrUser())
            ->put("/hr/employees/{$employee->id}", [
                'employee_id' => 'EMP-NEW',
                'start_date' => now()->addDay()->toDateString(),
                'end_date' => now()->addMonth()->toDateString(),
                'contract_type' => 'permanent',
                'status' => 'inactive',
            ])
            ->assertRedirect("/hr/employees/{$employee->id}")
            ->assertSessionHas('success', 'Data karyawan berhasil diperbarui.');

        $employee->refresh();

        $this->assertSame('EMP-NEW', $employee->employee_id);
        $this->assertSame('permanent', $employee->contract_type);
        $this->assertSame('inactive', $employee->status);
        $this->assertSame(now()->addDay()->toDateString(), $employee->start_date->toDateString());
        $this->assertSame(now()->addMonth()->toDateString(), $employee->end_date?->toDateString());
    }

    public function test_employee_update_memvalidasi_tanggal_selesai_setelah_tanggal_mulai(): void
    {
        $employee = Employee::query()->create($this->employeePayload());

        $this->actingAs($this->hrUser())
            ->put("/hr/employees/{$employee->id}", [
                'employee_id' => 'EMP-INVALID',
                'start_date' => now()->toDateString(),
                'end_date' => now()->subDay()->toDateString(),
                'contract_type' => 'contract',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('end_date');
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
