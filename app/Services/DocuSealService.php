<?php

namespace App\Services;

use App\Models\DocusealConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DocuSealService
{
    public function createSubmission(array $data): array
    {
        $response = Http::withHeaders(['X-Auth-Token' => $this->config()->api_key])
            ->acceptJson()
            ->post($this->baseUrl().'/submissions', $data)
            ->throw()
            ->json();

        return [
            'id' => (string) data_get($response, 'id', data_get($response, 'submission.id')),
            'submitters' => data_get($response, 'submitters', []),
            'raw' => $response,
        ];
    }

    public function getSubmissionStatus(string $submissionId): array
    {
        $response = Http::withHeaders(['X-Auth-Token' => $this->config()->api_key])
            ->acceptJson()
            ->get($this->baseUrl().'/submissions/'.$submissionId)
            ->throw()
            ->json();

        return [
            'status' => data_get($response, 'status'),
            'signed_at' => data_get($response, 'completed_at'),
            'document_url' => data_get($response, 'documents.0.url'),
            'raw' => $response,
        ];
    }

    public function downloadSignedDocument(string $submissionId): string
    {
        return Http::withHeaders(['X-Auth-Token' => $this->config()->api_key])
            ->get($this->baseUrl().'/submissions/'.$submissionId.'/documents/download')
            ->throw()
            ->body();
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = $this->config()->webhook_secret;

        if (blank($secret) || blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);
        $normalized = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;

        return hash_equals($expected, $normalized);
    }

    public function compressPdf(string $inputPath, string $outputPath): bool
    {
        if (! $this->ghostscriptAvailable()) {
            Log::warning('Ghostscript tidak tersedia, PDF digunakan tanpa kompresi.', ['input' => $inputPath]);

            return false;
        }

        $process = new Process([
            'gs', '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.4', '-dPDFSETTINGS=/ebook',
            '-dNOPAUSE', '-dQUIET', '-dBATCH', '-sOutputFile='.$outputPath, $inputPath,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::warning('Kompresi PDF gagal, PDF original digunakan.', ['error' => $process->getErrorOutput()]);

            return false;
        }

        return true;
    }

    private function config(): DocusealConfig
    {
        return DocusealConfig::query()->active()->firstOrFail();
    }

    private function baseUrl(): string
    {
        $apiUrl = $this->config()->api_url;

        if (blank($apiUrl)) {
            throw new \RuntimeException('DocuSeal API URL belum dikonfigurasi.');
        }

        return rtrim($apiUrl, '/');
    }

    private function ghostscriptAvailable(): bool
    {
        $process = new Process(['which', 'gs']);
        $process->run();

        return $process->isSuccessful();
    }
}
