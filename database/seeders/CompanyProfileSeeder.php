<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        CompanyProfile::query()->firstOrCreate([], [
            'company_name' => '',
            'about' => '',
            'values' => [],
            'gallery' => [],
        ]);
    }
}
