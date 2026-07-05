<?php

namespace App\Services;

use App\Models\ApprovalChain;
use App\Models\ApprovalRecord;
use App\Models\RecruitmentRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentRequestService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function create(array $data, User $requester): RecruitmentRequest
    {
        $data['requester_id'] = $requester->id;
        $data['status'] = 'draft';

        return RecruitmentRequest::query()->create($data);
    }

    public function update(RecruitmentRequest $fpk, array $data): RecruitmentRequest
    {
        $this->ensureEditable($fpk);
        $fpk->update($data);

        return $fpk;
    }

    public function submit(RecruitmentRequest $fpk, User $actor): void
    {
        DB::transaction(function () use ($fpk): void {
            if (! in_array($fpk->status, ['draft', 'need_revision'], true)) {
                throw ValidationException::withMessages(['status' => 'FPK hanya bisa submit dari draft atau need_revision.']);
            }

            $chains = ApprovalChain::query()
                ->where('department_id', $fpk->department_id)
                ->whereNotNull('approver_user_id')
                ->with('approverUser')
                ->orderBy('id')
                ->get();

            $this->ensureApprovalChainCanSubmit($chains);

            $fpk->update(['status' => 'requested']);
            $fpk->approvalRecords()->delete();

            $chains->each(function (ApprovalChain $chain) use ($fpk): void {
                ApprovalRecord::query()->create([
                    'recruitment_request_id' => $fpk->id,
                    'approval_chain_id' => $chain->id,
                    'approver_id' => $chain->approver_user_id,
                    'action' => 'waiting',
                ]);
            });

            $fpk->update(['status' => 'in_approval']);
        });

        $fpk->refresh();
        $this->notificationService->notifyFpkSubmitted($fpk, $this->pendingApproversFor($fpk), $this->notificationService->hrUsers());
    }

    /**
     * @param  Collection<int, ApprovalChain>  $chains
     */
    private function ensureApprovalChainCanSubmit(Collection $chains): void
    {
        if ($chains->isEmpty()) {
            throw ValidationException::withMessages(['department_id' => 'Approver department belum tersedia.']);
        }
    }

    public function approve(RecruitmentRequest $fpk, User $actor, ?string $comment): void
    {
        DB::transaction(function () use ($fpk, $actor, $comment): void {
            $record = $this->waitingRecordForActor($fpk, $actor);

            $record->update(['action' => 'approved', 'comment' => $comment, 'acted_at' => now()]);

            $hasWaitingOrRejected = $fpk->approvalRecords()
                ->whereIn('action', ['waiting', 'rejected', 'need_revision'])
                ->exists();

            if (! $hasWaitingOrRejected) {
                $fpk->update(['status' => 'approved']);
            }
        });

        $fpk->refresh();
        if ($fpk->status === 'approved') {
            $this->notificationService->notifyFpkApproved($fpk, $fpk->requester, $this->notificationService->hrUsers());
        }
    }

    public function reject(RecruitmentRequest $fpk, User $actor, string $comment): void
    {
        $comment = trim($comment);
        if ($comment === '') {
            throw ValidationException::withMessages(['comment' => 'Komentar wajib diisi saat reject.']);
        }

        DB::transaction(function () use ($fpk, $actor, $comment): void {
            $record = $this->waitingRecordForActor($fpk, $actor);
            $record->update(['action' => 'rejected', 'comment' => $comment, 'acted_at' => now()]);
            $fpk->update(['status' => 'rejected']);
        });

        $fpk->refresh();
        $this->notificationService->notifyFpkRejected($fpk, $fpk->requester, $this->notificationService->hrUsers());
    }

    public function needRevision(RecruitmentRequest $fpk, User $actor, string $comment): void
    {
        $comment = trim($comment);
        if ($comment === '') {
            throw ValidationException::withMessages(['comment' => 'Komentar wajib diisi saat need revision.']);
        }

        DB::transaction(function () use ($fpk, $actor, $comment): void {
            $record = $this->waitingRecordForActor($fpk, $actor);
            $record->update(['action' => 'need_revision', 'comment' => $comment, 'acted_at' => now()]);
            $fpk->update(['status' => 'need_revision']);
        });

        $fpk->refresh();
        $this->notificationService->notifyFpkNeedRevision($fpk, $fpk->requester, $this->notificationService->hrUsers());
    }

    public function close(RecruitmentRequest $fpk, User $actor): void
    {
        if ($fpk->status !== 'approved') {
            throw ValidationException::withMessages(['status' => 'FPK hanya bisa close setelah approved.']);
        }

        $fpk->update(['status' => 'closed']);
    }

    private function ensureEditable(RecruitmentRequest $fpk): void
    {
        if (! in_array($fpk->status, ['draft', 'need_revision'], true)) {
            throw ValidationException::withMessages(['status' => 'FPK hanya bisa diedit saat draft atau need_revision.']);
        }
    }

    private function waitingRecordForActor(RecruitmentRequest $fpk, User $actor): ApprovalRecord
    {
        if ($fpk->status !== 'in_approval') {
            throw ValidationException::withMessages(['status' => 'FPK tidak sedang dalam approval.']);
        }

        $record = $fpk->approvalRecords()
            ->where('approver_id', $actor->id)
            ->where('action', 'waiting')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages(['approval' => 'Approval menunggu untuk user ini tidak ditemukan.']);
        }

        return $record;
    }

    private function pendingApproversFor(RecruitmentRequest $fpk): Collection
    {
        $approverIds = $fpk->approvalRecords()
            ->where('action', 'waiting')
            ->whereNotNull('approver_id')
            ->pluck('approver_id');

        return User::query()->whereKey($approverIds)->get();
    }
}
