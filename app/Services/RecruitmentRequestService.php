<?php

namespace App\Services;

use App\Models\ApprovalChain;
use App\Models\ApprovalRecord;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Roles;
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
        $data['current_approval_level'] = null;

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
                ->orderBy('level')
                ->get();

            $this->ensureApprovalChainCanSubmit($chains);

            $fpk->update(['status' => 'requested']);
            $fpk->approvalRecords()->delete();

            $chains->each(function (ApprovalChain $chain) use ($fpk): void {
                ApprovalRecord::query()->create([
                    'recruitment_request_id' => $fpk->id,
                    'approval_chain_id' => $chain->id,
                    'level' => $chain->level,
                    'approver_id' => $chain->approver_user_id,
                    'action' => 'waiting',
                ]);
            });

            $fpk->update(['current_approval_level' => 1, 'status' => 'in_approval']);
        });

        $fpk->refresh();
        $this->notificationService->notifyFpkSubmitted($fpk, $this->approversForCurrentLevel($fpk), $this->notificationService->hrUsers());
    }

    /**
     * @param  Collection<int, ApprovalChain>  $chains
     */
    private function ensureApprovalChainCanSubmit(Collection $chains): void
    {
        if ($chains->isEmpty()) {
            throw ValidationException::withMessages(['department_id' => 'Approval chain department belum tersedia.']);
        }

        if ($chains->count() > 3) {
            throw ValidationException::withMessages(['level' => 'Maksimal 3 approval level per department.']);
        }

        $expectedLevels = range(1, $chains->count());
        if ($chains->pluck('level')->values()->all() !== $expectedLevels) {
            throw ValidationException::withMessages(['level' => 'Level approval harus berurutan tanpa gap.']);
        }

        $lastChain = $chains->last();
        if ($lastChain->type !== 'role' || ! in_array($lastChain->approver_role, [Roles::HrManager, Roles::HrRecruiter], true)) {
            throw ValidationException::withMessages([
                'approver_role' => 'Level terakhir harus bertipe role hr_manager atau hr_recruiter.',
            ]);
        }
    }

    public function approve(RecruitmentRequest $fpk, User $actor, ?string $comment): void
    {
        DB::transaction(function () use ($fpk, $actor, $comment): void {
            $record = $this->currentWaitingRecord($fpk);
            $this->ensureCanActOnRecord($record, $actor);

            $record->update(['action' => 'approved', 'comment' => $comment, 'acted_at' => now()]);

            $nextRecord = $fpk->approvalRecords()->where('level', '>', $record->level)->orderBy('level')->first();

            if ($nextRecord) {
                $fpk->update(['current_approval_level' => $nextRecord->level]);

                return;
            }

            $fpk->update(['status' => 'approved', 'current_approval_level' => null]);
        });

        $fpk->refresh();
        if ($fpk->status === 'approved') {
            $this->notificationService->notifyFpkApproved($fpk, $fpk->requester, $this->notificationService->hrUsers());

            return;
        }

        $this->notificationService->notifyFpkSubmitted($fpk, $this->approversForCurrentLevel($fpk), $this->notificationService->hrUsers());
    }

    public function reject(RecruitmentRequest $fpk, User $actor, string $comment): void
    {
        $comment = trim($comment);
        if ($comment === '') {
            throw ValidationException::withMessages(['comment' => 'Komentar wajib diisi saat reject.']);
        }

        DB::transaction(function () use ($fpk, $actor, $comment): void {
            $record = $this->currentWaitingRecord($fpk);
            $this->ensureCanActOnRecord($record, $actor);
            $record->update(['action' => 'rejected', 'comment' => $comment, 'acted_at' => now()]);
            $fpk->update(['status' => 'rejected', 'current_approval_level' => null]);
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
            $record = $this->currentWaitingRecord($fpk);
            $this->ensureCanActOnRecord($record, $actor);
            $record->update(['action' => 'need_revision', 'comment' => $comment, 'acted_at' => now()]);
            $fpk->update(['status' => 'need_revision', 'current_approval_level' => null]);
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

    private function currentWaitingRecord(RecruitmentRequest $fpk): ApprovalRecord
    {
        if ($fpk->status !== 'in_approval' || $fpk->current_approval_level === null) {
            throw ValidationException::withMessages(['status' => 'FPK tidak sedang dalam approval.']);
        }

        $record = $fpk->approvalRecords()
            ->where('level', $fpk->current_approval_level)
            ->where('action', 'waiting')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages(['approval' => 'Approval level saat ini tidak ditemukan.']);
        }

        return $record->load('approvalChain');
    }

    private function ensureCanActOnRecord(ApprovalRecord $record, User $actor): void
    {
        $chain = $record->approvalChain;

        if ($chain->type === 'user' && (int) $chain->approver_user_id === (int) $actor->id) {
            return;
        }

        if ($chain->type === 'role' && $actor->hasAnyRole([Roles::HrManager, Roles::HrRecruiter])) {
            return;
        }

        throw ValidationException::withMessages(['approver' => 'User tidak berhak approve level ini.']);
    }

    private function approversForCurrentLevel(RecruitmentRequest $fpk): Collection
    {
        $record = $fpk->approvalRecords()->with('approvalChain')->where('level', $fpk->current_approval_level)->first();

        if (! $record) {
            return collect();
        }

        if ($record->approvalChain->type === 'user') {
            return User::query()->whereKey($record->approvalChain->approver_user_id)->get();
        }

        return User::role([Roles::HrManager, Roles::HrRecruiter], 'web')->get();
    }
}
