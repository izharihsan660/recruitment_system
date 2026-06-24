<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignEmailIntakeToJobRequest;
use App\Http\Requests\MoveEmailIntakeToTalentPoolRequest;
use App\Http\Requests\RejectEmailIntakeRequest;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\EmailIntakeResource;
use App\Http\Resources\TalentPoolResource;
use App\Models\EmailIntake;
use App\Models\JobPosting;
use App\Services\EmailIntakeReviewService;
use App\Services\EmailIntakeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EmailIntakeController extends Controller
{
    public function __construct(
        private readonly EmailIntakeService $emailIntakeService,
        private readonly EmailIntakeReviewService $emailIntakeReviewService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return EmailIntakeResource::collection(
            EmailIntake::query()
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
                ->latest()
                ->paginate()
        );
    }

    public function show(EmailIntake $emailIntake): EmailIntakeResource
    {
        return new EmailIntakeResource($emailIntake);
    }

    public function fetch(): AnonymousResourceCollection
    {
        return EmailIntakeResource::collection($this->emailIntakeService->fetchEmails());
    }

    public function assignToJob(AssignEmailIntakeToJobRequest $request, EmailIntake $emailIntake): ApplicationResource
    {
        $job = JobPosting::query()->findOrFail($request->integer('job_posting_id'));

        return new ApplicationResource($this->emailIntakeReviewService->assignToJob($emailIntake, $job, $request->user(), $request->boolean('consent')));
    }

    public function moveToTalentPool(MoveEmailIntakeToTalentPoolRequest $request, EmailIntake $emailIntake): TalentPoolResource
    {
        return new TalentPoolResource($this->emailIntakeReviewService->moveToTalentPool($emailIntake, $request->user(), $request->boolean('consent'), $request->string('notes')->toString()));
    }

    public function reject(RejectEmailIntakeRequest $request, EmailIntake $emailIntake): Response
    {
        $this->emailIntakeReviewService->reject($emailIntake, $request->user(), $request->string('reason')->toString());

        return response()->noContent();
    }

    public function ignore(EmailIntake $emailIntake): Response
    {
        $this->emailIntakeReviewService->ignore($emailIntake, request()->user());

        return response()->noContent();
    }

    public function spam(EmailIntake $emailIntake): Response
    {
        $this->emailIntakeReviewService->markSpam($emailIntake, request()->user());

        return response()->noContent();
    }
}
