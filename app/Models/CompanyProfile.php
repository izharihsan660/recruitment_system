<?php

namespace App\Models;

use Database\Factories\CompanyProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected function casts(): array
    {
        return [
            'values' => 'array',
            'gallery' => 'array',
        ];
    }
}
