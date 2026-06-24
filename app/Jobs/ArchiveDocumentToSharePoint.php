<?php

namespace App\Jobs;

use App\Models\OfferingLetter;
use App\Models\PkwtContract;
use App\Notifications\SimpleTextNotification;
use App\Services\SharePointService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ArchiveDocumentToSharePoint implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public OfferingLetter|PkwtContract $document) {}

    public function handle(SharePointService $sharePointService): void
    {
        $this->document->loadMissing(['application.candidate', 'entity']);
        $url = $sharePointService->archiveDocument(Storage::disk('local')->path($this->document->pdf_path), [
            'doc_type' => $this->document instanceof OfferingLetter ? 'Offering' : 'PKWT',
            'entity_name' => $this->document->entity->name,
            'candidate_name' => $this->document->application->candidate->name,
            'position_name' => $this->document->position_name,
            'signed_date' => optional($this->document->signed_at)->format('Y-m-d') ?: now()->format('Y-m-d'),
        ]);

        $this->document->update([
            'sharepoint_url' => $url,
            'archive_status' => 'archived',
            'archive_attempted_at' => now(),
            'archive_error' => null,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $this->document->update([
            'archive_status' => 'failed',
            'archive_attempted_at' => now(),
            'archive_error' => $exception->getMessage(),
        ]);

        $signer = $this->document instanceof OfferingLetter ? $this->document->hrSigner : $this->document->companySigner;
        Notification::send($signer, new SimpleTextNotification('Arsip dokumen ke SharePoint gagal: '.$exception->getMessage()));
    }
}
