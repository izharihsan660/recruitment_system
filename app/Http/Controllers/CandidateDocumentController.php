<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadCandidateDocumentRequest;
use App\Http\Resources\CandidateDocumentResource;
use App\Models\Application;
use App\Models\CandidateDocument;
use App\Services\CandidateDocumentService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CandidateDocumentController extends Controller
{
    public function __construct(private readonly CandidateDocumentService $candidateDocumentService) {}

    public function index(Application $application): AnonymousResourceCollection
    {
        abort_unless($application->candidate_id === request()->user('candidate')->id, 404);

        return CandidateDocumentResource::collection($application->documents()->latest()->get());
    }

    public function store(UploadCandidateDocumentRequest $request, Application $application): CandidateDocumentResource
    {
        $document = $this->candidateDocumentService->upload($request->user('candidate'), $application->load('jobPosting'), $request->validated(), $request->file('file'));

        return new CandidateDocumentResource($document);
    }

    public function destroy(Application $application, CandidateDocument $docId): Response
    {
        abort_unless($application->candidate_id === request()->user('candidate')->id, 404);
        abort_unless($docId->application_id === $application->id, 404);

        $this->candidateDocumentService->delete($docId);

        return response()->noContent();
    }
}
