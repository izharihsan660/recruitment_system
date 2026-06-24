<?php

namespace App\Services;

use App\Models\Candidate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CandidateAuthService
{
    public function register(array $data): Candidate
    {
        return Candidate::query()->create($data);
    }

    public function updateProfile(Candidate $candidate, array $data): Candidate
    {
        $candidate->update($data);

        return $candidate;
    }

    public function replaceCv(Candidate $candidate, UploadedFile $file): Candidate
    {
        if ($candidate->cv_path) {
            Storage::disk('public')->delete($candidate->cv_path);
        }

        $candidate->update([
            'cv_path' => $file->store('cv/'.$candidate->id, 'public'),
            'cv_original_name' => $file->getClientOriginalName(),
        ]);

        return $candidate;
    }
}
