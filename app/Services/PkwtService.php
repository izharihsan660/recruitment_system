<?php

namespace App\Services;

use App\Jobs\ArchiveDocumentToSharePoint;
use App\Models\Application;
use App\Models\CompanySigner;
use App\Models\PkwtContract;
use App\Models\User;
use App\Notifications\HrDocumentSignedNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PkwtService
{
    public function __construct(private readonly DocuSealService $docuSealService) {}

    public function create(Application $app, User $actor): PkwtContract
    {
        if ($app->status !== 'pkwt') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage PKWT.']);
        }

        $app->loadMissing(['offeringLetter', 'jobPosting.department', 'jobPosting.entity', 'candidate']);

        if (! $app->offeringLetter || $app->offeringLetter->status !== 'signed') {
            throw ValidationException::withMessages(['offering' => 'Offering harus signed sebelum PKWT dibuat.']);
        }

        $signer = CompanySigner::query()
            ->where('entity_id', $app->jobPosting->entity_id)
            ->where('document_type', 'pkwt')
            ->where('is_active', true)
            ->firstOrFail();

        return PkwtContract::query()->firstOrCreate(
            ['application_id' => $app->id],
            [
                'entity_id' => $app->jobPosting->entity_id,
                'company_signer_id' => $signer->user_id,
                'candidate_id' => $app->candidate_id,
                'position_name' => $app->offeringLetter->position_name,
                'department' => $app->offeringLetter->department,
                'work_location' => $app->offeringLetter->work_location,
                'contract_type' => $app->offeringLetter->contract_type,
                'start_date' => $app->offeringLetter->start_date,
                'contract_duration' => $app->offeringLetter->contract_duration,
                'salary_gross' => $app->offeringLetter->salary_gross,
                'salary_nett' => $app->offeringLetter->salary_nett,
                'allowances' => $app->offeringLetter->allowances,
                'status' => 'draft',
            ]
        );
    }

    public function update(PkwtContract $pkwt, array $data): PkwtContract
    {
        $pkwt->update($data + ['pdf_path' => null]);

        return $pkwt->refresh();
    }

    public function generatePdf(PkwtContract $pkwt): string
    {
        $pkwt->loadMissing(['application.candidate', 'entity', 'companySigner']);
        $path = 'documents/pkwt/pkwt-'.$pkwt->id.'.pdf';
        Storage::disk('local')->put($path, Pdf::loadView('documents.pkwt', ['pkwt' => $pkwt])->output());
        $input = Storage::disk('local')->path($path);
        $compressed = Storage::disk('local')->path('documents/pkwt/pkwt-'.$pkwt->id.'-compressed.pdf');
        if ($this->docuSealService->compressPdf($input, $compressed)) {
            Storage::disk('local')->put($path, file_get_contents($compressed));
        }
        $pkwt->update(['pdf_path' => $path]);

        return $path;
    }

    public function send(PkwtContract $pkwt, User $actor): void
    {
        $pkwt->loadMissing(['application.candidate', 'companySigner']);
        $path = $pkwt->pdf_path ?: $this->generatePdf($pkwt);
        $submission = $this->docuSealService->createSubmission([
            'template_id' => null,
            'send_email' => true,
            'submitters' => [
                ['role' => 'Candidate', 'email' => $pkwt->application->candidate->email, 'name' => $pkwt->application->candidate->name],
                ['role' => 'Company Signer', 'email' => $pkwt->companySigner->email, 'name' => $pkwt->companySigner->name],
            ],
            'message' => ['subject' => 'Kontrak Kerja PKWT', 'body' => 'Silakan tanda tangani kontrak kerja.'],
            'metadata' => ['type' => 'pkwt', 'pkwt_contract_id' => $pkwt->id],
            'documents' => [['name' => basename($path), 'file' => base64_encode(Storage::disk('local')->get($path))]],
        ]);

        $pkwt->update([
            'docuseal_submission_id' => $submission['id'],
            'candidate_signing_url' => $this->signingUrl($submission, 'Candidate'),
            'company_signing_url' => $this->signingUrl($submission, 'Company Signer'),
            'status' => 'sent',
        ]);
    }

    public function handleWebhook(array $payload): void
    {
        $pkwt = $this->findFromPayload($payload);
        $event = (string) data_get($payload, 'event');

        if ($event === 'submission.completed') {
            DB::transaction(function () use ($pkwt): void {
                $binary = $this->docuSealService->downloadSignedDocument($pkwt->docuseal_submission_id);
                $path = 'documents/pkwt/pkwt-signed-'.$pkwt->id.'.pdf';
                Storage::disk('local')->put($path, $binary);
                $input = Storage::disk('local')->path($path);
                $compressed = Storage::disk('local')->path('documents/pkwt/pkwt-signed-'.$pkwt->id.'-compressed.pdf');
                if ($this->docuSealService->compressPdf($input, $compressed)) {
                    Storage::disk('local')->put($path, file_get_contents($compressed));
                }
                $pkwt->update(['status' => 'signed', 'signed_at' => now(), 'pdf_path' => $path]);
                $pkwt->application->update(['status' => 'hired']);
                ArchiveDocumentToSharePoint::dispatch($pkwt);
                $pkwt->companySigner->notify((new HrDocumentSignedNotification($pkwt))->afterCommit());
            });
        }

        if ($event === 'form.viewed') {
            $pkwt->update(['status' => 'partially_signed']);
        }
    }

    private function findFromPayload(array $payload): PkwtContract
    {
        $id = data_get($payload, 'metadata.pkwt_contract_id');

        return $id ? PkwtContract::query()->findOrFail($id) : PkwtContract::query()->where('docuseal_submission_id', data_get($payload, 'submission.id', data_get($payload, 'submission_id')))->firstOrFail();
    }

    private function signingUrl(array $submission, string $role): ?string
    {
        return collect($submission['submitters'])->firstWhere('role', $role)['embed_src']
            ?? collect($submission['submitters'])->firstWhere('role', $role)['signing_url']
            ?? null;
    }
}
