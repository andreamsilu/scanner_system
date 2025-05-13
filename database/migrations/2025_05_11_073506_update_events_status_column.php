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
        Schema::table('events', function (Blueprint $table) {
            // Drop the existing status column
            $table->dropColumn('status');
        });

        Schema::table('events', function (Blueprint $table) {
            // Add the new status column
            $table->string('status')->default('draft')->after('venue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Drop the string status column
            $table->dropColumn('status');
        });

        Schema::table('events', function (Blueprint $table) {
            // Recreate the original enum status column
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft')->after('venue');
        });
    }
};
