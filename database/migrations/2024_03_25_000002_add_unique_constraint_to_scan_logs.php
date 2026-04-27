<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only attempt to modify the column if the table and column both exist.
        if (!Schema::hasTable('scan_logs') || !Schema::hasColumn('scan_logs', 'scan_key')) {
            return;
        }

        // Make scan_key nullable and remove unique constraint if present
        Schema::table('scan_logs', function (Blueprint $table) {
            if (Schema::hasIndex('scan_logs', 'unique_scan_log')) {
                $table->dropUnique('unique_scan_log');
            }

            $table->string('scan_key', 32)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('scan_logs') || !Schema::hasColumn('scan_logs', 'scan_key')) {
            return;
        }

        Schema::table('scan_logs', function (Blueprint $table) {
            $table->string('scan_key', 32)->nullable(false)->change();
            $table->unique('scan_key', 'unique_scan_log');
        });
    }
}; 