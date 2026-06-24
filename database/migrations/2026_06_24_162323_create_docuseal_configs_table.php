<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docuseal_configs', function (Blueprint $table) {
            $table->id();
            $table->string('api_url');
            $table->string('api_key');
            $table->string('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('docuseal_configs')->insert([
            'api_url' => 'https://api.docuseal.com',
            'api_key' => Crypt::encryptString(''),
            'webhook_secret' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('graph_api_configs', function (Blueprint $table) {
            $table->dropColumn(['docuseal_api_key', 'docuseal_api_url', 'docuseal_webhook_secret']);
        });
    }

    public function down(): void
    {
        Schema::table('graph_api_configs', function (Blueprint $table) {
            $table->string('docuseal_api_key')->nullable()->after('recruitment_mailbox');
            $table->string('docuseal_api_url')->nullable()->default('https://api.docuseal.com')->after('docuseal_api_key');
            $table->string('docuseal_webhook_secret')->nullable()->after('docuseal_api_url');
        });

        Schema::dropIfExists('docuseal_configs');
    }
};
