<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('psycho_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->string('test_type');
            $table->timestamp('scheduled_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('decision', ['passed', 'failed'])->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('conducted_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psycho_tests');
    }
};
