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
        Schema::create('recruitment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->string('requester_position');
            $table->date('requested_at');
            $table->string('position_name');
            $table->unsignedInteger('headcount');
            $table->enum('employment_status', ['permanent', 'contract', 'intern']);
            $table->string('job_title');
            $table->string('work_location');
            $table->date('required_at');
            $table->enum('reason_type', ['replacement', 'addition', 'new_project', 'other']);
            $table->text('reason_notes');
            $table->string('min_education');
            $table->string('min_experience');
            $table->text('required_skills');
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->enum('gender', ['male', 'female', 'any'])->nullable();
            $table->text('job_description');
            $table->json('facilities');
            $table->enum('status', ['draft', 'requested', 'in_approval', 'approved', 'rejected', 'need_revision', 'closed'])->default('draft')->index();
            $table->unsignedTinyInteger('current_approval_level')->nullable();
            $table->timestamps();

            $table->index(['department_id', 'status']);
            $table->index(['requester_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recruitment_requests');
    }
};
