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
        // First modify the column to allow any string temporarily
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('registration_status')->change();
        });
        
        // Update the values
        DB::table('attendees')
            ->where('registration_status', 'registered')
            ->update(['registration_status' => 'unchecked_in']);

        // Then set the new ENUM type
        Schema::table('attendees', function (Blueprint $table) {
            $table->enum('registration_status', ['unchecked_in', 'checked_in', 'cancelled'])
                  ->default('unchecked_in')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First modify the column to allow any string temporarily
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('registration_status')->change();
        });

        // Update the values
        DB::table('attendees')
            ->where('registration_status', 'unchecked_in')
            ->update(['registration_status' => 'registered']);
        
        // Then set the original ENUM type
        Schema::table('attendees', function (Blueprint $table) {
            $table->enum('registration_status', ['registered', 'checked_in', 'cancelled'])
                  ->default('registered')
                  ->change();
        });
    }
}; 