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
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('entity_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('position_name');
            $table->enum('employment_status', ['permanent', 'contract', 'intern']);
            $table->string('work_location');
            $table->text('job_description');
            $table->text('requirements');
            $table->boolean('mcu_required')->default(false);
            $table->boolean('simper_required')->default(false);
            $table->enum('status', ['draft', 'open', 'closed', 'cancelled'])->default('draft')->index();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['recruitment_request_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
