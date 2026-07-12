<?php

namespace Database\Seeders;

use App\Models\GraphMailSenderSetting;
use Illuminate\Database\Seeder;

class GraphMailSenderSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuration = config('services.mail_graph_sender');

        if (blank($configuration['tenant_id'] ?? null)
            || blank($configuration['client_id'] ?? null)
            || blank($configuration['client_secret'] ?? null)
            || blank($configuration['mailbox'] ?? null)) {
            return;
        }

        GraphMailSenderSetting::query()->updateOrCreate(
            ['sender_mailbox' => $configuration['mailbox']],
            [
                'tenant_id' => $configuration['tenant_id'],
                'client_id' => $configuration['client_id'],
                'client_secret' => $configuration['client_secret'],
                'from_name' => $configuration['from_name'] ?? config('mail.from.name'),
                'is_active' => true,
            ],
        );
    }
}
