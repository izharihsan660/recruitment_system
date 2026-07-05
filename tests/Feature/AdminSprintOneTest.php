<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\GraphApiConfig;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSprintOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_seeder_creates_internal_web_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        foreach (Roles::all() as $role) {
            $this->assertTrue(Role::query()->where('name', $role)->where('guard_name', 'web')->exists());
        }
    }

    public function test_self_registration_routes_are_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_inactive_user_is_redirected_to_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_create_entity_via_admin_api(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post('/admin/entities', [
                'name' => 'PT Nusantara Abadi Jaya',
                'short_name' => 'NAJ',
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Entitas berhasil dibuat.');

        $this->assertDatabaseHas('entities', ['short_name' => 'NAJ']);
    }

    public function test_admin_can_open_user_management_page(): void
    {
        $admin = $this->adminUser();
        $department = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole(Roles::HrRecruiter);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 2)
                ->has('departments', 1)
                ->where('roles.0', Roles::Admin)
            );
    }

    public function test_admin_can_create_user_with_role_and_department(): void
    {
        $admin = $this->adminUser();
        $department = Department::factory()->create();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Recruiter Baru',
                'email' => 'recruiter@example.test',
                'password' => 'password123',
                'department_id' => $department->id,
                'is_active' => true,
                'roles' => [Roles::HrRecruiter],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'User berhasil dibuat.');

        $createdUser = User::query()->where('email', 'recruiter@example.test')->firstOrFail();

        $this->assertSame($department->id, $createdUser->department_id);
        $this->assertTrue($createdUser->hasRole(Roles::HrRecruiter));
    }

    public function test_non_admin_cannot_access_admin_api(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole(Roles::HrRecruiter);

        $this->actingAs($user)
            ->getJson('/admin/entities')
            ->assertForbidden();
    }

    public function test_approval_chain_stores_multiple_user_approvers_without_levels(): void
    {
        $admin = $this->adminUser();
        $department = Department::factory()->create();
        $approver = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post('/admin/approval-chains', [
                'department_id' => $department->id,
                'user_ids' => [$admin->id, $approver->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Approval Chain berhasil dibuat.');

        $this->assertDatabaseHas('approval_chains', [
            'department_id' => $department->id,
            'type' => 'user',
            'approver_user_id' => $admin->id,
            'approver_role' => null,
        ]);
        $this->assertDatabaseHas('approval_chains', [
            'department_id' => $department->id,
            'type' => 'user',
            'approver_user_id' => $approver->id,
            'approver_role' => null,
        ]);

        $this->actingAs($admin)
            ->post('/admin/approval-chains', [
                'department_id' => $department->id,
                'user_ids' => [$admin->id],
            ])
            ->assertSessionHasErrors('user_ids.0');
    }

    public function test_smtp_and_graph_secrets_are_encrypted_and_single_active(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post('/admin/smtp-settings', array_merge(SmtpSetting::factory()->make()->toArray(), [
            'password' => 'smtp-secret-one',
            'is_active' => true,
        ]))->assertRedirect()->assertSessionHas('success');

        $this->actingAs($admin)->post('/admin/smtp-settings', array_merge(SmtpSetting::factory()->make()->toArray(), [
            'password' => 'smtp-secret-two',
            'is_active' => true,
        ]))->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, SmtpSetting::query()->where('is_active', true)->count());
        $this->assertDatabaseMissing('smtp_settings', ['password' => 'smtp-secret-two']);

        $this->actingAs($admin)->post('/admin/graph-api-configs', array_merge(GraphApiConfig::factory()->make()->toArray(), [
            'client_secret' => 'graph-secret-one',
            'is_active' => true,
        ]))->assertRedirect()->assertSessionHas('success');

        $this->actingAs($admin)->post('/admin/graph-api-configs', array_merge(GraphApiConfig::factory()->make()->toArray(), [
            'client_secret' => 'graph-secret-two',
            'is_active' => true,
        ]))->assertRedirect()->assertSessionHas('success');

        $this->assertSame(1, GraphApiConfig::query()->where('is_active', true)->count());
        $this->assertDatabaseMissing('graph_api_configs', ['client_secret' => 'graph-secret-two']);
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Roles::Admin);

        return $user;
    }
}
