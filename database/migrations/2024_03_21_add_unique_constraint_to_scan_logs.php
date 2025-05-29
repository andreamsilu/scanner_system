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
        // First, add a new column to store the scan timestamp in a format suitable for unique constraint
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->string('scan_key', 32)->after('created_at')->nullable();
        });

        // Update existing records with a unique scan key
        DB::statement('UPDATE scan_logs SET scan_key = CONCAT(ticket_id, "_", scanner_id, "_", DATE_FORMAT(created_at, "%Y%m%d%H%i%s"))');

        // Make the scan_key column not nullable
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->string('scan_key', 32)->nullable(false)->change();
        });

        // Add unique constraint on scan_key
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->unique('scan_key', 'unique_scan_log');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->dropUnique('unique_scan_log');
            $table->dropColumn('scan_key');
        });
    }
}; 