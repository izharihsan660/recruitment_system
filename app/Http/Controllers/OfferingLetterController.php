<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviseOfferingLetterRequest;
use App\Http\Requests\StoreOfferingLetterRequest;
use App\Models\Application;
use App\Services\OfferingLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OfferingLetterController extends Controller
{
    public function __construct(private readonly OfferingLetterService $offeringLetterService) {}

    public function show(Application $application): Response
    {
        return Inertia::render('Pipeline/Offering', [
            'application' => $application->load(['candidate', 'jobPosting.recruitmentRequest', 'offeringLetter.hrSigner']),
        ]);
    }

    public function store(StoreOfferingLetterRequest $request, Application $application): RedirectResponse
    {
        $this->offeringLetterService->create($application, $request->validated(), $request->user());

        return back()->with('success', 'Draft offering berhasil disimpan.');
    }

    public function update(StoreOfferingLetterRequest $request, Application $application): RedirectResponse
    {
        $this->offeringLetterService->create($application, $request->validated(), $request->user());

        return back()->with('success', 'Draft offering berhasil diperbarui.');
    }

    public function send(Application $application): RedirectResponse
    {
        $this->offeringLetterService->send($application->offeringLetter()->firstOrFail(), request()->user());

        return back()->with('success', 'Offering dikirim ke DocuSeal.');
    }

    public function revise(ReviseOfferingLetterRequest $request, Application $application): RedirectResponse
    {
        $this->offeringLetterService->revise($application->offeringLetter()->firstOrFail(), $request->validated(), $request->user());

        return back()->with('success', 'Offering direvisi dan dikirim ulang.');
    }

    public function preview(Application $application): HttpResponse
    {
        $path = $this->offeringLetterService->generatePdf($application->offeringLetter()->firstOrFail());

        return response(Storage::disk('local')->get($path), 200, ['Content-Type' => 'application/pdf']);
    }
}
