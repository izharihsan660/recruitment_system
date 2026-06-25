<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEntityRequest;
use App\Http\Requests\Admin\UpdateEntityRequest;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use App\Services\EntityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EntityController extends Controller
{
    public function __construct(private readonly EntityService $entityService) {}

    public function index(): AnonymousResourceCollection
    {
        return EntityResource::collection(Entity::query()->latest()->paginate());
    }

    public function store(StoreEntityRequest $request): RedirectResponse
    {
        $this->entityService->create($request->validated());

        return redirect()->back()->with('success', 'Entitas berhasil dibuat.');
    }

    public function show(Entity $entity): EntityResource
    {
        return new EntityResource($entity);
    }

    public function update(UpdateEntityRequest $request, Entity $entity): RedirectResponse
    {
        $this->entityService->update($entity, $request->validated());

        return redirect()->back()->with('success', 'Entitas berhasil diperbarui.');
    }

    public function destroy(Entity $entity): RedirectResponse
    {
        $this->entityService->delete($entity);

        return redirect()->back()->with('success', 'Entitas berhasil dihapus.');
    }
}
