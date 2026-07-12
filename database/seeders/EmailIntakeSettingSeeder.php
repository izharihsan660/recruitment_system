<?php

namespace Database\Seeders;

use App\Models\EmailIntakeSetting;
use Illuminate\Database\Seeder;

class EmailIntakeSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuration = config('services.mail_graph_intake');

        if (blank($configuration['tenant_id'] ?? null)
            || blank($configuration['client_id'] ?? null)
            || blank($configuration['client_secret'] ?? null)
            || blank($configuration['mailbox'] ?? null)) {
            return;
        }

        EmailIntakeSetting::query()->updateOrCreate(
            ['mailbox_address' => $configuration['mailbox']],
            [
                'tenant_id' => $configuration['tenant_id'],
                'client_id' => $configuration['client_id'],
                'client_secret' => $configuration['client_secret'],
                'is_active' => true,
                'sync_interval_minutes' => 10,
            ],
        );
    }
}
