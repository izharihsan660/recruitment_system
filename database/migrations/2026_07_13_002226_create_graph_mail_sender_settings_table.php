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
        Schema::create('graph_mail_sender_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('client_id');
            $table->text('client_secret');
            $table->string('sender_mailbox');
            $table->string('from_name');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('graph_mail_sender_settings');
    }
};
