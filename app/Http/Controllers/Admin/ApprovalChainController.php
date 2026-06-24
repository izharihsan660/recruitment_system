<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApprovalChainRequest;
use App\Http\Requests\Admin\UpdateApprovalChainRequest;
use App\Http\Resources\ApprovalChainResource;
use App\Models\ApprovalChain;
use App\Services\ApprovalChainService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ApprovalChainController extends Controller
{
    public function __construct(private readonly ApprovalChainService $approvalChainService) {}

    public function index(): AnonymousResourceCollection
    {
        return ApprovalChainResource::collection(ApprovalChain::query()->with(['department.entity', 'approverUser.roles'])->orderBy('department_id')->orderBy('level')->paginate());
    }

    public function store(StoreApprovalChainRequest $request): ApprovalChainResource
    {
        return new ApprovalChainResource($this->approvalChainService->create($request->validated())->load(['department.entity', 'approverUser.roles']));
    }

    public function show(ApprovalChain $approvalChain): ApprovalChainResource
    {
        return new ApprovalChainResource($approvalChain->load(['department.entity', 'approverUser.roles']));
    }

    public function update(UpdateApprovalChainRequest $request, ApprovalChain $approvalChain): ApprovalChainResource
    {
        return new ApprovalChainResource($this->approvalChainService->update($approvalChain, $request->validated())->load(['department.entity', 'approverUser.roles']));
    }

    public function destroy(ApprovalChain $approvalChain): Response
    {
        $this->approvalChainService->delete($approvalChain);

        return response()->noContent();
    }
}
