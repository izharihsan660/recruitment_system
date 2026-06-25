<?php

namespace Tests\Feature;

use App\Models\ApprovalChain;
use App\Models\Department;
use App\Models\Entity;
use App\Models\RecruitmentRequest;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    private User $hrManager;

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
        $this->hrManager = User::factory()->for($this->department)->create(['is_active' => true]);
        $this->hrManager->assignRole(Roles::HrManager);

        ApprovalChain::factory()->for($this->department)->create([
            'level' => 1,
            'type' => 'user',
            'approver_user_id' => $this->approver->id,
            'approver_role' => null,
        ]);

        ApprovalChain::factory()->for($this->department)->create([
            'level' => 2,
            'type' => 'role',
            'approver_user_id' => null,
            'approver_role' => Roles::HrManager,
        ]);
    }

    public function test_fpk_draft_submit_approve_all_levels_becomes_approved(): void
    {
        $fpk = $this->createFpk();

        $this->actingAs($this->requester)
            ->post(route('fpk.submit', $fpk))
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil disubmit.');
        $this->assertSame('in_approval', $fpk->refresh()->status);
        $this->assertSame(1, $fpk->current_approval_level);

        $this->actingAs($this->approver)
            ->post(route('fpk.approve', $fpk), ['comment' => 'OK'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil disetujui.');
        $this->assertSame('in_approval', $fpk->refresh()->status);
        $this->assertSame(2, $fpk->current_approval_level);

        $this->actingAs($this->hrManager)
            ->post(route('fpk.approve', $fpk), ['comment' => 'Final OK'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil disetujui.');
        $this->assertSame('approved', $fpk->refresh()->status);
        $this->assertNull($fpk->current_approval_level);
        $this->assertSame(['approved', 'approved'], $fpk->approvalRecords()->orderBy('level')->pluck('action')->all());
    }

    public function test_fpk_submit_requires_final_approval_level_to_be_hr_role(): void
    {
        ApprovalChain::query()
            ->where('department_id', $this->department->id)
            ->where('level', 2)
            ->delete();

        $fpk = $this->createFpk();

        $this->actingAs($this->requester)
            ->postJson(route('fpk.submit', $fpk))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'approver_role' => 'Level terakhir harus bertipe role hr_manager atau hr_recruiter.',
            ]);

        $this->assertSame('draft', $fpk->refresh()->status);
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

    public function test_fpk_draft_submit_reject_saves_comment_and_rejected_status(): void
    {
        $fpk = $this->submittedFpk();

        $this->actingAs($this->approver)
            ->post(route('fpk.reject', $fpk), ['comment' => 'Budget belum tersedia'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK berhasil ditolak.');

        $this->assertSame('rejected', $fpk->refresh()->status);
        $this->assertDatabaseHas('approval_records', [
            'recruitment_request_id' => $fpk->id,
            'level' => 1,
            'action' => 'rejected',
            'comment' => 'Budget belum tersedia',
        ]);
    }

    public function test_fpk_need_revision_returns_to_requester(): void
    {
        $fpk = $this->submittedFpk();

        $this->actingAs($this->approver)
            ->post(route('fpk.need-revision', $fpk), ['comment' => 'Lengkapi kualifikasi'])
            ->assertRedirect(route('fpk.show', $fpk))
            ->assertSessionHas('success', 'FPK dikembalikan untuk revisi.');

        $this->assertSame('need_revision', $fpk->refresh()->status);
        $this->assertNull($fpk->current_approval_level);
    }

    public function test_unauthorized_actor_cannot_approve_current_level(): void
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

        unset($payload['requester_id'], $payload['status'], $payload['current_approval_level']);

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
