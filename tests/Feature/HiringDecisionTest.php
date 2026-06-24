<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HiringDecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_approved_memindahkan_aplikasi_ke_pkwt(): void
    {
        $application = Application::factory()->create(['status' => 'hiring_decision']);

        $this->actingAs($this->hrUser())->post("/hr/hiring-decision/{$application->id}", [
            'decision' => 'approved',
            'notes' => 'Disetujui',
        ])->assertRedirect();

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'pkwt']);
        $this->assertDatabaseHas('hiring_decisions', ['application_id' => $application->id, 'decision' => 'approved']);
    }

    public function test_submit_rejected_tanpa_alasan_ditolak_validasi(): void
    {
        $application = Application::factory()->create(['status' => 'hiring_decision']);

        $this->actingAs($this->hrUser())->postJson("/hr/hiring-decision/{$application->id}", [
            'decision' => 'rejected',
            'reason' => '',
        ])->assertUnprocessable()->assertJsonValidationErrors('reason');
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }
}
