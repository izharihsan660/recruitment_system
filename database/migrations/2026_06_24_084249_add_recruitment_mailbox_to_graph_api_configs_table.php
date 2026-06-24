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
            $table->string('recruitment_mailbox')->nullable()->after('calendar_user_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('graph_api_configs', function (Blueprint $table) {
            $table->dropColumn('recruitment_mailbox');
        });
    }
};
