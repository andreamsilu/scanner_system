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
        // Only attempt to modify the table if it exists
        if (!Schema::hasTable('scan_logs')) {
            return;
        }

        // Only proceed if the scan_key column actually exists
        if (!Schema::hasColumn('scan_logs', 'scan_key')) {
            return;
        }

        Schema::table('scan_logs', function (Blueprint $table) {
            // Drop any unique constraints first if they exist
            if (Schema::hasIndex('scan_logs', 'unique_scan_log')) {
                $table->dropUnique('unique_scan_log');
            }

            // Remove the scan_key column
            $table->dropColumn('scan_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only add the column back if the table exists and the column is missing
        if (Schema::hasTable('scan_logs') && !Schema::hasColumn('scan_logs', 'scan_key')) {
            Schema::table('scan_logs', function (Blueprint $table) {
                // Add the scan_key column back
                $table->string('scan_key', 32)->after('id');
            });
        }
    }
};
