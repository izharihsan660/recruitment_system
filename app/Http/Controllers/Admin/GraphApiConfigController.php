<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGraphApiConfigRequest;
use App\Http\Requests\Admin\TestGraphApiConnectionRequest;
use App\Http\Requests\Admin\UpdateGraphApiConfigRequest;
use App\Http\Resources\GraphApiConfigResource;
use App\Models\GraphApiConfig;
use App\Services\GraphApiConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GraphApiConfigController extends Controller
{
    public function __construct(private readonly GraphApiConfigService $graphApiConfigService) {}

    public function index(): AnonymousResourceCollection
    {
        return GraphApiConfigResource::collection(GraphApiConfig::query()->latest()->paginate());
    }

    public function store(StoreGraphApiConfigRequest $request): RedirectResponse
    {
        $this->graphApiConfigService->create($request->validated());

        return redirect()->back()->with('success', 'Konfigurasi Graph API berhasil dibuat.');
    }

    public function show(GraphApiConfig $graphApiConfig): GraphApiConfigResource
    {
        return new GraphApiConfigResource($graphApiConfig);
    }

    public function update(UpdateGraphApiConfigRequest $request, GraphApiConfig $graphApiConfig): RedirectResponse
    {
        $this->graphApiConfigService->update($graphApiConfig, $request->validated());

        return redirect()->back()->with('success', 'Konfigurasi Graph API berhasil diperbarui.');
    }

    public function destroy(GraphApiConfig $graphApiConfig): RedirectResponse
    {
        $this->graphApiConfigService->delete($graphApiConfig);

        return redirect()->back()->with('success', 'Konfigurasi Graph API berhasil dihapus.');
    }

    public function testConnection(TestGraphApiConnectionRequest $request, GraphApiConfig $graphApiConfig): JsonResponse
    {
        $this->graphApiConfigService->testConnection($graphApiConfig);

        return response()->json(['message' => 'Microsoft Graph token generated successfully.']);
    }
}
