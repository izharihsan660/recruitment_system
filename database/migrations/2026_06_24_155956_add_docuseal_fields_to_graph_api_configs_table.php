<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('graph_api_configs', 'docuseal_api_key')) {
            Schema::table('graph_api_configs', function (Blueprint $table) {
                $table->string('docuseal_api_key')->nullable()->after('recruitment_mailbox');
            });
        }

        if (! Schema::hasColumn('graph_api_configs', 'docuseal_api_url')) {
            Schema::table('graph_api_configs', function (Blueprint $table) {
                $table->string('docuseal_api_url')->nullable()->default('https://api.docuseal.com')->after('docuseal_api_key');
            });
        }

        if (! Schema::hasColumn('graph_api_configs', 'docuseal_webhook_secret')) {
            Schema::table('graph_api_configs', function (Blueprint $table) {
                $table->string('docuseal_webhook_secret')->nullable()->after('docuseal_api_url');
            });
        }
    }

    public function down(): void
    {
        $graphDocuSealColumns = array_values(array_filter([
            'docuseal_api_key',
            'docuseal_api_url',
            'docuseal_webhook_secret',
        ], fn (string $column): bool => Schema::hasColumn('graph_api_configs', $column)));

        if ($graphDocuSealColumns !== []) {
            Schema::table('graph_api_configs', function (Blueprint $table) use ($graphDocuSealColumns) {
                $table->dropColumn($graphDocuSealColumns);
            });
        }
    }
};
