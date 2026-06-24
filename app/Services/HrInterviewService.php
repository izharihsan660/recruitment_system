<?php

namespace App\Services;

use App\Models\Application;
use App\Models\HrInterview;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrInterviewService
{
    public function __construct(
        private readonly PipelineService $pipelineService,
        private readonly TeamsMeetingService $teamsMeetingService,
    ) {}

    public function schedule(Application $application, array $data, User $actor): HrInterview
    {
        if ($application->status !== 'interview_hr') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage interview HR.']);
        }

        $scheduledAt = Carbon::parse($data['scheduled_at']);

        $application->loadMissing(['candidate']);
        $meeting = $this->teamsMeetingService->create($application, $scheduledAt);

        return HrInterview::query()->updateOrCreate(
            ['application_id' => $application->id],
            [
                'scheduled_at' => $scheduledAt,
                'teams_meeting_link' => $meeting['join_url'],
                'teams_meeting_id' => $meeting['meeting_id'],
                'interviewer_id' => $data['interviewer_id'] ?? $actor->id,
                'status' => 'scheduled',
            ],
        );
    }

    public function submitScorecard(HrInterview $interview, array $data, User $actor): void
    {
        if ($interview->application->status !== 'interview_hr') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage interview HR.']);
        }

        if (($data['recommendation'] ?? null) === 'not_recommended' && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages(['notes' => 'Catatan wajib diisi jika tidak direkomendasikan.']);
        }

        DB::transaction(function () use ($interview, $data, $actor): void {
            $interview->update([
                'score_communication' => $data['score_communication'],
                'score_personality' => $data['score_personality'],
                'score_motivation' => $data['score_motivation'],
                'score_attitude' => $data['score_attitude'],
                'score_culture_fit' => $data['score_culture_fit'],
                'strengths' => $data['strengths'] ?? null,
                'weaknesses' => $data['weaknesses'] ?? null,
                'salary_expectation' => $data['salary_expectation'] ?? null,
                'recommendation' => $data['recommendation'],
                'notes' => $data['notes'] ?? null,
                'status' => in_array($data['recommendation'], ['recommended', 'considered'], true) ? 'passed' : 'failed',
            ]);

            if (in_array($interview->recommendation, ['recommended', 'considered'], true)) {
                $this->pipelineService->moveToNextStage($interview->application->refresh(), $actor);
            }

            if ($interview->recommendation === 'not_recommended') {
                $this->pipelineService->reject($interview->application->refresh(), $actor, (string) $interview->notes);
            }
        });
    }
}
