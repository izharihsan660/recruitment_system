<?php

namespace Database\Factories;

use App\Models\DocusealConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocusealConfig>
 */
class DocusealConfigFactory extends Factory
{
    public function definition(): array
    {
        return [
            'api_url' => 'https://api.docuseal.com',
            'api_key' => 'docuseal-secret',
            'webhook_secret' => 'webhook-secret',
            'is_active' => true,
        ];
    }
}
