<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->updateSqliteEmployeeTableDefinition(
                from: ["'permanent', 'contract', 'intern'", "'pending_activation', 'active', 'cancelled'"],
                to: ["'permanent', 'contract', 'intern', 'internship'", "'pending_activation', 'active', 'inactive', 'cancelled'"],
            );

            return;
        }

        DB::statement("ALTER TABLE employees MODIFY contract_type ENUM('permanent', 'contract', 'intern', 'internship') NOT NULL");
        DB::statement("ALTER TABLE employees MODIFY status ENUM('pending_activation', 'active', 'inactive', 'cancelled') NOT NULL DEFAULT 'pending_activation'");
    }

    public function down(): void
    {
        DB::table('employees')->where('contract_type', 'internship')->update(['contract_type' => 'intern']);
        DB::table('employees')->where('status', 'inactive')->update(['status' => 'cancelled']);

        if (DB::getDriverName() === 'sqlite') {
            $this->updateSqliteEmployeeTableDefinition(
                from: ["'permanent', 'contract', 'intern', 'internship'", "'pending_activation', 'active', 'inactive', 'cancelled'"],
                to: ["'permanent', 'contract', 'intern'", "'pending_activation', 'active', 'cancelled'"],
            );

            return;
        }

        DB::statement("ALTER TABLE employees MODIFY contract_type ENUM('permanent', 'contract', 'intern') NOT NULL");
        DB::statement("ALTER TABLE employees MODIFY status ENUM('pending_activation', 'active', 'cancelled') NOT NULL DEFAULT 'pending_activation'");
    }

    /**
     * @param  array<int, string>  $from
     * @param  array<int, string>  $to
     */
    private function updateSqliteEmployeeTableDefinition(array $from, array $to): void
    {
        $definition = DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'employees')
            ->value('sql');

        if (! is_string($definition)) {
            return;
        }

        DB::statement('PRAGMA writable_schema = 1');
        DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'employees')
            ->update(['sql' => str_replace($from, $to, $definition)]);
        DB::statement('PRAGMA writable_schema = 0');
    }
};
