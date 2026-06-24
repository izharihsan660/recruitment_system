<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CompanyProfile;
use App\Models\JobPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidatePortalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_portal_pages_render_open_jobs(): void
    {
        CompanyProfile::factory()->create(['company_name' => 'PT Rekrutmen Nusantara']);
        $job = JobPosting::factory()->open()->create(['position_name' => 'Staff HR']);

        $this->get('/portal')
            ->assertOk()
            ->assertSee('PT Rekrutmen Nusantara')
            ->assertSee('Staff HR');

        $this->get('/portal/jobs')
            ->assertOk()
            ->assertSee('Staff HR');

        $this->get('/portal/jobs/'.$job->id)
            ->assertOk()
            ->assertSee('Lamar Sekarang');
    }

    public function test_candidate_can_view_apply_page_and_submit_with_cv(): void
    {
        CompanyProfile::factory()->create();
        $candidate = Candidate::factory()->withCv()->create();
        $job = JobPosting::factory()->open()->create();

        $this->actingAs($candidate, 'candidate')
            ->get('/candidate/jobs/'.$job->id.'/apply')
            ->assertOk();

        $this->actingAs($candidate, 'candidate')
            ->post('/candidate/jobs/'.$job->id.'/apply', ['consent' => true])
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'candidate_id' => $candidate->id,
            'job_posting_id' => $job->id,
            'source' => 'portal',
        ]);
    }
}
