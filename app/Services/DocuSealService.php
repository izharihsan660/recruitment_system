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
        $response = Http::withHeaders(['X-Auth-Token' => $this->apiKey()])
            ->acceptJson()
            ->post($this->apiBaseUrl().'/submissions', $data)
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
        $response = Http::withHeaders(['X-Auth-Token' => $this->apiKey()])
            ->acceptJson()
            ->get($this->apiBaseUrl().'/submissions/'.$submissionId)
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
        return Http::withHeaders(['X-Auth-Token' => $this->apiKey()])
            ->get($this->apiBaseUrl().'/submissions/'.$submissionId.'/documents/download')
            ->throw()
            ->body();
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.docuseal.webhook_secret') ?: $this->config()->webhook_secret;

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

    public static function normalizeApiBaseUrl(string $apiUrl): string
    {
        $baseUrl = rtrim($apiUrl, '/');

        return str_ends_with($baseUrl, '/api') ? $baseUrl : $baseUrl.'/api';
    }

    private function apiBaseUrl(): string
    {
        $apiUrl = config('services.docuseal.api_url') ?: $this->config()->api_url;

        if (blank($apiUrl)) {
            throw new \RuntimeException('DocuSeal API URL belum dikonfigurasi.');
        }

        return self::normalizeApiBaseUrl($apiUrl);
    }

    private function apiKey(): string
    {
        $apiKey = config('services.docuseal.api_key') ?: $this->config()->api_key;

        if (blank($apiKey)) {
            throw new \RuntimeException('DocuSeal API key belum dikonfigurasi.');
        }

        return $apiKey;
    }

    private function ghostscriptAvailable(): bool
    {
        $process = new Process(['which', 'gs']);
        $process->run();

        return $process->isSuccessful();
    }
}
