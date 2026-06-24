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
        Schema::create('talent_pools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'passive', 'hot_prospect', 'on_hold', 'do_not_contact', 'hired_elsewhere', 'archived'])->default('active')->index();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('source_application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->foreignId('added_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('added_at')->index();
            $table->timestamps();

            $table->unique('candidate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talent_pools');
    }
};
