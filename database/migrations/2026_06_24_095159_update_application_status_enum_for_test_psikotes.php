<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('applied','screening','test_psikotes','interview_hr','interview_user','background_check','offering','mcu_simper','hiring_decision','pkwt','hired','rejected','withdrawn') NOT NULL DEFAULT 'applied'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM('applied','screening','test','interview_hr','interview_user','background_check','offering','mcu_simper','hiring_decision','pkwt','hired','rejected','withdrawn') NOT NULL DEFAULT 'applied'");
        }
    }
};
