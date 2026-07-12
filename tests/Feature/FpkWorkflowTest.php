<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\Department;
use App\Models\Entity;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Notifications\FpkApprovalRequestedNotification;
use App\Notifications\FpkStatusChangedNotification;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FpkWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Entity $entity;

    private Department $department;

    private User $requester;

    private User $approver;

    private User $secondApprover;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (Roles::all() as $role) {
            Role::findOrCreate($role, 'web');
        }

        Mail::fake();

        $this->entity = Entity::factory()->create();
        $this->department = Department::factory()->for($this->entity)->create();
        $this->requester = User::factory()->for($this->department)->create(['is_active' => true]);
        $this->requester->assignRole(Roles::HiringManager);
        $this->approver = User::factory()->for($this->department)->create(['is_active' => true]);
        $this->approver->assignRole(Roles::Approver);
        $this->secondApprover = User::factory()->for($this->department)->create(['is_active' => true]);
        $this->secondApprover->assignRole(Roles::HrManager);

        ApprovalChain::factory()->for($this->department)->create([
            'approver_user_id' => $this->approver->id,
        ]);

        ApprovalChain::factory()->for($this->department)->create([
            'approver_user_id' => $this->secondApprover->id,
        ]);
    }

    public function test_fpk_submit_creates_waiting_records_for_all_approvers(): void
    {
        $fpk = $this->submittedFpk();

        $this->assertSame('in_approval', $fpk->status);
        $this->assertEqualsCanonicalizing(
            [$this->approver->id, $this->secondApprover->id],
            $fpk->approvalRecords()->pluck('approver_id')->all(),
        );
        $this->assertSame(['waiting', 'waiting'], $fpk->approvalRecords()->orderBy('approver_id')->pluck('action')->all());
    }

    public function test_fpk_submit_notifies_all_parallel_approvers_with_contextual_email(): void
    {
        Notification::fake();

        $fpk = $this->submittedFpk();

        foreach ([$this->approver, $this->secondApprover] as $approver) {
            Notification::assertSentTo($approver, FpkApprovalRequestedNotification::class, function (FpkApprovalRequestedNotification $notification) use ($approver, $fpk): bool {
                $mail = $notification->toMail($approver);

                return $mail->subject === "Permintaan Approval FPK - {$fpk->position_name} ({$this->department->name})"
                    && in_array('Pemohon: '.$this->requester->name, $mail->introLines, true)
                    && in_array('Alasan kebutuhan: '.$fpk->reason_notes, $mail->introLines, true);
            });
        }
    }

    public function test_fpk_final_status_notifies_requester_with_relevant_content(): void
    {
        Notification::fake();
        $fpk = $this->submittedFpk();

        $this->actingAs($this->secondApprover)->post(route('fpk.reject', $fpk), ['comment' => 'Budget belum tersedia']);

        Notification::assertSentTo($this->requester, FpkStatusChangedNotification::class, function (FpkStatusChangedNotification $notification) use ($fpk): bool {
            $mail = $notification->toMail($this->requester);

            return $mail->subject === 'FPK Anda Ditolak - '.$fpk->position_name
                && in_array('Alasan: Budget belum tersedia', $mail->introLines, true);
        });
    }

    public function test_fpk_need_revision_notification_links_requester_to_edit_page(): void
    {
        Notification::fake();
        $fpk = $this->submittedFpk();

        $this->actingAs($this->approver)->post(route('fpk.need-revision', $fpk), ['comment' => 'Lengkapi kualifikasi']);

        Notification::assertSentTo($this->requester, FpkStatusChangedNotification::class, function (FpkStatusChangedNotification $notification) use ($fpk): bool {
            $mail = $notification->toMail($this->requester);

            return $mail->subject === 'FPK Memerlukan Revisi - '.$fpk->position_name
                && in_array('Catatan revisi: Lengkapi kualifikasi', $mail->introLines, true)
                && $mail->actionUrl === route('fpk.edit', $fpk);
        });
    }

    public function test_second_approver_can_approve_first_and_fpk_approved_after_all_approve(): void
    {
        $fpk = $this->submittedFpk();

        $this->actingAs($this->secondApprover)
            ->post(route('fpk.approve', $fpk), ['comment' => 'Second OK'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil disetujui.');

        $this->assertSame('in_approval', $fpk->refresh()->status);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'approver_id' => $this->secondApprover->id,
            'action' => 'approved',
        ]);

        $this->actingAs($this->approver)
            ->post(route('fpk.approve', $fpk), ['comment' => 'OK'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil disetujui.');

        $this->assertSame('approved', $fpk->refresh()->status);
        $this->assertSame(['approved', 'approved'], $fpk->approvalRecords()->orderBy('approver_id')->pluck('action')->all());
    }

    public function test_fpk_submit_requires_approval_chain(): void
    {
        ApprovalChain::query()
            ->where('department_id', $this->department->id)
            ->delete();

        $fpk = $this->createFpk();

        $this->actingAs($this->requester)
            ->postJson(route('fpk.submit', $fpk))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('department_id');

        $this->assertSame('draft', $fpk->refresh()->status);
    }

    public function test_fpk_reject_from_any_approver_immediately_rejects_fpk(): void
    {
        $fpk = $this->submittedFpk();

        $this->actingAs($this->secondApprover)
            ->post(route('fpk.reject', $fpk), ['comment' => 'Budget belum tersedia'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil ditolak.');

        $this->assertSame('rejected', $fpk->refresh()->status);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'approver_id' => $this->secondApprover->id,
            'action' => 'rejected',
            'comment' => 'Budget belum tersedia',
        ]);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'approver_id' => $this->approver->id,
            'action' => 'waiting',
        ]);
    }

    public function test_http_two_approvers_out_of_order_approval(): void
    {
        $fpk = $this->submittedFpk();

        $this->actingAs($this->secondApprover)
            ->post(route('fpk.approve', $fpk), ['comment' => 'Second approver approves first'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil disetujui.');

        $this->assertSame('in_approval', $fpk->refresh()->status);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'approver_id' => $this->secondApprover->id,
            'action' => 'approved',
        ]);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'approver_id' => $this->approver->id,
            'action' => 'waiting',
        ]);

        $this->actingAs($this->approver)
            ->post(route('fpk.approve', $fpk), ['comment' => 'First approver approves after second'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil disetujui.');

        $this->assertSame('approved', $fpk->refresh()->status);
    }

    public function test_http_reject_from_non_first_approver_immediately_rejects(): void
    {
        $fpk = $this->submittedFpk();

        $this->actingAs($this->secondApprover)
            ->post(route('fpk.reject', $fpk), ['comment' => 'Reject before first approver votes'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil ditolak.');

        $this->assertSame('rejected', $fpk->refresh()->status);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'approver_id' => $this->secondApprover->id,
            'action' => 'rejected',
            'comment' => 'Reject before first approver votes',
        ]);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'approver_id' => $this->approver->id,
            'action' => 'waiting',
        ]);
    }

    public function test_fpk_need_revision_returns_to_requester_and_resubmit_recreates_records(): void
    {
        $fpk = $this->submittedFpk();
        $firstRecordIds = $fpk->approvalRecords()->pluck('id')->all();

        $this->actingAs($this->approver)
            ->post(route('fpk.need-revision', $fpk), ['comment' => 'Lengkapi kualifikasi'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK dikembalikan untuk revisi.');

        $this->assertSame('need_revision', $fpk->refresh()->status);

        $this->actingAs($this->requester)
            ->post(route('fpk.submit', $fpk))
            ->assertRedirect(route('fpk.show', $fpk));

        $newRecordIds = $fpk->refresh()->approvalRecords()->pluck('id')->all();
        $this->assertSame('in_approval', $fpk->status);
        $this->assertEmpty(array_intersect($firstRecordIds, $newRecordIds));
        $this->assertSame(['waiting', 'waiting'], $fpk->approvalRecords()->orderBy('approver_id')->pluck('action')->all());
    }

    public function test_unauthorized_actor_cannot_approve_without_own_waiting_record(): void
    {
        $fpk = $this->submittedFpk();
        $otherApprover = User::factory()->for($this->department)->create(['is_active' => true]);
        $otherApprover->assignRole(Roles::Approver);

        $this->actingAs($otherApprover)->postJson(route('fpk.approve', $fpk), ['comment' => 'OK'])->assertUnprocessable();

        $this->assertSame('in_approval', $fpk->refresh()->status);
    }

    public function test_reject_without_comment_is_rejected_by_validation(): void
    {
        $fpk = $this->submittedFpk();

        $this->actingAs($this->approver)->postJson(route('fpk.reject', $fpk), [])->assertUnprocessable()->assertJsonValidationErrors('comment');

        $this->assertSame('in_approval', $fpk->refresh()->status);
    }

    public function test_hiring_manager_can_create_fpk_with_fsd_facilities(): void
    {
        $payload = $this->validFpkPayload();

        $this->actingAs($this->requester)
            ->post(route('fpk.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'FPK berhasil dibuat.');

        $fpk = RecruitmentRequest::query()->where('requester_id', $this->requester->id)->latest('id')->firstOrFail();

        $this->assertSame($payload['facilities'], $fpk->facilities);
    }

    public function test_fpk_store_requires_all_fsd_facility_keys(): void
    {
        $payload = $this->validFpkPayload();
        unset($payload['facilities']['salary_gross']);

        $this->actingAs($this->requester)
            ->post(route('fpk.store'), $payload)
            ->assertSessionHasErrors('facilities.salary_gross');
    }

    public function test_user_without_hiring_manager_role_cannot_create_fpk(): void
    {
        $user = User::factory()->for($this->department)->create(['is_active' => true]);

        $this->actingAs($user)
            ->post(route('fpk.store'), $this->validFpkPayload())
            ->assertForbidden();
    }

    private function createFpk(): RecruitmentRequest
    {
        $payload = $this->validFpkPayload();

        $this->actingAs($this->requester)
            ->post(route('fpk.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success', 'FPK berhasil dibuat.');

        return RecruitmentRequest::query()->where('requester_id', $this->requester->id)->latest('id')->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function validFpkPayload(): array
    {
        $payload = RecruitmentRequest::factory()->make([
            'entity_id' => $this->entity->id,
            'department_id' => $this->department->id,
            'requester_id' => $this->requester->id,
        ])->toArray();

        unset($payload['requester_id'], $payload['status']);

        return $payload;
    }

    private function submittedFpk(): RecruitmentRequest
    {
        $fpk = $this->createFpk();
        $this->actingAs($this->requester)
            ->post(route('fpk.submit', $fpk))
            ->assertRedirect(route('fpk.show', $fpk));

        return $fpk->refresh();
    }
}
