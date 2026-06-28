<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocusealConfigRequest;
use App\Http\Requests\Admin\TestDocusealConnectionRequest;
use App\Http\Requests\Admin\UpdateDocusealConfigRequest;
use App\Http\Resources\DocusealConfigResource;
use App\Models\DocusealConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DocusealConfigController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DocusealConfigResource::collection(DocusealConfig::query()->latest()->paginate());
    }

    public function store(StoreDocusealConfigRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): DocusealConfig {
            $data = $request->validated();
            $this->deactivateOthersWhenActive($data);

            return DocusealConfig::query()->create($data);
        });

        return redirect()->back()->with('success', 'Konfigurasi DocuSeal berhasil dibuat.');
    }

    public function show(DocusealConfig $docusealConfig): DocusealConfigResource
    {
        return new DocusealConfigResource($docusealConfig);
    }

    public function update(UpdateDocusealConfigRequest $request, DocusealConfig $docusealConfig): RedirectResponse
    {
        DB::transaction(function () use ($request, $docusealConfig): DocusealConfig {
            $data = collect($request->validated())
                ->reject(fn ($value, string $key): bool => in_array($key, ['api_key', 'webhook_secret'], true) && blank($value))
                ->all();

            $this->deactivateOthersWhenActive($data, $docusealConfig->id);
            $docusealConfig->update($data);

            return $docusealConfig->refresh();
        });

        return redirect()->back()->with('success', 'Konfigurasi DocuSeal berhasil diperbarui.');
    }

    public function destroy(DocusealConfig $docusealConfig): RedirectResponse
    {
        $docusealConfig->delete();

        return redirect()->back()->with('success', 'Konfigurasi DocuSeal berhasil dihapus.');
    }

    public function testConnection(TestDocusealConnectionRequest $request, DocusealConfig $docusealConfig): JsonResponse
    {
        Http::withHeaders(['X-Auth-Token' => $docusealConfig->api_key])
            ->acceptJson()
            ->timeout(10)
            ->get(rtrim($docusealConfig->api_url, '/').'/submissions', ['limit' => 1])
            ->throw();

        return response()->json(['message' => 'Koneksi DocuSeal berhasil.']);
    }

    private function deactivateOthersWhenActive(array $data, ?int $exceptId = null): void
    {
        if (($data['is_active'] ?? false) !== true) {
            return;
        }

        DocusealConfig::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->update(['is_active' => false]);
    }
}
