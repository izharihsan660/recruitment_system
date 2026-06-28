<?php

namespace App\Models;

use Database\Factories\CompanyProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanyProfile extends Model
{
    /** @use HasFactory<CompanyProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'company_name', 'tagline', 'hero_image_path', 'about', 'values', 'gallery', 'address', 'email', 'phone',
    ];

    protected $attributes = [
        'company_name' => '',
        'about' => '',
        'values' => '[]',
        'gallery' => '[]',
    ];

    protected $appends = ['full_gallery_urls', 'full_hero_image_url'];

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'gallery' => 'array',
        ];
    }

    public function getFullGalleryUrlsAttribute(): array
    {
        return collect($this->gallery ?? [])
            ->map(fn ($path) => Storage::url($path))
            ->values()
            ->toArray();
    }

    public function getFullHeroImageUrlAttribute(): ?string
    {
        return $this->hero_image_path
            ? Storage::url($this->hero_image_path)
            : null;
    }
}
