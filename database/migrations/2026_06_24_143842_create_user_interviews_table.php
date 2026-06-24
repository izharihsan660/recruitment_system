<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('location');
            $table->foreignId('interviewer_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('score_technical')->nullable();
            $table->unsignedTinyInteger('score_experience')->nullable();
            $table->unsignedTinyInteger('score_problem_solving')->nullable();
            $table->unsignedTinyInteger('score_team_fit')->nullable();
            $table->enum('recommendation', ['accepted', 'considered', 'rejected'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'recommended', 'not_recommended', 'consider'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interviews');
    }
};
