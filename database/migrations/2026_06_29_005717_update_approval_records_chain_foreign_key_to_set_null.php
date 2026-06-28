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
        Schema::table('approval_records', function (Blueprint $table) {
            $table->dropForeign(['approval_chain_id']);
            $table->foreignId('approval_chain_id')->nullable()->change();
            $table->foreign('approval_chain_id')->references('id')->on('approval_chains')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approval_records', function (Blueprint $table) {
            $table->dropForeign(['approval_chain_id']);
            $table->foreignId('approval_chain_id')->nullable(false)->change();
            $table->foreign('approval_chain_id')->references('id')->on('approval_chains')->restrictOnDelete();
        });
    }
};
