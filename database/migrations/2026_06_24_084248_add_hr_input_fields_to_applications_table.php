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
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('source_id')->nullable()->after('source')->constrained('candidate_sources')->nullOnDelete();
            $table->string('referral_name')->nullable()->after('source_id');
            $table->string('referral_department')->nullable()->after('referral_name');
            $table->string('referral_phone')->nullable()->after('referral_department');
            $table->string('referral_relation')->nullable()->after('referral_phone');
            $table->text('referral_notes')->nullable()->after('referral_relation');
            $table->foreignId('input_by')->nullable()->after('referral_notes')->constrained('users')->nullOnDelete();
            $table->foreignId('consent_by')->nullable()->after('consent_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_id');
            $table->dropConstrainedForeignId('input_by');
            $table->dropConstrainedForeignId('consent_by');
            $table->dropColumn(['referral_name', 'referral_department', 'referral_phone', 'referral_relation', 'referral_notes']);
        });
    }
};
