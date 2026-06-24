<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\SubjectTextNotification;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class McuSimperTest extends TestCase
{
    use RefreshDatabase;

    public function test_jadwal_mcu_mengirim_notifikasi_email_ke_kandidat(): void
    {
        Notification::fake();
        $application = $this->application(['mcu_required' => true], 'mcu_simper');

        $this->actingAs($this->hrUser())->post("/hr/mcu-simper/{$application->id}/schedule-mcu", [
            'mcu_scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'mcu_location' => 'Klinik Utama',
        ])->assertRedirect();

        Notification::assertSentTo($application->candidate, SubjectTextNotification::class);
        $this->assertDatabaseHas('mcu_simper_records', ['application_id' => $application->id, 'mcu_status' => 'pending']);
    }

    public function test_upload_mcu_passed_dan_simper_not_required_bisa_proceed_ke_hiring_decision(): void
    {
        Storage::fake('local');
        $application = $this->application(['mcu_required' => true, 'simper_required' => false], 'mcu_simper');
        $user = $this->hrUser();

        $this->actingAs($user)->post("/hr/mcu-simper/{$application->id}")->assertRedirect();
        $this->actingAs($user)->post("/hr/mcu-simper/{$application->id}/result-mcu", [
            'result_file' => UploadedFile::fake()->create('mcu.pdf', 100, 'application/pdf'),
            'status' => 'passed',
            'notes' => 'Fit to work',
        ])->assertRedirect();
        $this->actingAs($user)->post("/hr/mcu-simper/{$application->id}/proceed")->assertRedirect();

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'hiring_decision']);
    }

    public function test_upload_mcu_failed_membuat_aplikasi_rejected(): void
    {
        Storage::fake('local');
        $application = $this->application(['mcu_required' => true], 'mcu_simper');
        $user = $this->hrUser();

        $this->actingAs($user)->post("/hr/mcu-simper/{$application->id}/result-mcu", [
            'result_file' => UploadedFile::fake()->create('mcu.pdf', 100, 'application/pdf'),
            'status' => 'failed',
            'notes' => 'Tidak fit',
            'rejection_reason' => 'Tidak lulus MCU',
        ])->assertRedirect();

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'rejected']);
    }

    private function application(array $jobOverrides, string $status): Application
    {
        return Application::factory()->for(JobPosting::factory()->open()->state($jobOverrides))->create(['status' => $status]);
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }
}
