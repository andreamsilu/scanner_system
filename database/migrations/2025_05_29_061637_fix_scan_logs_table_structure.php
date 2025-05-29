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
        // First, check if the column exists
        if (Schema::hasColumn('scan_logs', 'scan_key')) {
            // Drop any unique constraints if they exist
            if (Schema::hasIndex('scan_logs', 'unique_scan_log')) {
                Schema::table('scan_logs', function (Blueprint $table) {
                    $table->dropUnique('unique_scan_log');
                });
            }

            // First, allow NULL values
            DB::statement('ALTER TABLE scan_logs MODIFY scan_key VARCHAR(32) NULL');
            
            // Then update existing NULL values to a default value
            DB::statement("UPDATE scan_logs SET scan_key = CONCAT('SK_', UUID()) WHERE scan_key IS NULL");
            
            // Finally, add a default value for new records
            DB::statement("ALTER TABLE scan_logs MODIFY scan_key VARCHAR(32) NOT NULL DEFAULT (CONCAT('SK_', UUID()))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('scan_logs', 'scan_key')) {
            // Remove the default value
            DB::statement('ALTER TABLE scan_logs MODIFY scan_key VARCHAR(32) NOT NULL');
            
            // Re-add the unique constraint
            Schema::table('scan_logs', function (Blueprint $table) {
                $table->unique('scan_key', 'unique_scan_log');
            });
        }
    }
};
