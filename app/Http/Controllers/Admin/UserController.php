<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()->with(['department.entity', 'roles'])->latest()->paginate(10),
            'departments' => Department::query()->with('entity')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'entity_id']),
            'roles' => Role::query()->orderBy('name')->pluck('name')->values(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->create($request->validated());

        return redirect()->back()->with('success', 'User berhasil dibuat.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()->back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->delete($user);

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }
}
