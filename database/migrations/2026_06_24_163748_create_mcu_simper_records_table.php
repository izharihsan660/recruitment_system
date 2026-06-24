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
        Schema::create('mcu_simper_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('mcu_required')->default(false);
            $table->timestamp('mcu_scheduled_at')->nullable();
            $table->string('mcu_location')->nullable();
            $table->string('mcu_result_path')->nullable();
            $table->enum('mcu_status', ['not_required', 'pending', 'passed', 'failed'])->nullable()->index();
            $table->text('mcu_notes')->nullable();
            $table->boolean('simper_required')->default(false);
            $table->timestamp('simper_scheduled_at')->nullable();
            $table->string('simper_location')->nullable();
            $table->string('simper_result_path')->nullable();
            $table->enum('simper_status', ['not_required', 'pending', 'passed', 'failed'])->nullable()->index();
            $table->text('simper_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcu_simper_records');
    }
};
