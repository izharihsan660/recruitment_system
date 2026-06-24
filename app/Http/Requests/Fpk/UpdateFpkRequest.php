<?php

namespace App\Http\Requests\Fpk;

use App\Models\RecruitmentRequest;

class UpdateFpkRequest extends StoreFpkRequest
{
    public function authorize(): bool
    {
        $fpk = $this->route('fpk');

        return $fpk instanceof RecruitmentRequest && ($this->user()?->can('update', $fpk) ?? false);
    }
}
