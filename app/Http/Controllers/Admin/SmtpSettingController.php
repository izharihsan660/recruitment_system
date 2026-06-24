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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SmtpSettingController extends Controller
{
    public function __construct(private readonly SmtpSettingService $smtpSettingService) {}

    public function index(): AnonymousResourceCollection
    {
        return SmtpSettingResource::collection(SmtpSetting::query()->latest()->paginate());
    }

    public function store(StoreSmtpSettingRequest $request): SmtpSettingResource
    {
        return new SmtpSettingResource($this->smtpSettingService->create($request->validated()));
    }

    public function show(SmtpSetting $smtpSetting): SmtpSettingResource
    {
        return new SmtpSettingResource($smtpSetting);
    }

    public function update(UpdateSmtpSettingRequest $request, SmtpSetting $smtpSetting): SmtpSettingResource
    {
        return new SmtpSettingResource($this->smtpSettingService->update($smtpSetting, $request->validated()));
    }

    public function destroy(SmtpSetting $smtpSetting): Response
    {
        $this->smtpSettingService->delete($smtpSetting);

        return response()->noContent();
    }

    public function testConnection(TestSmtpConnectionRequest $request, SmtpSetting $smtpSetting): JsonResponse
    {
        $this->smtpSettingService->testConnection($smtpSetting, $request->validated('email'));

        return response()->json(['message' => 'SMTP connection test email sent.']);
    }
}
