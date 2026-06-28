<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSmtpSettingRequest;
use App\Http\Requests\Admin\TestSmtpConnectionRequest;
use App\Http\Requests\Admin\UpdateSmtpSettingRequest;
use App\Http\Resources\SmtpSettingResource;
use App\Models\SmtpSetting;
use App\Services\SmtpSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SmtpSettingController extends Controller
{
    public function __construct(private readonly SmtpSettingService $smtpSettingService) {}

    public function index(): AnonymousResourceCollection
    {
        return SmtpSettingResource::collection(SmtpSetting::query()->latest()->paginate());
    }

    public function store(StoreSmtpSettingRequest $request): RedirectResponse
    {
        $this->smtpSettingService->create($request->validated());

        return redirect()->back()->with('success', 'Pengaturan SMTP berhasil dibuat.');
    }

    public function show(SmtpSetting $smtpSetting): SmtpSettingResource
    {
        return new SmtpSettingResource($smtpSetting);
    }

    public function update(UpdateSmtpSettingRequest $request, SmtpSetting $smtpSetting): RedirectResponse
    {
        $data = collect($request->validated())
            ->reject(fn ($value, string $key): bool => $key === 'password' && blank($value))
            ->all();

        $this->smtpSettingService->update($smtpSetting, $data);

        return redirect()->back()->with('success', 'Pengaturan SMTP berhasil diperbarui.');
    }

    public function destroy(SmtpSetting $smtpSetting): RedirectResponse
    {
        $this->smtpSettingService->delete($smtpSetting);

        return redirect()->back()->with('success', 'Pengaturan SMTP berhasil dihapus.');
    }

    public function testConnection(TestSmtpConnectionRequest $request, SmtpSetting $smtpSetting): JsonResponse
    {
        $this->smtpSettingService->testConnection($smtpSetting, $request->validated('email'));

        return response()->json(['message' => 'SMTP connection test email sent.']);
    }
}
