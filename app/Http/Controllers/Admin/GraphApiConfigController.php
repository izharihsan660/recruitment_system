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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GraphApiConfigController extends Controller
{
    public function __construct(private readonly GraphApiConfigService $graphApiConfigService) {}

    public function index(): AnonymousResourceCollection
    {
        return GraphApiConfigResource::collection(GraphApiConfig::query()->latest()->paginate());
    }

    public function store(StoreGraphApiConfigRequest $request): GraphApiConfigResource
    {
        return new GraphApiConfigResource($this->graphApiConfigService->create($request->validated()));
    }

    public function show(GraphApiConfig $graphApiConfig): GraphApiConfigResource
    {
        return new GraphApiConfigResource($graphApiConfig);
    }

    public function update(UpdateGraphApiConfigRequest $request, GraphApiConfig $graphApiConfig): GraphApiConfigResource
    {
        return new GraphApiConfigResource($this->graphApiConfigService->update($graphApiConfig, $request->validated()));
    }

    public function destroy(GraphApiConfig $graphApiConfig): Response
    {
        $this->graphApiConfigService->delete($graphApiConfig);

        return response()->noContent();
    }

    public function testConnection(TestGraphApiConnectionRequest $request, GraphApiConfig $graphApiConfig): JsonResponse
    {
        $this->graphApiConfigService->testConnection($graphApiConfig);

        return response()->json(['message' => 'Microsoft Graph token generated successfully.']);
    }
}
