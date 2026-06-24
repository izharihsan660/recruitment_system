<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateDocument>
 */
class CandidateDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'candidate_id' => Candidate::factory(),
            'application_id' => Application::factory(),
            'document_type' => 'KTP',
            'file_path' => 'documents/test/ktp.pdf',
            'original_name' => 'ktp.pdf',
            'uploaded_at' => now(),
        ];
    }
}
