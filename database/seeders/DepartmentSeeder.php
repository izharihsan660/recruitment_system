<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Entity;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $entity = Entity::query()->where('short_name', 'NAJ')->first();

        if (! $entity) {
            return;
        }

        foreach (['Human Resources', 'Operations'] as $name) {
            Department::query()->updateOrCreate(
                ['entity_id' => $entity->id, 'name' => $name],
                ['is_active' => true],
            );
        }
    }
}
