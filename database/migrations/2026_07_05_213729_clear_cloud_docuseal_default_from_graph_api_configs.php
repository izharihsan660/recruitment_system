<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('graph_api_configs', function (Blueprint $table) {
            $table->string('docuseal_api_url')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('graph_api_configs', function (Blueprint $table) {
            $table->string('docuseal_api_url')->nullable()->default('https://api.docuseal.com')->change();
        });
    }
};
