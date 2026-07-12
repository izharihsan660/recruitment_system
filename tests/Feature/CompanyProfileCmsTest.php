<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyProfileCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_company_profile_with_empty_culture_item(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->put('/admin/company-profile', [
                'company_name' => 'PT Nusantara Abadi Jaya',
                'tagline' => 'Mining recruitment partner',
                'about' => 'Kami membantu proses rekrutmen internal.',
                'values' => [
                    ['title' => '', 'description' => ''],
                ],
                'address' => 'Balikpapan',
                'email' => 'hr@example.test',
                'phone' => '08123456789',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Profil perusahaan berhasil diperbarui.');

        $profile = CompanyProfile::query()->firstOrFail();

        $this->assertSame('PT Nusantara Abadi Jaya', $profile->company_name);
        $this->assertSame([], $profile->values);
        $this->assertSame('hr@example.test', $profile->email);
    }

    public function test_admin_can_upload_company_profile_hero_image(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post('/admin/company-profile/hero-image', [
                'image' => UploadedFile::fake()->image('hero.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Aksi berhasil dijalankan.');

        $profile = CompanyProfile::query()->firstOrFail();

        $this->assertNotNull($profile->hero_image_path);
        Storage::disk('public')->assertExists($profile->hero_image_path);
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Roles::Admin);

        return $user;
    }
}
