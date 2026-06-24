<?php

namespace App\Services;

use App\Models\Application;
use App\Models\GraphApiConfig;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class TeamsMeetingService
{
    /**
     * @return array{join_url: string|null, meeting_id: string|null}
     */
    public function create(Application $application, Carbon $scheduledAt): array
    {
        $config = GraphApiConfig::query()->where('is_active', true)->first();

        if (! $config) {
            return ['join_url' => null, 'meeting_id' => null];
        }

        $token = Http::asForm()
            ->post("https://login.microsoftonline.com/{$config->tenant_id}/oauth2/v2.0/token", [
                'client_id' => $config->client_id,
                'client_secret' => $config->client_secret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ])
            ->throw()
            ->json('access_token');

        $response = Http::withToken($token)
            ->post('https://graph.microsoft.com/v1.0/users/'.$config->calendar_user_email.'/onlineMeetings', [
                'startDateTime' => $scheduledAt->toIso8601String(),
                'endDateTime' => $scheduledAt->copy()->addHour()->toIso8601String(),
                'subject' => 'Interview HR - '.$application->candidate?->name,
            ])
            ->throw()
            ->json();

        return [
            'join_url' => Arr::get($response, 'joinWebUrl'),
            'meeting_id' => Arr::get($response, 'id'),
        ];
    }
}
