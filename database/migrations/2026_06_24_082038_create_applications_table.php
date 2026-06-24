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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('source', ['portal', 'hr_input', 'email_intake', 'talent_pool'])->default('portal');
            $table->enum('status', ['applied', 'screening', 'test_psikotes', 'interview_hr', 'interview_user', 'background_check', 'offering', 'mcu_simper', 'hiring_decision', 'pkwt', 'hired', 'rejected', 'withdrawn'])->default('applied')->index();
            $table->text('rejection_reason')->nullable();
            $table->string('rejection_stage')->nullable();
            $table->boolean('consent')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['job_posting_id', 'candidate_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
