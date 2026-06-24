<?php

namespace Database\Seeders;

use App\Models\Entity;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        Entity::query()->updateOrCreate(
            ['short_name' => 'NAJ'],
            ['name' => 'PT Nusantara Abadi Jaya', 'is_active' => true],
        );

        Entity::query()->updateOrCreate(
            ['short_name' => 'NMI'],
            ['name' => 'PT Nusantara Mineral Indonesia', 'is_active' => true],
        );
    }
}
