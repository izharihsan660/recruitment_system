<?php

namespace App\Services;

use App\Models\CompanySigner;

class CompanySignerService extends AdminCrudService
{
    protected function modelClass(): string
    {
        return CompanySigner::class;
    }
}
