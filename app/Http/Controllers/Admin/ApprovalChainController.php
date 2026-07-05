<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreApprovalChainRequest;
use App\Http\Requests\Admin\UpdateApprovalChainRequest;
use App\Http\Resources\ApprovalChainResource;
use App\Models\ApprovalChain;
use App\Services\ApprovalChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApprovalChainController extends Controller
{
    public function __construct(private readonly ApprovalChainService $approvalChainService) {}

    public function index(): AnonymousResourceCollection
    {
        return ApprovalChainResource::collection(ApprovalChain::query()->whereNotNull('approver_user_id')->with(['department.entity', 'approverUser.roles'])->withCount('approvalRecords')->orderBy('department_id')->latest('id')->paginate());
    }

    public function store(StoreApprovalChainRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->approvalChainService->createMany((int) $validated['department_id'], $validated['user_ids']);

        return redirect()->back()->with('success', 'Approval Chain berhasil dibuat.');
    }

    public function show(ApprovalChain $approvalChain): ApprovalChainResource
    {
        return new ApprovalChainResource($approvalChain->load(['department.entity', 'approverUser.roles'])->loadCount('approvalRecords'));
    }

    public function update(UpdateApprovalChainRequest $request, ApprovalChain $approvalChain): RedirectResponse
    {
        $this->approvalChainService->update($approvalChain, $request->validated());

        return redirect()->back()->with('success', 'Approval Chain berhasil diperbarui.');
    }

    public function destroy(ApprovalChain $approvalChain): RedirectResponse
    {
        $this->approvalChainService->delete($approvalChain);

        return redirect()->back()->with('success', 'Approval Chain berhasil dihapus.');
    }
}
