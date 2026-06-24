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
        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_chain_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('level');
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('action', ['approved', 'rejected', 'need_revision', 'waiting'])->default('waiting');
            $table->text('comment')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['recruitment_request_id', 'level']);
            $table->index(['recruitment_request_id', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_records');
    }
};
