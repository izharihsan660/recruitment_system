<?php

namespace App\Http\Controllers;

use App\Http\Requests\Fpk\ApprovalActionRequest;
use App\Http\Requests\Fpk\StoreFpkRequest;
use App\Http\Requests\Fpk\UpdateFpkRequest;
use App\Http\Resources\ApprovalRecordResource;
use App\Http\Resources\RecruitmentRequestResource;
use App\Models\RecruitmentRequest;
use App\Services\RecruitmentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class FpkController extends Controller
{
    public function __construct(private readonly RecruitmentRequestService $recruitmentRequestService) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RecruitmentRequest::class);

        $query = RecruitmentRequest::query()->with(['entity', 'department', 'requester'])->latest();

        if (! request()->user()->hasAnyRole(['hr_recruiter', 'hr_manager'])) {
            $query->where('department_id', request()->user()->department_id);
        }

        return RecruitmentRequestResource::collection($query->paginate());
    }

    public function store(StoreFpkRequest $request): JsonResponse
    {
        $fpk = $this->recruitmentRequestService->create($request->validated(), $request->user());

        return (new RecruitmentRequestResource($fpk->load(['entity', 'department', 'requester', 'approvalRecords.approver'])))->response()->setStatusCode(201);
    }

    public function show(RecruitmentRequest $fpk): RecruitmentRequestResource
    {
        Gate::authorize('view', $fpk);

        return new RecruitmentRequestResource($fpk->load(['entity', 'department', 'requester', 'approvalRecords.approver']));
    }

    public function update(UpdateFpkRequest $request, RecruitmentRequest $fpk): RecruitmentRequestResource
    {
        $fpk = $this->recruitmentRequestService->update($fpk, $request->validated());

        return new RecruitmentRequestResource($fpk->load(['entity', 'department', 'requester', 'approvalRecords.approver']));
    }

    public function submit(RecruitmentRequest $fpk): Response
    {
        Gate::authorize('submit', $fpk);
        $this->recruitmentRequestService->submit($fpk, request()->user());

        return response()->noContent();
    }

    public function approve(ApprovalActionRequest $request, RecruitmentRequest $fpk): Response
    {
        Gate::authorize('approve', $fpk);
        $this->recruitmentRequestService->approve($fpk, $request->user(), $request->string('comment')->toString() ?: null);

        return response()->noContent();
    }

    public function reject(ApprovalActionRequest $request, RecruitmentRequest $fpk): Response
    {
        Gate::authorize('reject', $fpk);
        $this->recruitmentRequestService->reject($fpk, $request->user(), $request->string('comment')->toString());

        return response()->noContent();
    }

    public function needRevision(ApprovalActionRequest $request, RecruitmentRequest $fpk): Response
    {
        Gate::authorize('needRevision', $fpk);
        $this->recruitmentRequestService->needRevision($fpk, $request->user(), $request->string('comment')->toString());

        return response()->noContent();
    }

    public function close(RecruitmentRequest $fpk): Response
    {
        Gate::authorize('close', $fpk);
        $this->recruitmentRequestService->close($fpk, request()->user());

        return response()->noContent();
    }

    public function approvals(RecruitmentRequest $fpk): AnonymousResourceCollection
    {
        Gate::authorize('view', $fpk);

        return ApprovalRecordResource::collection($fpk->approvalRecords()->with('approver')->get());
    }
}
