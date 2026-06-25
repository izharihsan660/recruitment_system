<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApprovalChain;
use App\Models\ApprovalRecord;
use App\Models\BackgroundCheck;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Entity;
use App\Models\HrInterview;
use App\Models\JobPosting;
use App\Models\OfferingLetter;
use App\Models\PipelineLog;
use App\Models\PsychoTest;
use App\Models\RecruitmentRequest;
use App\Models\Screening;
use App\Models\TalentPool;
use App\Models\User;
use App\Models\UserInterview;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $now = now();
        $naj = $this->entity('PT Nusantara Abadi Jaya', 'NAJ');
        $this->entity('PT Nirwana Anugerah Jaya', 'NAJ2');

        $departments = collect(['Human Resources', 'Operations', 'Finance', 'IT'])
            ->mapWithKeys(fn (string $name): array => [$name => $this->department($naj, $name)]);

        $users = $this->users($departments->all());
        $chains = $this->approvalChains($departments->all(), $users);
        $requests = $this->recruitmentRequests($naj, $departments->all(), $users, $chains, $now);
        $postings = $this->jobPostings($requests, $now);
        $candidates = $this->candidates($now);

        $this->applicationWithProgress($postings['Software Engineer'], $candidates['Budi Santoso'], 'screening', ['applied', 'screening'], $users, $now);
        $this->applicationWithProgress($postings['Software Engineer'], $candidates['Sari Dewi'], 'interview_hr', ['applied', 'screening', 'test_psikotes', 'interview_hr'], $users, $now, ['screening', 'psycho_test']);
        $this->applicationWithProgress($postings['Driver'], $candidates['Ahmad Fauzi'], 'background_check', ['applied', 'screening', 'interview_hr', 'interview_user', 'background_check'], $users, $now, ['screening', 'hr_interview', 'user_interview']);
        $this->applicationWithProgress($postings['Finance Staff'], $candidates['Rina Marlina'], 'offering', ['applied', 'screening', 'interview_hr', 'interview_user', 'background_check', 'offering'], $users, $now, ['screening', 'hr_interview', 'user_interview', 'background_check', 'offering']);
        $this->applicationWithProgress($postings['Driver'], $candidates['Doni Prasetyo'], 'applied', ['applied'], $users, $now);

        $this->talentPoolApplications($postings, $users, $now);
    }

    private function entity(string $name, string $shortName): Entity
    {
        return Entity::query()->firstOrCreate(
            ['short_name' => $shortName],
            ['name' => $name, 'is_active' => true],
        );
    }

    private function department(Entity $entity, string $name): Department
    {
        return Department::query()->firstOrCreate(
            ['entity_id' => $entity->id, 'name' => $name],
            ['is_active' => true],
        );
    }

    /**
     * @param  array<string, Department>  $departments
     * @return array<string, User>
     */
    private function users(array $departments): array
    {
        $records = [
            'superadmin@example.com' => ['name' => 'Super Admin', 'username' => 'superadmin', 'role' => Roles::Admin, 'department' => null],
            'hr@example.com' => ['name' => 'HR Recruiter', 'username' => 'hrrecruiter', 'role' => Roles::HrRecruiter, 'department' => 'Human Resources'],
            'hrmanager@example.com' => ['name' => 'HR Manager', 'username' => 'hrmanager', 'role' => Roles::HrManager, 'department' => 'Human Resources'],
            'hiring@example.com' => ['name' => 'Hiring Manager', 'username' => 'hiringmanager', 'role' => Roles::HiringManager, 'department' => 'Operations'],
            'approver@example.com' => ['name' => 'Approver', 'username' => 'approver', 'role' => Roles::Approver, 'department' => 'Operations'],
            'pic@example.com' => ['name' => 'PIC Preboarding', 'username' => 'picpreboarding', 'role' => Roles::PicPreboarding, 'department' => 'Human Resources'],
        ];

        return collect($records)->mapWithKeys(function (array $record, string $email) use ($departments): array {
            $departmentId = $record['department'] ? $departments[$record['department']]->id : null;
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $record['name'],
                    'username' => $record['username'],
                    'department_id' => $departmentId,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );

            $user->syncRoles([$record['role']]);

            return [$email => $user];
        })->all();
    }

    /**
     * @param  array<string, Department>  $departments
     * @param  array<string, User>  $users
     * @return array<string, array<int, ApprovalChain>>
     */
    private function approvalChains(array $departments, array $users): array
    {
        $definitions = [
            'Human Resources' => [['level' => 1, 'type' => 'role', 'role' => Roles::HrManager]],
            'Operations' => [
                ['level' => 1, 'type' => 'user', 'user' => $users['approver@example.com']],
                ['level' => 2, 'type' => 'role', 'role' => Roles::HrManager],
            ],
            'Finance' => [['level' => 1, 'type' => 'role', 'role' => Roles::HrRecruiter]],
            'IT' => [['level' => 1, 'type' => 'role', 'role' => Roles::HrManager]],
        ];

        return collect($definitions)->mapWithKeys(function (array $levels, string $departmentName) use ($departments): array {
            $chains = collect($levels)->map(function (array $level) use ($departments, $departmentName): ApprovalChain {
                return ApprovalChain::query()->firstOrCreate(
                    ['department_id' => $departments[$departmentName]->id, 'level' => $level['level']],
                    [
                        'type' => $level['type'],
                        'approver_user_id' => $level['user']->id ?? null,
                        'approver_role' => $level['role'] ?? null,
                    ],
                );
            });

            return [$departmentName => $chains->all()];
        })->all();
    }

    /**
     * @param  array<string, Department>  $departments
     * @param  array<string, User>  $users
     * @param  array<string, array<int, ApprovalChain>>  $chains
     * @return array<string, RecruitmentRequest>
     */
    private function recruitmentRequests(Entity $entity, array $departments, array $users, array $chains, Carbon $now): array
    {
        $records = [
            'Software Engineer' => ['department' => 'IT', 'skills' => 'Laravel, React, MySQL', 'education' => 'S1 Informatika'],
            'Driver' => ['department' => 'Operations', 'skills' => 'SIM B2, defensive driving, SIMPER readiness', 'education' => 'SMA/SMK'],
            'Finance Staff' => ['department' => 'Finance', 'skills' => 'Accounting, Excel, tax administration', 'education' => 'S1 Akuntansi'],
        ];

        return collect($records)->mapWithKeys(function (array $record, string $position) use ($entity, $departments, $users, $chains, $now): array {
            $request = RecruitmentRequest::query()->firstOrCreate(
                ['position_name' => $position, 'department_id' => $departments[$record['department']]->id, 'entity_id' => $entity->id],
                [
                    'requester_id' => $users['hiring@example.com']->id,
                    'requester_position' => 'Hiring Manager',
                    'requested_at' => $now->copy()->subDays(21)->toDateString(),
                    'headcount' => 1,
                    'employment_status' => 'contract',
                    'job_title' => $position,
                    'work_location' => 'Head Office / Site',
                    'required_at' => $now->copy()->addDays(30)->toDateString(),
                    'reason_type' => 'addition',
                    'reason_notes' => 'Demo recruitment pipeline data.',
                    'min_education' => $record['education'],
                    'min_experience' => 'Minimal 2 tahun',
                    'required_skills' => $record['skills'],
                    'age_min' => 22,
                    'age_max' => 40,
                    'gender' => 'any',
                    'job_description' => "Mengisi kebutuhan posisi {$position} untuk demo pipeline.",
                    'facilities' => ['BPJS', 'THR'],
                    'status' => 'approved',
                    'current_approval_level' => null,
                ],
            );

            $request->forceFill(['status' => 'approved', 'current_approval_level' => null])->save();

            foreach ($chains[$record['department']] as $chain) {
                ApprovalRecord::query()->firstOrCreate(
                    ['recruitment_request_id' => $request->id, 'level' => $chain->level],
                    [
                        'approval_chain_id' => $chain->id,
                        'approver_id' => $chain->type === 'user' ? $chain->approver_user_id : $this->roleApproverId($chain->approver_role, $users),
                        'action' => 'approved',
                        'comment' => 'Approved for demo data.',
                        'acted_at' => $now->copy()->subDays(19)->addHours($chain->level),
                    ],
                );
            }

            return [$position => $request];
        })->all();
    }

    /** @param array<string, User> $users */
    private function roleApproverId(?string $role, array $users): int
    {
        return match ($role) {
            Roles::HrRecruiter => $users['hr@example.com']->id,
            default => $users['hrmanager@example.com']->id,
        };
    }

    /**
     * @param  array<string, RecruitmentRequest>  $requests
     * @return array<string, JobPosting>
     */
    private function jobPostings(array $requests, Carbon $now): array
    {
        $requirements = [
            'Software Engineer' => ['test_required' => true, 'mcu_required' => false, 'simper_required' => false],
            'Driver' => ['test_required' => false, 'mcu_required' => true, 'simper_required' => true],
            'Finance Staff' => ['test_required' => false, 'mcu_required' => false, 'simper_required' => false],
        ];

        return collect($requests)->mapWithKeys(function (RecruitmentRequest $request, string $position) use ($requirements, $now): array {
            $posting = JobPosting::query()->firstOrCreate(
                ['recruitment_request_id' => $request->id],
                [
                    'entity_id' => $request->entity_id,
                    'department_id' => $request->department_id,
                    'position_name' => $position,
                    'employment_status' => $request->employment_status,
                    'work_location' => $request->work_location,
                    'job_description' => $request->job_description,
                    'requirements' => $request->required_skills,
                    'status' => 'open',
                    'opened_at' => $now->copy()->subDays(18),
                    ...$requirements[$position],
                ],
            );

            $posting->forceFill([
                'status' => 'open',
                'opened_at' => $posting->opened_at ?? $now->copy()->subDays(18),
                ...$requirements[$position],
            ])->save();

            return [$position => $posting];
        })->all();
    }

    /** @return array<string, Candidate> */
    private function candidates(Carbon $now): array
    {
        $records = [
            'kandidat1@example.com' => 'Budi Santoso',
            'kandidat2@example.com' => 'Sari Dewi',
            'kandidat3@example.com' => 'Ahmad Fauzi',
            'kandidat4@example.com' => 'Rina Marlina',
            'kandidat5@example.com' => 'Doni Prasetyo',
        ];

        return collect($records)->mapWithKeys(function (string $name, string $email) use ($now): array {
            $candidate = Candidate::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'phone' => '08'.fake()->numerify('##########'),
                    'address' => 'Balikpapan',
                    'birth_date' => $now->copy()->subYears(28)->toDateString(),
                    'gender' => 'male',
                    'cv_path' => "demo/cv/{$email}.pdf",
                    'cv_original_name' => "CV {$name}.pdf",
                    'education' => [['level' => 'S1', 'major' => 'Umum', 'institution' => 'Universitas Demo']],
                    'experience' => [['company' => 'Perusahaan Demo', 'position' => 'Staff', 'years' => 3]],
                    'email_verified_at' => $now,
                ],
            );

            $candidate->forceFill([
                'name' => $name,
                'password' => Hash::make('password'),
                'cv_path' => "demo/cv/{$email}.pdf",
                'cv_original_name' => "CV {$name}.pdf",
                'email_verified_at' => $candidate->email_verified_at ?? $now,
            ])->save();

            return [$name => $candidate];
        })->all();
    }

    /**
     * @param  array<int, string>  $path
     * @param  array<string, User>  $users
     * @param  array<int, string>  $completedRecords
     */
    private function applicationWithProgress(JobPosting $posting, Candidate $candidate, string $status, array $path, array $users, Carbon $now, array $completedRecords = []): Application
    {
        $application = Application::query()->firstOrCreate(
            ['job_posting_id' => $posting->id, 'candidate_id' => $candidate->id],
            ['source' => 'portal', 'status' => $status, 'consent' => true, 'consent_at' => $now->copy()->subDays(12)],
        );

        $application->forceFill(['status' => $status, 'consent' => true, 'consent_at' => $application->consent_at ?? $now->copy()->subDays(12)])->save();
        $this->pipelineLogs($application, $path, $users['hr@example.com'], $now);

        if (in_array('screening', $completedRecords, true)) {
            $this->screening($application, $users['hr@example.com'], $now, 'passed');
        }

        if (in_array('psycho_test', $completedRecords, true)) {
            $this->psychoTest($application, $users['hr@example.com'], $now, 'passed');
        }

        if (in_array('hr_interview', $completedRecords, true)) {
            $this->hrInterview($application, $users['hrmanager@example.com'], $now, 'passed');
        }

        if (in_array('user_interview', $completedRecords, true)) {
            $this->userInterview($application, $users['hiring@example.com'], $now, 'passed');
        }

        if (in_array('background_check', $completedRecords, true)) {
            $this->backgroundCheck($application, $users['hr@example.com'], $now, 'passed');
        }

        if (in_array('offering', $completedRecords, true)) {
            $this->offering($application, $users['hrmanager@example.com'], $now);
        }

        return $application;
    }

    private function screening(Application $application, User $reviewer, Carbon $now, string $decision, ?string $reason = null): Screening
    {
        return Screening::query()->firstOrCreate(
            ['application_id' => $application->id],
            [
                'education_match' => $decision === 'passed',
                'experience_match' => $decision === 'passed',
                'document_complete' => true,
                'notes' => $decision === 'passed' ? 'Screening passed for demo.' : 'Screening failed for demo talent pool.',
                'decision' => $decision,
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => $now->copy()->subDays(10),
            ],
        );
    }

    private function psychoTest(Application $application, User $conductor, Carbon $now, string $decision): PsychoTest
    {
        return PsychoTest::query()->firstOrCreate(
            ['application_id' => $application->id],
            ['test_type' => 'Psikotes Online', 'scheduled_at' => $now->copy()->subDays(9), 'notes' => 'Psycho test passed for demo.', 'decision' => $decision, 'conducted_by' => $conductor->id],
        );
    }

    private function hrInterview(Application $application, User $interviewer, Carbon $now, string $status, ?string $reason = null): HrInterview
    {
        return HrInterview::query()->firstOrCreate(
            ['application_id' => $application->id],
            [
                'scheduled_at' => $now->copy()->subDays(8),
                'teams_meeting_link' => 'https://teams.microsoft.com/demo',
                'teams_meeting_id' => 'DEMO-HR-'.$application->id,
                'interviewer_id' => $interviewer->id,
                'score_communication' => $status === 'passed' ? 85 : 55,
                'score_personality' => $status === 'passed' ? 82 : 58,
                'score_motivation' => $status === 'passed' ? 88 : 50,
                'score_attitude' => $status === 'passed' ? 84 : 52,
                'score_culture_fit' => $status === 'passed' ? 86 : 54,
                'strengths' => 'Komunikasi baik dan motivasi jelas.',
                'weaknesses' => $reason,
                'salary_expectation' => 7500000,
                'recommendation' => $status === 'passed' ? 'recommended' : 'not_recommended',
                'notes' => 'HR interview demo record.',
                'status' => $status,
            ],
        );
    }

    private function userInterview(Application $application, User $interviewer, Carbon $now, string $status): UserInterview
    {
        return UserInterview::query()->firstOrCreate(
            ['application_id' => $application->id],
            [
                'scheduled_at' => $now->copy()->subDays(7),
                'location' => 'Head Office',
                'interviewer_id' => $interviewer->id,
                'score_technical' => 84,
                'score_experience' => 82,
                'score_problem_solving' => 80,
                'score_team_fit' => 85,
                'recommendation' => 'accepted',
                'notes' => 'User interview demo record.',
                'status' => $status === 'passed' ? 'recommended' : 'not_recommended',
            ],
        );
    }

    private function backgroundCheck(Application $application, User $verifier, Carbon $now, string $decision): BackgroundCheck
    {
        return BackgroundCheck::query()->firstOrCreate(
            ['application_id' => $application->id],
            ['ktp_verified' => true, 'ijazah_verified' => true, 'certificate_verified' => true, 'reference_verified' => true, 'notes' => 'Background check clear.', 'decision' => $decision === 'passed' ? 'clear' : $decision, 'verified_by' => $verifier->id, 'verified_at' => $now->copy()->subDays(5)],
        );
    }

    private function offering(Application $application, User $signer, Carbon $now): OfferingLetter
    {
        return OfferingLetter::query()->firstOrCreate(
            ['application_id' => $application->id],
            [
                'entity_id' => $application->jobPosting->entity_id,
                'hr_signer_id' => $signer->id,
                'position_name' => $application->jobPosting->position_name,
                'department' => $application->jobPosting->department->name,
                'work_location' => $application->jobPosting->work_location,
                'contract_type' => 'contract',
                'start_date' => $now->copy()->addDays(14)->toDateString(),
                'contract_duration' => '12 bulan',
                'salary_gross' => 8000000,
                'salary_nett' => 7500000,
                'allowances' => ['Transport', 'Meal'],
                'expiry_date' => $now->copy()->addDays(7)->toDateString(),
                'status' => 'draft',
                'archive_status' => 'pending',
            ],
        );
    }

    /** @param array<int, string> $path */
    private function pipelineLogs(Application $application, array $path, User $actor, Carbon $now): void
    {
        $previous = null;

        foreach ($path as $index => $stage) {
            PipelineLog::query()->firstOrCreate(
                ['application_id' => $application->id, 'from_stage' => $previous, 'to_stage' => $stage],
                ['actor_id' => $actor->id, 'notes' => 'Demo pipeline movement.', 'created_at' => $now->copy()->subDays(12 - $index)],
            );

            $previous = $stage;
        }
    }

    /**
     * @param  array<string, JobPosting>  $postings
     * @param  array<string, User>  $users
     */
    private function talentPoolApplications(array $postings, array $users, Carbon $now): void
    {
        $records = [
            ['email' => 'talent-screening@example.com', 'name' => 'Maya Lestari', 'posting' => 'Finance Staff', 'stage' => 'screening', 'reason' => 'Belum memenuhi kualifikasi minimum.'],
            ['email' => 'talent-hr@example.com', 'name' => 'Eko Prabowo', 'posting' => 'Software Engineer', 'stage' => 'interview_hr', 'reason' => 'Belum sesuai dengan kebutuhan budaya kerja saat ini.'],
        ];

        foreach ($records as $record) {
            $candidate = Candidate::query()->firstOrCreate(
                ['email' => $record['email']],
                ['name' => $record['name'], 'password' => Hash::make('password'), 'cv_path' => "demo/cv/{$record['email']}.pdf", 'cv_original_name' => "CV {$record['name']}.pdf", 'email_verified_at' => $now],
            );

            $application = Application::query()->firstOrCreate(
                ['job_posting_id' => $postings[$record['posting']]->id, 'candidate_id' => $candidate->id],
                ['source' => 'portal', 'status' => 'rejected', 'rejection_stage' => $record['stage'], 'rejection_reason' => $record['reason'], 'consent' => true, 'consent_at' => $now->copy()->subDays(14)],
            );

            $application->forceFill(['status' => 'rejected', 'rejection_stage' => $record['stage'], 'rejection_reason' => $record['reason']])->save();
            $this->pipelineLogs($application, $record['stage'] === 'screening' ? ['applied', 'screening', 'rejected'] : ['applied', 'screening', 'interview_hr', 'rejected'], $users['hr@example.com'], $now);
            $this->screening($application, $users['hr@example.com'], $now, $record['stage'] === 'screening' ? 'failed' : 'passed', $record['stage'] === 'screening' ? $record['reason'] : null);

            if ($record['stage'] === 'interview_hr') {
                $this->hrInterview($application, $users['hrmanager@example.com'], $now, 'failed', $record['reason']);
            }

            TalentPool::query()->firstOrCreate(
                ['candidate_id' => $candidate->id],
                ['status' => 'active', 'tags' => ['demo', 'rejected-'.$record['stage']], 'notes' => $record['reason'], 'source_application_id' => $application->id, 'added_by' => $users['hr@example.com']->id, 'added_at' => $now],
            );
        }
    }
}
