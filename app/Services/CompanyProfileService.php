<?php

namespace App\Services;

use App\Models\CompanyProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanyProfileService
{
    public function current(): CompanyProfile
    {
        return CompanyProfile::query()->firstOrCreate([], [
            'company_name' => '',
            'about' => '',
            'values' => [],
            'gallery' => [],
        ]);
    }

    public function update(array $data): CompanyProfile
    {
        $profile = $this->current();
        $profile->update($data);

        return $profile;
    }

    public function replaceHeroImage(UploadedFile $file): CompanyProfile
    {
        $profile = $this->current();

        if ($profile->hero_image_path) {
            Storage::disk('public')->delete($profile->hero_image_path);
        }

        $profile->update(['hero_image_path' => $file->store('company', 'public')]);

        return $profile;
    }

    public function addGalleryImage(UploadedFile $file): CompanyProfile
    {
        $profile = $this->current();
        $gallery = $profile->gallery ?? [];
        $gallery[] = $file->store('company', 'public');
        $profile->update(['gallery' => $gallery]);

        return $profile;
    }

    public function deleteGalleryImage(int $index): CompanyProfile
    {
        $profile = $this->current();
        $gallery = $profile->gallery ?? [];

        if (array_key_exists($index, $gallery)) {
            Storage::disk('public')->delete($gallery[$index]);
            array_splice($gallery, $index, 1);
            $profile->update(['gallery' => $gallery]);
        }

        return $profile;
    }
}
