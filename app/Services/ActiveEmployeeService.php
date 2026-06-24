<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActiveEmployeeService
{
    public function __construct(private readonly PreboardingService $preboardingService, private readonly ProbationService $probationService) {}

    public function activate(Application $app, array $data, User $actor): Employee
    {
        if ($app->status !== 'hired') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berstatus hired.']);
        }

        $app->loadMissing(['candidate', 'jobPosting.department', 'jobPosting.entity', 'pkwtContract']);
        if (! $app->pkwtContract || $app->pkwtContract->status !== 'signed' || $app->pkwtContract->archive_status !== 'archived') {
            throw ValidationException::withMessages(['pkwt' => 'PKWT harus signed dan archived sebelum aktivasi.']);
        }

        return DB::transaction(function () use ($app, $data, $actor): Employee {
            $employee = Employee::query()->create([
                'application_id' => $app->id,
                'candidate_id' => $app->candidate_id,
                'entity_id' => $app->jobPosting->entity_id,
                'department_id' => $app->jobPosting->department_id,
                'employee_id' => $data['employee_id'],
                'full_name' => $app->candidate->name,
                'email' => $app->candidate->email,
                'phone' => $app->candidate->phone,
                'position_name' => $app->pkwtContract->position_name,
                'contract_type' => $app->pkwtContract->contract_type,
                'start_date' => $data['start_date'] ?? $app->pkwtContract->start_date,
                'end_date' => $app->pkwtContract->end_date,
                'status' => 'active',
                'activated_by' => $actor->id,
                'activated_at' => now(),
            ]);

            $this->preboardingService->createFromTemplate($employee);
            $this->probationService->create($employee);

            return $employee;
        });
    }
}
