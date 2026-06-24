<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CandidateDocumentService
{
    public function upload(Candidate $candidate, Application $application, array $data, UploadedFile $file): CandidateDocument
    {
        $this->ensureCanUpload($candidate, $application);

        return CandidateDocument::query()->create([
            'candidate_id' => $candidate->id,
            'application_id' => $application->id,
            'document_type' => $data['document_type'],
            'file_path' => $file->store('documents/'.$candidate->id, 'public'),
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now(),
        ]);
    }

    public function delete(CandidateDocument $document): void
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
    }

    private function ensureCanUpload(Candidate $candidate, Application $application): void
    {
        if ($application->candidate_id !== $candidate->id) {
            abort(404);
        }

        if ($application->jobPosting->status === 'open') {
            return;
        }

        if (! in_array($application->status, ['rejected', 'withdrawn'], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'application' => 'Dokumen tidak bisa diunggah untuk lamaran ini.',
        ]);
    }
}
