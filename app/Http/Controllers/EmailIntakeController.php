<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignEmailIntakeToJobRequest;
use App\Http\Requests\MoveEmailIntakeToTalentPoolRequest;
use App\Http\Requests\RejectEmailIntakeRequest;
use App\Http\Resources\EmailIntakeResource;
use App\Models\EmailIntake;
use App\Models\JobPosting;
use App\Services\EmailIntakeReviewService;
use App\Services\EmailIntakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function assignToJob(AssignEmailIntakeToJobRequest $request, EmailIntake $emailIntake): RedirectResponse
    {
        $job = JobPosting::query()->findOrFail($request->integer('job_posting_id'));

        $this->emailIntakeReviewService->assignToJob($emailIntake, $job, $request->user(), $request->boolean('consent'));

        return redirect()->route('pipeline.index')
            ->with('success', 'Aksi berhasil dijalankan.');
    }

    public function moveToTalentPool(MoveEmailIntakeToTalentPoolRequest $request, EmailIntake $emailIntake): RedirectResponse
    {
        $this->emailIntakeReviewService->moveToTalentPool($emailIntake, $request->user(), $request->boolean('consent'), $request->string('notes')->toString());

        return redirect()->to('/hr/talent-pool')
            ->with('success', 'Aksi berhasil dijalankan.');
    }

    public function reject(RejectEmailIntakeRequest $request, EmailIntake $emailIntake): RedirectResponse
    {
        $this->emailIntakeReviewService->reject($emailIntake, $request->user(), $request->string('reason')->toString());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function ignore(EmailIntake $emailIntake): RedirectResponse
    {
        $this->emailIntakeReviewService->ignore($emailIntake, request()->user());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }

    public function spam(EmailIntake $emailIntake): RedirectResponse
    {
        $this->emailIntakeReviewService->markSpam($emailIntake, request()->user());

        return redirect()->back()->with('success', 'Aksi berhasil dijalankan.');
    }
}
