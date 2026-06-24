<?php

namespace Tests\Feature;

use App\Mail\CandidatePortalCredentialsMail;
use App\Models\Candidate;
use App\Models\CandidateSource;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HrInputCandidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_input_new_candidate_to_job_creates_candidate_application_and_sends_credentials(): void
    {
        Mail::fake();

        $hr = $this->hrUser();
        $job = JobPosting::factory()->open()->create();
        $source = CandidateSource::factory()->create(['name' => 'LinkedIn']);

        $this->actingAs($hr)->postJson('/hr/candidates/input-to-job', [
            'job_posting_id' => $job->id,
            'name' => 'Candidate Baru',
            'email' => 'candidate-baru@example.com',
            'source_id' => $source->id,
            'consent' => true,
        ])->assertSuccessful()->assertJsonPath('data.status', 'applied');

        $candidate = Candidate::query()->where('email', 'candidate-baru@example.com')->first();

        $this->assertNotNull($candidate);
        $this->assertDatabaseHas('applications', [
            'job_posting_id' => $job->id,
            'candidate_id' => $candidate->id,
            'source' => 'hr_input',
            'status' => 'applied',
        ]);
        Mail::assertSent(CandidatePortalCredentialsMail::class, fn (CandidatePortalCredentialsMail $mail) => $mail->candidate->is($candidate));
    }

    public function test_hr_input_existing_candidate_creates_new_application_without_duplicate_candidate(): void
    {
        Mail::fake();

        $hr = $this->hrUser();
        $job = JobPosting::factory()->open()->create();
        $source = CandidateSource::factory()->create(['name' => 'Walk-in']);
        $candidate = Candidate::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($hr)->postJson('/hr/candidates/input-to-job', [
            'job_posting_id' => $job->id,
            'name' => 'Should Be Ignored',
            'email' => 'existing@example.com',
            'source_id' => $source->id,
            'consent' => true,
        ])->assertSuccessful();

        $this->assertSame(1, Candidate::query()->where('email', 'existing@example.com')->count());
        $this->assertDatabaseHas('applications', [
            'candidate_id' => $candidate->id,
            'job_posting_id' => $job->id,
            'source' => 'hr_input',
        ]);
        Mail::assertNothingSent();
    }

    public function test_hr_input_referral_without_referral_name_is_rejected(): void
    {
        $hr = $this->hrUser();
        $job = JobPosting::factory()->open()->create();
        $referral = CandidateSource::factory()->create(['name' => 'Referral']);

        $this->actingAs($hr)->postJson('/hr/candidates/input-to-job', [
            'job_posting_id' => $job->id,
            'name' => 'Referral Candidate',
            'email' => 'referral@example.com',
            'source_id' => $referral->id,
            'referral_department' => 'HR',
            'referral_phone' => '0812345',
            'referral_relation' => 'Teman',
            'consent' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['referral_name']);
    }

    public function test_hr_input_to_talent_pool_creates_talent_pool_without_application(): void
    {
        Mail::fake();

        $hr = $this->hrUser();

        $this->actingAs($hr)->postJson('/hr/candidates/input-to-talent-pool', [
            'name' => 'Talent Pool Candidate',
            'email' => 'tp@example.com',
            'status' => 'active',
            'consent' => true,
        ])->assertSuccessful();

        $candidate = Candidate::query()->where('email', 'tp@example.com')->first();
        $this->assertNotNull($candidate);
        $this->assertDatabaseHas('talent_pools', ['candidate_id' => $candidate->id, 'status' => 'active']);
        $this->assertDatabaseMissing('applications', ['candidate_id' => $candidate->id]);
        Mail::assertSent(CandidatePortalCredentialsMail::class);
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }
}
