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
        // Make scan_key nullable and remove unique constraint
        Schema::table('scan_logs', function (Blueprint $table) {
            // Drop the unique constraint if it exists
            if (Schema::hasIndex('scan_logs', 'unique_scan_log')) {
                $table->dropUnique('unique_scan_log');
            }
            
            // Modify scan_key to be nullable
            $table->string('scan_key', 32)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            // Make scan_key not nullable again
            $table->string('scan_key', 32)->nullable(false)->change();
            
            // Re-add the unique constraint
            $table->unique('scan_key', 'unique_scan_log');
        });
    }
}; 