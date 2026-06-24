<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::query()->with(['department.entity', 'roles'])->latest()->paginate());
    }

    public function store(StoreUserRequest $request): UserResource
    {
        return new UserResource($this->userService->create($request->validated())->load(['department.entity', 'roles']));
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load(['department.entity', 'roles']));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->userService->update($user, $request->validated())->load(['department.entity', 'roles']));
    }

    public function destroy(User $user): Response
    {
        $this->userService->delete($user);

        return response()->noContent();
    }
}
