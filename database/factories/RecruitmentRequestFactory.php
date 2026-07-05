<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Entity;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentRequest>
 */
class RecruitmentRequestFactory extends Factory
{
    public function definition(): array
    {
        $entity = Entity::factory();

        return [
            'entity_id' => $entity,
            'department_id' => Department::factory()->for($entity),
            'requester_id' => User::factory(),
            'requester_position' => 'Supervisor',
            'requested_at' => now()->toDateString(),
            'position_name' => 'Operator Produksi',
            'headcount' => 2,
            'employment_status' => 'contract',
            'job_title' => 'Operator',
            'work_location' => 'Site A',
            'required_at' => now()->addMonth()->toDateString(),
            'reason_type' => 'addition',
            'reason_notes' => 'Kebutuhan operasional.',
            'min_education' => 'SMA',
            'min_experience' => '1 tahun',
            'required_skills' => 'Komunikasi, disiplin',
            'age_min' => 18,
            'age_max' => 35,
            'gender' => null,
            'job_description' => 'Menjalankan proses operasional harian.',
            'facilities' => [
                'salary_gross' => true,
                'salary_nett' => false,
                'transport' => true,
                'health' => true,
                'communication' => false,
                'meal' => true,
                'overtime' => false,
                'vehicle' => false,
                'laptop' => true,
                'mess' => false,
                'apd' => false,
                'uniform' => false,
            ],
            'status' => 'draft',
        ];
    }
}
