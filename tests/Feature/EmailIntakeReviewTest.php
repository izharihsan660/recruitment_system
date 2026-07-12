<?php

namespace Tests\Feature;

use App\Models\EmailIntake;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailIntakeReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_assign_to_job_requires_consent_and_moves_candidate_directly_to_screening(): void
    {
        $hr = User::factory()->create()->assignRole(Roles::HrRecruiter);
        $intake = EmailIntake::factory()->create();
        $job = JobPosting::factory()->open()->create();

        $this->actingAs($hr)
            ->post("/hr/email-intake/{$intake->id}/assign-to-job", [
                'job_posting_id' => $job->id,
                'consent' => false,
            ])
            ->assertInvalid(['consent']);

        $this->actingAs($hr)
            ->post("/hr/email-intake/{$intake->id}/assign-to-job", [
                'job_posting_id' => $job->id,
                'consent' => true,
            ])
            ->assertRedirect(route('pipeline.index'));

        $this->assertDatabaseHas('applications', [
            'job_posting_id' => $job->id,
            'source' => 'email_intake',
            'status' => 'screening',
            'consent' => true,
        ]);
        $this->assertDatabaseHas('pipeline_logs', ['to_stage' => 'screening']);
    }

    public function test_move_to_talent_pool_requires_consent_and_reason(): void
    {
        $hr = User::factory()->create()->assignRole(Roles::HrRecruiter);
        $intake = EmailIntake::factory()->create();

        $this->actingAs($hr)
            ->post("/hr/email-intake/{$intake->id}/move-to-talent-pool", [
                'consent' => false,
                'notes' => '',
            ])
            ->assertInvalid(['consent', 'notes']);
    }

    public function test_reject_requires_reason_and_ignore_spam_remain_manual_actions(): void
    {
        $hr = User::factory()->create()->assignRole(Roles::HrRecruiter);
        $intake = EmailIntake::factory()->create();

        $this->actingAs($hr)->post("/hr/email-intake/{$intake->id}/reject", ['reason' => ''])->assertInvalid(['reason']);
        $this->assertSame('need_review', $intake->refresh()->status);

        $this->actingAs($hr)->post("/hr/email-intake/{$intake->id}/ignore")->assertRedirect();
        $this->assertSame('ignored', $intake->refresh()->status);

        $spam = EmailIntake::factory()->create();
        $this->actingAs($hr)->post("/hr/email-intake/{$spam->id}/spam")->assertRedirect();
        $this->assertSame('spam', $spam->refresh()->status);
    }
}
