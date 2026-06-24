<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEntityRequest;
use App\Http\Requests\Admin\UpdateEntityRequest;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use App\Services\EntityService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EntityController extends Controller
{
    public function __construct(private readonly EntityService $entityService) {}

    public function index(): AnonymousResourceCollection
    {
        return EntityResource::collection(Entity::query()->latest()->paginate());
    }

    public function store(StoreEntityRequest $request): EntityResource
    {
        return new EntityResource($this->entityService->create($request->validated()));
    }

    public function show(Entity $entity): EntityResource
    {
        return new EntityResource($entity);
    }

    public function update(UpdateEntityRequest $request, Entity $entity): EntityResource
    {
        return new EntityResource($this->entityService->update($entity, $request->validated()));
    }

    public function destroy(Entity $entity): Response
    {
        $this->entityService->delete($entity);

        return response()->noContent();
    }
}
