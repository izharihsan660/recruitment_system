<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('background_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('ktp_verified')->default(false);
            $table->boolean('ijazah_verified')->default(false);
            $table->boolean('certificate_verified')->default(false);
            $table->boolean('reference_verified')->nullable();
            $table->text('notes')->nullable();
            $table->enum('decision', ['clear', 'issue', 'failed']);
            $table->text('rejection_reason')->nullable();
            $table->foreignId('verified_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('background_checks');
    }
};
