<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

abstract class AdminCrudService
{
    public function create(array $data): Model
    {
        return $this->modelClass()::query()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    abstract protected function modelClass(): string;
}
