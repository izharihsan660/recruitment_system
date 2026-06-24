<?php

namespace App\Services;

use App\Models\GraphApiConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SharePointService
{
    public function archiveDocument(string $localPath, array $metadata): string
    {
        $config = $this->config();
        $token = $this->token($config);
        $ptName = Str::of((string) $metadata['entity_name'])->replaceMatches('/[^A-Za-z0-9]+/', '-')->trim('-');
        $folder = '/Rekrutmen/'.$metadata['doc_type'].'/'.now()->year.'/'.$ptName;
        $this->getOrCreateFolder($folder);

        $fileName = $this->fileName($metadata);
        $path = trim($folder, '/').'/'.$fileName;
        $response = Http::withToken($token)
            ->withBody(file_get_contents($localPath), 'application/pdf')
            ->put('https://graph.microsoft.com/v1.0/users/'.$config->calendar_user_email.'/drive/root:/'.$path.':/content')
            ->throw()
            ->json();

        return (string) data_get($response, 'webUrl');
    }

    public function getOrCreateFolder(string $path): string
    {
        $config = $this->config();
        $token = $this->token($config);
        $parent = '';
        $folderId = 'root';

        foreach (array_filter(explode('/', trim($path, '/'))) as $segment) {
            $target = trim($parent.'/'.$segment, '/');
            $get = Http::withToken($token)->get('https://graph.microsoft.com/v1.0/users/'.$config->calendar_user_email.'/drive/root:/'.$target);

            if ($get->successful()) {
                $folderId = (string) data_get($get->json(), 'id');
                $parent = $target;

                continue;
            }

            $create = Http::withToken($token)
                ->post('https://graph.microsoft.com/v1.0/users/'.$config->calendar_user_email.'/drive/items/'.$folderId.'/children', [
                    'name' => $segment,
                    'folder' => new \stdClass,
                    '@microsoft.graph.conflictBehavior' => 'replace',
                ])
                ->throw()
                ->json();
            $folderId = (string) data_get($create, 'id');
            $parent = $target;
        }

        return $folderId;
    }

    private function config(): GraphApiConfig
    {
        return GraphApiConfig::query()->where('is_active', true)->firstOrFail();
    }

    private function token(GraphApiConfig $config): string
    {
        return (string) data_get(Http::asForm()->post('https://login.microsoftonline.com/'.$config->tenant_id.'/oauth2/v2.0/token', [
            'client_id' => $config->client_id,
            'client_secret' => $config->client_secret,
            'scope' => 'https://graph.microsoft.com/.default',
            'grant_type' => 'client_credentials',
        ])->throw()->json(), 'access_token');
    }

    private function fileName(array $metadata): string
    {
        $name = Str::of($metadata['candidate_name'].'_'.$metadata['position_name'].'_'.$metadata['signed_date'])
            ->replaceMatches('/[^A-Za-z0-9_\-]+/', '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_');

        return $name.'.pdf';
    }
}
