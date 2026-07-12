<?php

namespace App\Services;

use App\Jobs\ArchiveDocumentToSharePoint;
use App\Models\Application;
use App\Models\CompanySigner;
use App\Models\OfferingLetter;
use App\Models\User;
use App\Notifications\HrDocumentSignedNotification;
use App\Notifications\SimpleTextNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OfferingLetterService
{
    public function __construct(
        private readonly PipelineService $pipelineService,
        private readonly DocuSealService $docuSealService,
    ) {}

    public function create(Application $app, array $data, User $actor): OfferingLetter
    {
        if ($app->status !== 'offering') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage Offering.']);
        }

        $app->loadMissing(['backgroundCheck', 'jobPosting.department', 'jobPosting.entity', 'jobPosting.recruitmentRequest', 'candidate']);

        if ($app->backgroundCheck?->decision !== 'clear') {
            throw ValidationException::withMessages(['background_check' => 'Background check harus clear sebelum offering.']);
        }

        $signer = CompanySigner::query()
            ->where('entity_id', $app->jobPosting->entity_id)
            ->where('document_type', 'offering')
            ->where('is_active', true)
            ->firstOrFail();

        return OfferingLetter::query()->updateOrCreate(
            ['application_id' => $app->id],
            array_merge($data, [
                'entity_id' => $app->jobPosting->entity_id,
                'hr_signer_id' => $signer->user_id,
                'position_name' => $app->jobPosting->position_name,
                'department' => $app->jobPosting->department->name,
                'work_location' => $app->jobPosting->work_location,
                'contract_type' => $app->jobPosting->employment_status,
                'status' => 'draft',
            ])
        );
    }

    public function generatePdf(OfferingLetter $offering): string
    {
        $offering->loadMissing(['application.candidate', 'entity', 'hrSigner']);
        $path = 'documents/offering/offering-'.$offering->id.'.pdf';
        Storage::disk('local')->put($path, Pdf::loadView('documents.offering-letter', ['offering' => $offering])->output());
        $offering->update(['pdf_path' => $path]);

        return $path;
    }

    public function send(OfferingLetter $offering, User $actor): void
    {
        $offering->loadMissing(['application.candidate', 'hrSigner']);
        $path = $offering->pdf_path ?: $this->generatePdf($offering);
        $submission = $this->docuSealService->createSubmission([
            'template_id' => null,
            'send_email' => true,
            'submitters' => [
                ['role' => 'HR Signer', 'email' => $offering->hrSigner->email, 'name' => $offering->hrSigner->name],
                ['role' => 'Candidate', 'email' => $offering->application->candidate->email, 'name' => $offering->application->candidate->name],
            ],
            'message' => ['subject' => 'Offering Letter', 'body' => 'Silakan tanda tangani offering letter.'],
            'metadata' => ['type' => 'offering', 'offering_letter_id' => $offering->id],
            'documents' => [['name' => basename($path), 'file' => base64_encode(Storage::disk('local')->get($path))]],
        ]);

        $offering->update([
            'docuseal_submission_id' => $submission['id'],
            'hr_signing_url' => $this->signingUrl($submission, 'HR Signer'),
            'candidate_signing_url' => $this->signingUrl($submission, 'Candidate'),
            'status' => 'sent',
        ]);

        Notification::send([$offering->hrSigner, $offering->application->candidate], new SimpleTextNotification('Offering letter dikirim untuk ditandatangani.'));
    }

    public function handleWebhook(array $payload): void
    {
        $offering = $this->findFromPayload($payload);
        $event = (string) data_get($payload, 'event');

        if ($event === 'submission.completed') {
            DB::transaction(function () use ($offering): void {
                $binary = $this->docuSealService->downloadSignedDocument($offering->docuseal_submission_id);
                $path = 'documents/offering/offering-signed-'.$offering->id.'.pdf';
                Storage::disk('local')->put($path, $binary);
                $input = Storage::disk('local')->path($path);
                $compressed = Storage::disk('local')->path('documents/offering/offering-signed-'.$offering->id.'-compressed.pdf');
                if ($this->docuSealService->compressPdf($input, $compressed)) {
                    Storage::disk('local')->put($path, file_get_contents($compressed));
                }
                $offering->update(['status' => 'signed', 'signed_at' => now(), 'pdf_path' => $path]);
                ArchiveDocumentToSharePoint::dispatch($offering);
                $this->pipelineService->moveToNextStage($offering->application, $offering->hrSigner);
                $offering->hrSigner->notify((new HrDocumentSignedNotification($offering))->afterCommit());
            });
        }

        if ($event === 'submission.expired') {
            $offering->update(['status' => 'expired']);
        }
    }

    public function negotiate(OfferingLetter $offering, string $notes): void
    {
        $offering->update(['status' => 'negotiation', 'negotiation_notes' => $notes]);
    }

    public function revise(OfferingLetter $offering, array $data, User $actor): void
    {
        $offering->update(array_merge($data, ['status' => 'draft', 'pdf_path' => null]));
        $this->generatePdf($offering->refresh());
        $this->send($offering->refresh(), $actor);
    }

    private function findFromPayload(array $payload): OfferingLetter
    {
        $id = data_get($payload, 'metadata.offering_letter_id');

        return $id ? OfferingLetter::query()->findOrFail($id) : OfferingLetter::query()->where('docuseal_submission_id', data_get($payload, 'submission.id', data_get($payload, 'submission_id')))->firstOrFail();
    }

    private function signingUrl(array $submission, string $role): ?string
    {
        return collect($submission['submitters'])->firstWhere('role', $role)['embed_src']
            ?? collect($submission['submitters'])->firstWhere('role', $role)['signing_url']
            ?? null;
    }
}
