<?php

namespace App\Http\Controllers;

use App\Http\Requests\Fpk\ApprovalActionRequest;
use App\Http\Requests\Fpk\StoreFpkRequest;
use App\Http\Requests\Fpk\UpdateFpkRequest;
use App\Http\Resources\ApprovalRecordResource;
use App\Http\Resources\RecruitmentRequestResource;
use App\Models\RecruitmentRequest;
use App\Services\RecruitmentRequestService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FpkController extends Controller
{
    public function __construct(private readonly RecruitmentRequestService $recruitmentRequestService) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);

        $query = RecruitmentRequest::query()->with(['entity', 'department', 'requester'])->latest();

        if (! request()->user()->hasAnyRole([Roles::Admin, Roles::HrRecruiter, Roles::HrManager])) {
            $query->where('department_id', request()->user()->department_id);
        }

        return RecruitmentRequestResource::collection($query->paginate());
    }

    public function store(StoreFpkRequest $request): RedirectResponse
    {
        $fpk = $this->recruitmentRequestService->create($request->validated(), $request->user());

        return redirect()->route('fpk.show', $fpk)
            ->with('success', 'FPK berhasil dibuat.');
    }

    public function show(RecruitmentRequest $fpk): RecruitmentRequestResource
    {
        Gate::authorize('view', $fpk);

        return new RecruitmentRequestResource($fpk->load(['entity', 'department', 'requester', 'approvalRecords.approver']));
    }

    public function update(UpdateFpkRequest $request, RecruitmentRequest $fpk): RedirectResponse
    {
        $fpk = $this->recruitmentRequestService->update($fpk, $request->validated());

        return redirect()->route('fpk.show', $fpk)
            ->with('success', 'FPK berhasil diperbarui.');
    }

    public function submit(RecruitmentRequest $fpk): RedirectResponse
    {
        Gate::authorize('submit', $fpk);
        $this->recruitmentRequestService->submit($fpk, request()->user());

        return redirect()->route('fpk.show', $fpk)
            ->with('success', 'FPK berhasil disubmit.');
    }

    public function approve(ApprovalActionRequest $request, RecruitmentRequest $fpk): RedirectResponse
    {
        Gate::authorize('approve', $fpk);
        $this->recruitmentRequestService->approve($fpk, $request->user(), $request->string('comment')->toString() ?: null);

        return redirect()->route('fpk.show', $fpk)
            ->with('success', 'FPK berhasil disetujui.');
    }

    public function reject(ApprovalActionRequest $request, RecruitmentRequest $fpk): RedirectResponse
    {
        Gate::authorize('reject', $fpk);
        $this->recruitmentRequestService->reject($fpk, $request->user(), $request->string('comment')->toString());

        return redirect()->route('fpk.show', $fpk)
            ->with('success', 'FPK berhasil ditolak.');
    }

    public function needRevision(ApprovalActionRequest $request, RecruitmentRequest $fpk): RedirectResponse
    {
        Gate::authorize('needRevision', $fpk);
        $this->recruitmentRequestService->needRevision($fpk, $request->user(), $request->string('comment')->toString());

        return redirect()->route('fpk.show', $fpk)
            ->with('success', 'FPK dikembalikan untuk revisi.');
    }

    public function close(RecruitmentRequest $fpk): RedirectResponse
    {
        Gate::authorize('close', $fpk);
        $this->recruitmentRequestService->close($fpk, request()->user());

        return redirect()->route('fpk.show', $fpk)
            ->with('success', 'FPK berhasil ditutup.');
    }

    public function approvals(RecruitmentRequest $fpk): AnonymousResourceCollection
    {
        Gate::authorize('view', $fpk);

        return ApprovalRecordResource::collection($fpk->approvalRecords()->with('approver')->get());
    }
}
