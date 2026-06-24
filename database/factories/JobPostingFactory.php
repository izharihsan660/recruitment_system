<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Entity;
use App\Models\JobPosting;
use App\Models\RecruitmentRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        $entity = Entity::factory();

        return [
            'recruitment_request_id' => RecruitmentRequest::factory()->state(['status' => 'approved']),
            'entity_id' => $entity,
            'department_id' => Department::factory()->for($entity),
            'position_name' => 'Operator Produksi',
            'employment_status' => 'contract',
            'work_location' => 'Site A',
            'job_description' => 'Menjalankan proses operasional harian.',
            'requirements' => 'Minimal SMA dan berpengalaman.',
            'mcu_required' => false,
            'simper_required' => false,
            'status' => 'draft',
            'opened_at' => null,
            'closed_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'open',
            'opened_at' => now(),
        ]);
    }
}
