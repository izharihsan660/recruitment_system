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
        Schema::table('approval_records', function (Blueprint $table) {
            $table->index('recruitment_request_id', 'approval_records_recruitment_request_id_index');
            $table->dropUnique('approval_records_recruitment_request_id_level_unique');
            $table->dropColumn('level');
            $table->unique(['recruitment_request_id', 'approver_id']);
        });

        Schema::table('approval_chains', function (Blueprint $table) {
            $table->index('department_id', 'approval_chains_department_id_index');
            $table->dropUnique('approval_chains_department_id_level_unique');
            $table->dropColumn('level');
            $table->unique(['department_id', 'approver_user_id']);
        });

        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->dropColumn('current_approval_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_approval_level')->nullable();
        });

        Schema::table('approval_chains', function (Blueprint $table) {
            $table->dropUnique('approval_chains_department_id_approver_user_id_unique');
            $table->unsignedTinyInteger('level')->nullable();
            $table->unique(['department_id', 'level']);
        });

        Schema::table('approval_records', function (Blueprint $table) {
            $table->dropUnique('approval_records_recruitment_request_id_approver_id_unique');
            $table->unsignedTinyInteger('level')->nullable();
            $table->unique(['recruitment_request_id', 'level']);
        });
    }
};
