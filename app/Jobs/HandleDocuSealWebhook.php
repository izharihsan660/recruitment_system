<?php

namespace App\Jobs;

use App\Services\OfferingLetterService;
use App\Services\PkwtService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class HandleDocuSealWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $payload) {}

    public function handle(OfferingLetterService $offeringLetterService, PkwtService $pkwtService): void
    {
        $type = data_get($this->payload, 'metadata.type')
            ?: data_get($this->payload, 'submission.metadata.type');

        if ($type === 'offering') {
            $offeringLetterService->handleWebhook($this->payload);
        }

        if ($type === 'pkwt') {
            $pkwtService->handleWebhook($this->payload);
        }
    }
}
