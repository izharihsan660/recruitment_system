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
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('123123'),
        ])->assignRole(Roles::Admin);

        User::factory()->create([
            'name' => 'HR Recruiter',
            'username' => 'hrrecruiter',
            'email' => 'hr@example.com',
            'password' => bcrypt('123123'),
        ])->assignRole(Roles::HrRecruiter);

        User::factory()->create([
            'name' => 'HR Manager',
            'username' => 'hrmanager',
            'email' => 'hrmanager@example.com',
            'password' => bcrypt('123123'),
        ])->assignRole(Roles::HrManager);

        User::factory()->create([
            'name' => 'Hiring Manager',
            'username' => 'hiringmanager',
            'email' => 'hiring@example.com',
            'password' => bcrypt('123123'),
        ])->assignRole(Roles::HiringManager);

        User::factory()->create([
            'name' => 'Approver',
            'username' => 'approver',
            'email' => 'approver@example.com',
            'password' => bcrypt('123123'),
        ])->assignRole(Roles::Approver);
    }
}
