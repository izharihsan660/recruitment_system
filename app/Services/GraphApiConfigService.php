<?php

namespace App\Services;

use App\Models\GraphApiConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GraphApiConfigService extends AdminCrudService
{
    public function create(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $this->deactivateOthersWhenActive($data);

            return parent::create($data);
        });
    }

    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $this->deactivateOthersWhenActive($data, $model->getKey());

            return parent::update($model, $data);
        });
    }

    public function testConnection(GraphApiConfig $config): void
    {
        Http::asForm()
            ->timeout(10)
            ->post("https://login.microsoftonline.com/{$config->tenant_id}/oauth2/v2.0/token", [
                'client_id' => $config->client_id,
                'client_secret' => $config->client_secret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ])
            ->throw();
    }

    protected function modelClass(): string
    {
        return GraphApiConfig::class;
    }

    private function deactivateOthersWhenActive(array $data, ?int $exceptId = null): void
    {
        if (($data['is_active'] ?? false) !== true) {
            return;
        }

        GraphApiConfig::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->update(['is_active' => false]);
    }
}
