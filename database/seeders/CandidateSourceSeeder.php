<?php

namespace Database\Seeders;

use App\Models\CandidateSource;
use Illuminate\Database\Seeder;

class CandidateSourceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Walk-in', 'LinkedIn', 'JobStreet', 'Instagram', 'Email Manual', 'Referral'] as $name) {
            CandidateSource::query()->updateOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
