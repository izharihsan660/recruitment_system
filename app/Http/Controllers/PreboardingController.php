<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignPreboardingPicRequest;
use App\Http\Requests\StorePreboardingItemRequest;
use App\Models\Employee;
use App\Models\PreboardingChecklist;
use App\Models\PreboardingItem;
use App\Models\User;
use App\Services\PreboardingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PreboardingController extends Controller
{
    public function __construct(private readonly PreboardingService $preboardingService) {}

    public function index(): Response
    {
        return Inertia::render('Employees/Index', ['employees' => Employee::query()->with(['department', 'entity'])->where('status', 'active')->latest()->paginate(10)]);
    }

    public function show(Employee $employee): Response
    {
        return Inertia::render('Preboarding/Show', ['employee' => $employee->load(['department']), 'checklist' => $employee->preboardingChecklist?->load(['items.pic']) ?? $this->preboardingService->createFromTemplate($employee), 'users' => User::query()->where('is_active', true)->get(['id', 'name', 'email'])]);
    }

    public function storeItem(StorePreboardingItemRequest $request, PreboardingChecklist $checklist): RedirectResponse
    {
        $this->preboardingService->addItem($checklist, $request->validated());

        return back()->with('success', 'Item berhasil ditambahkan.');
    }

    public function destroyItem(PreboardingItem $item): RedirectResponse
    {
        $this->preboardingService->removeItem($item);

        return back()->with('success', 'Item berhasil dihapus.');
    }

    public function assign(AssignPreboardingPicRequest $request, PreboardingItem $item): RedirectResponse
    {
        $this->preboardingService->assignPic($item, User::query()->findOrFail($request->validated('assigned_to')));

        return back()->with('success', 'PIC berhasil ditugaskan.');
    }

    public function complete(PreboardingItem $item): RedirectResponse
    {
        $this->preboardingService->completeItem($item, request()->user());

        return back()->with('success', 'Item pre-boarding selesai.');
    }
}
