<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('teams_meeting_link')->nullable();
            $table->string('teams_meeting_id')->nullable();
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('score_communication')->nullable();
            $table->unsignedTinyInteger('score_personality')->nullable();
            $table->unsignedTinyInteger('score_motivation')->nullable();
            $table->unsignedTinyInteger('score_attitude')->nullable();
            $table->unsignedTinyInteger('score_culture_fit')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->unsignedBigInteger('salary_expectation')->nullable();
            $table->enum('recommendation', ['recommended', 'considered', 'not_recommended'])->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'passed', 'failed', 'pending'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_interviews');
    }
};
