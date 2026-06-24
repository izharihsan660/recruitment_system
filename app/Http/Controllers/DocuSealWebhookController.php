<?php

namespace App\Http\Controllers;

use App\Jobs\HandleDocuSealWebhook;
use App\Services\DocuSealService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocuSealWebhookController extends Controller
{
    public function __invoke(Request $request, DocuSealService $docuSealService): Response
    {
        $signature = (string) ($request->header('X-Docuseal-Signature') ?: $request->header('Docuseal-Signature'));

        if (! $docuSealService->verifyWebhookSignature($request->getContent(), $signature)) {
            abort(403, 'Invalid DocuSeal signature.');
        }

        HandleDocuSealWebhook::dispatch($request->all());

        return response()->noContent();
    }
}
