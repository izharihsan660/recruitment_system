<?php

namespace App\Services;

use App\Models\Entity;

class EntityService extends AdminCrudService
{
    protected function modelClass(): string
    {
        return Entity::class;
    }
}
