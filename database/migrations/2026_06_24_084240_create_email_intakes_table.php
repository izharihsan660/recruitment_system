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
        Schema::create('email_intakes', function (Blueprint $table) {
            $table->id();
            $table->string('graph_message_id')->unique();
            $table->string('sender_name');
            $table->string('sender_email')->index();
            $table->string('subject');
            $table->text('body');
            $table->timestamp('received_at')->index();
            $table->string('attachment_path')->nullable();
            $table->foreignId('suggested_job_id')->nullable()->constrained('job_postings')->nullOnDelete();
            $table->enum('status', ['need_review', 'assigned_to_job', 'moved_to_talent_pool', 'rejected', 'ignored', 'spam'])->default('need_review')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_duplicate')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_intakes');
    }
};
