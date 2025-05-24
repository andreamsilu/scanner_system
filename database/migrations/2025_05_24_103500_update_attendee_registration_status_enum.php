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
        // First, update any existing 'registered' status to 'unchecked_in'
        DB::statement("UPDATE attendees SET registration_status = 'unchecked_in' WHERE registration_status = 'registered'");
        
        // Then modify the ENUM type
        DB::statement("ALTER TABLE attendees MODIFY COLUMN registration_status ENUM('unchecked_in', 'checked_in', 'cancelled') DEFAULT 'unchecked_in'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any 'unchecked_in' status back to 'registered'
        DB::statement("UPDATE attendees SET registration_status = 'registered' WHERE registration_status = 'unchecked_in'");
        
        // Then revert the ENUM type
        DB::statement("ALTER TABLE attendees MODIFY COLUMN registration_status ENUM('registered', 'checked_in', 'cancelled') DEFAULT 'registered'");
    }
}; 