<?php

namespace Tests\Unit;

use App\Services\DocuSealService;
use PHPUnit\Framework\TestCase;

class DocuSealServiceTest extends TestCase
{
    public function test_it_normalizes_self_hosted_api_url_without_api_path(): void
    {
        $this->assertSame(
            'http://145.79.12.57:3000/api',
            DocuSealService::normalizeApiBaseUrl('http://145.79.12.57:3000'),
        );
    }

    public function test_it_keeps_existing_api_path_without_duplication(): void
    {
        $this->assertSame(
            'http://145.79.12.57:3000/api',
            DocuSealService::normalizeApiBaseUrl('http://145.79.12.57:3000/api/'),
        );
    }
}
