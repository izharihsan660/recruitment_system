<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use App\Models\UserInterview;
use App\Notifications\UserInterviewScheduledNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserInterviewService
{
    public function __construct(private readonly PipelineService $pipelineService) {}

    public function schedule(Application $application, array $data, User $actor): UserInterview
    {
        if ($application->status !== 'interview_user') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage interview user.']);
        }

        return DB::transaction(function () use ($application, $data): UserInterview {
            $interview = UserInterview::query()->updateOrCreate(
                ['application_id' => $application->id],
                [
                    'scheduled_at' => $data['scheduled_at'],
                    'location' => $data['location'],
                    'interviewer_id' => $data['interviewer_id'],
                    'status' => 'scheduled',
                ],
            );

            $interview->loadMissing(['interviewer', 'application.candidate', 'application.jobPosting']);
            $interview->interviewer->notify(new UserInterviewScheduledNotification($interview));
            $interview->application->candidate->notify(new UserInterviewScheduledNotification($interview));

            return $interview;
        });
    }

    public function submitScorecard(UserInterview $interview, array $data, User $actor): void
    {
        if ((int) $interview->interviewer_id !== (int) $actor->id) {
            abort(403, 'Hanya interviewer yang ditugaskan dapat mengisi scorecard.');
        }

        if ($interview->application->status !== 'interview_user') {
            throw ValidationException::withMessages(['application_id' => 'Aplikasi harus berada di stage interview user.']);
        }

        if (($data['recommendation'] ?? null) === 'rejected' && blank($data['rejection_reason'] ?? null)) {
            throw ValidationException::withMessages(['rejection_reason' => 'Alasan penolakan wajib diisi.']);
        }

        DB::transaction(function () use ($interview, $data, $actor): void {
            $interview->update([
                'score_technical' => $data['score_technical'],
                'score_experience' => $data['score_experience'],
                'score_problem_solving' => $data['score_problem_solving'],
                'score_team_fit' => $data['score_team_fit'],
                'recommendation' => $data['recommendation'],
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => in_array($data['recommendation'], ['accepted', 'considered'], true) ? 'recommended' : 'not_recommended',
            ]);

            if (in_array($interview->recommendation, ['accepted', 'considered'], true)) {
                $this->pipelineService->moveToNextStage($interview->application->refresh(), $actor);
            }

            if ($interview->recommendation === 'rejected') {
                $this->pipelineService->reject($interview->application->refresh(), $actor, (string) $interview->rejection_reason);
            }
        });
    }
}
