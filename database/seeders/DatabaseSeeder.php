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
            DemoSeeder::class,
        ]);

        DocusealConfig::query()->firstOrCreate(
            ['api_url' => 'https://api.docuseal.com'],
            ['api_key' => '', 'webhook_secret' => null, 'is_active' => true],
        );

        User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email_verified_at' => now(),
                'password' => bcrypt('123123'),
                'is_active' => true,
            ],
        )->syncRoles(Roles::Admin);

        User::firstOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Recruiter',
                'username' => 'hrrecruiter',
                'email_verified_at' => now(),
                'password' => bcrypt('123123'),
                'is_active' => true,
            ],
        )->syncRoles(Roles::HrRecruiter);

        User::firstOrCreate(
            ['email' => 'hrmanager@example.com'],
            [
                'name' => 'HR Manager',
                'username' => 'hrmanager',
                'email_verified_at' => now(),
                'password' => bcrypt('123123'),
                'is_active' => true,
            ],
        )->syncRoles(Roles::HrManager);

        User::firstOrCreate(
            ['email' => 'hiring@example.com'],
            [
                'name' => 'Hiring Manager',
                'username' => 'hiringmanager',
                'email_verified_at' => now(),
                'password' => bcrypt('123123'),
                'is_active' => true,
            ],
        )->syncRoles(Roles::HiringManager);

        User::firstOrCreate(
            ['email' => 'approver@example.com'],
            [
                'name' => 'Approver',
                'username' => 'approver',
                'email_verified_at' => now(),
                'password' => bcrypt('123123'),
                'is_active' => true,
            ],
        )->syncRoles(Roles::Approver);
    }
}
