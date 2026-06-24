<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePkwtContractRequest;
use App\Models\Application;
use App\Services\PkwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PkwtController extends Controller
{
    public function __construct(private readonly PkwtService $pkwtService) {}

    public function show(Application $application): Response
    {
        return Inertia::render('Pipeline/Pkwt', [
            'application' => $application->load(['candidate', 'jobPosting', 'offeringLetter', 'pkwtContract.companySigner']),
        ]);
    }

    public function store(Application $application): RedirectResponse
    {
        $this->pkwtService->create($application, request()->user());

        return back()->with('success', 'Draft PKWT berhasil dibuat.');
    }

    public function update(UpdatePkwtContractRequest $request, Application $application): RedirectResponse
    {
        $this->pkwtService->update($application->pkwtContract()->firstOrFail(), $request->validated());

        return back()->with('success', 'PKWT berhasil diperbarui.');
    }

    public function send(Application $application): RedirectResponse
    {
        $this->pkwtService->send($application->pkwtContract()->firstOrFail(), request()->user());

        return back()->with('success', 'PKWT dikirim ke DocuSeal.');
    }

    public function preview(Application $application): HttpResponse
    {
        $path = $this->pkwtService->generatePdf($application->pkwtContract()->firstOrFail());

        return response(Storage::disk('local')->get($path), 200, ['Content-Type' => 'application/pdf']);
    }
}
