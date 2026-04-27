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
        // Only drop the column if the table and column both exist
        if (!Schema::hasTable('scan_logs') || !Schema::hasColumn('scan_logs', 'scan_key')) {
            return;
        }

        Schema::table('scan_logs', function (Blueprint $table) {
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
                $table->string('scan_key')->nullable()->after('id');
            });
        }
    }
};
