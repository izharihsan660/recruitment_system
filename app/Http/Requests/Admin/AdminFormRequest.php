<?php

namespace App\Http\Requests\Admin;

use App\Support\Roles;
use Illuminate\Foundation\Http\FormRequest;

abstract class AdminFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Roles::Admin) === true;
    }
}
