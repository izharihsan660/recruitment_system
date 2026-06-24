<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadCandidateDocumentRequest;
use App\Http\Resources\CandidateDocumentResource;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Services\CandidateDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CandidateDocumentController extends Controller
{
    public function __construct(private readonly CandidateDocumentService $candidateDocumentService) {}

    public function store(UploadCandidateDocumentRequest $request, Application $application): RedirectResponse|JsonResponse
    {
        $document = $this->candidateDocumentService->upload($request->user('candidate'), $application->load('jobPosting'), $request->validated(), $request->file('file'));

        if ($request->expectsJson()) {
            return response()->json([
                'data' => CandidateDocumentResource::make($document)->resolve(),
            ], 201);
        }

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function destroy(Application $application, CandidateDocument $docId): RedirectResponse
    {
        abort_unless($application->candidate_id === request()->user('candidate')->id, 404);
        abort_unless($docId->application_id === $application->id, 404);

        $this->candidateDocumentService->delete($docId);

        return back()->with('success', 'Dokumen berhasil dihapus.');
    }
}
