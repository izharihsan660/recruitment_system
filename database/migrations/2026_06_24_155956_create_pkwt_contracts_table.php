<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pkwt_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('entity_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_signer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('candidate_id')->constrained()->restrictOnDelete();
            $table->string('position_name');
            $table->string('department');
            $table->string('work_location');
            $table->enum('contract_type', ['permanent', 'contract', 'intern']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('contract_duration')->nullable();
            $table->bigInteger('salary_gross')->nullable();
            $table->bigInteger('salary_nett')->nullable();
            $table->json('allowances')->nullable();
            $table->string('docuseal_submission_id')->nullable()->index();
            $table->text('candidate_signing_url')->nullable();
            $table->text('company_signing_url')->nullable();
            $table->enum('status', ['draft', 'sent', 'partially_signed', 'signed', 'rejected', 'expired'])->default('draft')->index();
            $table->timestamp('signed_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('sharepoint_url')->nullable();
            $table->enum('archive_status', ['pending', 'archived', 'failed'])->default('pending');
            $table->timestamp('archive_attempted_at')->nullable();
            $table->text('archive_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pkwt_contracts');
    }
};
