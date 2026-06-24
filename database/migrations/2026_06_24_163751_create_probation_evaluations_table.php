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
        Schema::create('probation_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('probation_id')->constrained('probation_records')->cascadeOnDelete();
            $table->enum('milestone', ['day30', 'day60', 'day90', 'extended']);
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->text('performance_notes');
            $table->enum('recommendation', ['permanent', 'extended', 'terminated']);
            $table->timestamp('evaluated_at');
            $table->timestamps();
            $table->unique(['probation_id', 'milestone']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('probation_evaluations');
    }
};
