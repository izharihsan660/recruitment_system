<?php

namespace Tests\Feature;

use App\Models\Candidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CandidateAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_register_login_and_access_profile(): void
    {
        $this->postJson(route('candidate.register'), [
            'name' => 'Budi Kandidat',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('data.email', 'budi@example.com');

        $this->postJson(route('candidate.logout'))->assertNoContent();

        $this->postJson(route('candidate.login'), [
            'email' => 'budi@example.com',
            'password' => 'password123',
        ])->assertOk();

        $this->getJson(route('candidate.profile'))->assertOk()->assertJsonPath('data.email', 'budi@example.com');
    }

    public function test_candidate_can_upload_valid_cv(): void
    {
        Storage::fake('public');
        $candidate = Candidate::factory()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.cv.store'), [
            'cv' => UploadedFile::fake()->create('cv.pdf', 128, 'application/pdf'),
        ])->assertOk()->assertJsonPath('data.has_cv', true);
    }

    public function test_candidate_cv_upload_rejects_non_pdf(): void
    {
        Storage::fake('public');
        $candidate = Candidate::factory()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.cv.store'), [
            'cv' => UploadedFile::fake()->image('cv.jpg'),
        ])->assertUnprocessable()->assertJsonValidationErrors('cv');
    }

    public function test_candidate_cv_upload_rejects_files_larger_than_two_mb(): void
    {
        Storage::fake('public');
        $candidate = Candidate::factory()->create();

        $this->actingAs($candidate, 'candidate')->postJson(route('candidate.cv.store'), [
            'cv' => UploadedFile::fake()->create('cv.pdf', 2049, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('cv');
    }
}
