<?php

namespace App\Services;

use App\Models\Department;

class DepartmentService extends AdminCrudService
{
    protected function modelClass(): string
    {
        return Department::class;
    }
}
