<?php

namespace Database\Seeders;

use App\Models\DocusealConfig;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            EntitySeeder::class,
            DepartmentSeeder::class,
            CandidateSourceSeeder::class,
            CompanyProfileSeeder::class,
        ]);

        DocusealConfig::query()->firstOrCreate(
            ['api_url' => 'https://api.docuseal.com'],
            ['api_key' => '', 'webhook_secret' => null, 'is_active' => true],
        );

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ])->assignRole(Roles::Admin);
    }
}
