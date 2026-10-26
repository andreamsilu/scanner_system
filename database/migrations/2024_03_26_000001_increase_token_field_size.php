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
        // Increase token field size in attendees table
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('token', 64)->change();
        });

        // Increase token field size in tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('token', 64)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert token field size in attendees table
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('token', 64)->change();
        });

        // Revert token field size in tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('token', 64)->change();
        });
    }
};
