<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Entity;
use App\Models\GraphApiConfig;
use App\Models\SmtpSetting;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->postJson('/admin/entities', [
                'name' => 'PT Nusantara Abadi Jaya',
                'short_name' => 'NAJ',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.short_name', 'NAJ');

        $this->assertDatabaseHas('entities', ['short_name' => 'NAJ']);
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

    public function test_approval_chain_requires_last_level_hr_manager_role(): void
    {
        $admin = $this->adminUser();
        $department = Department::factory()->create();

        $this->actingAs($admin)
            ->postJson('/admin/approval-chains', [
                'department_id' => $department->id,
                'level' => 1,
                'type' => 'user',
                'approver_user_id' => $admin->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('approver_role');

        $this->actingAs($admin)
            ->postJson('/admin/approval-chains', [
                'department_id' => $department->id,
                'level' => 1,
                'type' => 'role',
                'approver_role' => Roles::HrManager,
            ])
            ->assertCreated();
    }

    public function test_smtp_and_graph_secrets_are_encrypted_and_single_active(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->postJson('/admin/smtp-settings', array_merge(SmtpSetting::factory()->make()->toArray(), [
            'password' => 'smtp-secret-one',
            'is_active' => true,
        ]))->assertCreated();

        $this->actingAs($admin)->postJson('/admin/smtp-settings', array_merge(SmtpSetting::factory()->make()->toArray(), [
            'password' => 'smtp-secret-two',
            'is_active' => true,
        ]))->assertCreated();

        $this->assertSame(1, SmtpSetting::query()->where('is_active', true)->count());
        $this->assertDatabaseMissing('smtp_settings', ['password' => 'smtp-secret-two']);

        $this->actingAs($admin)->postJson('/admin/graph-api-configs', array_merge(GraphApiConfig::factory()->make()->toArray(), [
            'client_secret' => 'graph-secret-one',
            'is_active' => true,
        ]))->assertCreated();

        $this->actingAs($admin)->postJson('/admin/graph-api-configs', array_merge(GraphApiConfig::factory()->make()->toArray(), [
            'client_secret' => 'graph-secret-two',
            'is_active' => true,
        ]))->assertCreated();

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
