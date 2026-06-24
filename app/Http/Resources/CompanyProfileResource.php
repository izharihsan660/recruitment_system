<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'tagline' => $this->tagline,
            'hero_image_path' => $this->hero_image_path,
            'full_hero_image_url' => $this->hero_image_path ? Storage::disk('public')->url($this->hero_image_path) : null,
            'about' => $this->about,
            'values' => $this->values,
            'gallery' => $this->gallery,
            'full_gallery_urls' => collect($this->gallery ?? [])->map(fn (string $path): string => Storage::disk('public')->url($path))->values(),
            'address' => $this->address,
            'email' => $this->email,
            'phone' => $this->phone,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
