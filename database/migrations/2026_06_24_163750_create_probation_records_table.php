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
        Schema::create('probation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('day30_due');
            $table->date('day60_due');
            $table->date('day90_due');
            $table->date('extended_until')->nullable();
            $table->unsignedTinyInteger('extension_count')->default(0);
            $table->enum('status', ['not_started', 'in_progress', 'day30_review', 'day60_review', 'day90_review', 'permanent', 'extended', 'terminated'])->default('in_progress')->index();
            $table->enum('final_outcome', ['permanent', 'terminated'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('probation_records');
    }
};
