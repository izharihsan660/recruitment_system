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
        Schema::table('docuseal_configs', function (Blueprint $table) {
            $table->string('offering_template_id')->nullable()->after('webhook_secret');
            $table->string('pkwt_template_id')->nullable()->after('offering_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('docuseal_configs', function (Blueprint $table) {
            $table->dropColumn(['offering_template_id', 'pkwt_template_id']);
        });
    }
};
