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
        Schema::create('email_intake_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('client_id');
            $table->text('client_secret');
            $table->string('mailbox_address');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->unsignedSmallInteger('sync_interval_minutes')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_intake_settings');
    }
};
