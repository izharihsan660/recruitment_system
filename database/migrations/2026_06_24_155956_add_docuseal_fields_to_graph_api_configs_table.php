<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('graph_api_configs', function (Blueprint $table) {
            $table->string('docuseal_api_key')->nullable()->after('recruitment_mailbox');
            $table->string('docuseal_api_url')->nullable()->default('https://api.docuseal.com')->after('docuseal_api_key');
            $table->string('docuseal_webhook_secret')->nullable()->after('docuseal_api_url');
        });
    }

    public function down(): void
    {
        Schema::table('graph_api_configs', function (Blueprint $table) {
            $table->dropColumn(['docuseal_api_key', 'docuseal_api_url', 'docuseal_webhook_secret']);
        });
    }
};
