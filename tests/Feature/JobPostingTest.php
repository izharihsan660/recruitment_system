<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_fpk_approved_can_create_job_posting_open_and_show_in_portal(): void
    {
        $hr = $this->hrUser();
        $fpk = RecruitmentRequest::factory()->create(['status' => 'approved']);

        $this->actingAs($hr)->post(route('job-postings.store'), [
            'recruitment_request_id' => $fpk->id,
            'requirements' => 'Minimal SMA.',
            'mcu_required' => true,
        ])->assertRedirect()->assertSessionHas('success', 'Job Posting berhasil dibuat.');

        $job = JobPosting::query()->where('recruitment_request_id', $fpk->id)->firstOrFail();

        $this->actingAs($hr)->post(route('job-postings.open', $job))->assertRedirect()->assertSessionHas('success', 'Aksi berhasil dijalankan.');

        $this->getJson(route('portal.jobs.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $job->id);
    }

    public function test_fpk_not_approved_cannot_create_job_posting(): void
    {
        $hr = $this->hrUser();
        $fpk = RecruitmentRequest::factory()->create(['status' => 'draft']);

        $this->actingAs($hr)->postJson(route('job-postings.store'), [
            'recruitment_request_id' => $fpk->id,
            'requirements' => 'Minimal SMA.',
        ])->assertUnprocessable();
    }

    public function test_job_posting_open_candidate_can_apply_with_consent_and_status_applied(): void
    {
        $job = JobPosting::factory()->open()->create();
        $candidate = Candidate::factory()->withCv()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.jobs.apply', $job), [
            'consent' => true,
        ])->assertCreated()->assertJsonPath('data.status', 'applied');
    }

    public function test_candidate_cannot_apply_same_job_twice(): void
    {
        $job = JobPosting::factory()->open()->create();
        $candidate = Candidate::factory()->withCv()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.jobs.apply', $job), ['consent' => true])->assertCreated();
        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.jobs.apply', $job), ['consent' => true])->assertUnprocessable();
    }

    public function test_candidate_cannot_apply_without_cv(): void
    {
        $job = JobPosting::factory()->open()->create();
        $candidate = Candidate::factory()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.jobs.apply', $job), ['consent' => true])->assertUnprocessable();
    }

    public function test_candidate_cannot_apply_without_consent(): void
    {
        $job = JobPosting::factory()->open()->create();
        $candidate = Candidate::factory()->withCv()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.jobs.apply', $job), ['consent' => false])->assertUnprocessable();
    }

    public function test_candidate_cannot_apply_closed_job(): void
    {
        $job = JobPosting::factory()->create(['status' => 'closed']);
        $candidate = Candidate::factory()->withCv()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.jobs.apply', $job), ['consent' => true])->assertUnprocessable();
    }

    public function test_active_pipeline_candidate_can_upload_document_when_job_closed(): void
    {
        Storage::fake('public');
        $job = JobPosting::factory()->create(['status' => 'closed']);
        $candidate = Candidate::factory()->withCv()->create();
        $application = Application::factory()->for($job)->for($candidate)->create(['status' => 'screening']);

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.applications.documents.store', $application), [
            'document_type' => 'KTP',
            'file' => UploadedFile::fake()->image('ktp.jpg'),
        ])->assertCreated()->assertJsonPath('data.document_type', 'KTP');
    }

    private function hrUser(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Roles::HrRecruiter);

        return $user;
    }
}
