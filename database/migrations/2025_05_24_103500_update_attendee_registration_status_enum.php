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
        // Get the current ENUM values
        $columnInfo = DB::select("SHOW COLUMNS FROM attendees WHERE Field = 'registration_status'")[0];
        $currentEnum = str_replace(['enum(', ')', "'"], '', $columnInfo->Type);
        $currentValues = explode(',', $currentEnum);

        // If 'registered' is still a valid value, update it to 'unchecked_in'
        if (in_array('registered', $currentValues)) {
            DB::statement("UPDATE attendees SET registration_status = 'unchecked_in' WHERE registration_status = 'registered'");
        }

        // Modify the ENUM type regardless of current values
        DB::statement("ALTER TABLE attendees MODIFY COLUMN registration_status ENUM('unchecked_in', 'checked_in', 'cancelled') DEFAULT 'unchecked_in'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the current ENUM values
        $columnInfo = DB::select("SHOW COLUMNS FROM attendees WHERE Field = 'registration_status'")[0];
        $currentEnum = str_replace(['enum(', ')', "'"], '', $columnInfo->Type);
        $currentValues = explode(',', $currentEnum);

        // If 'unchecked_in' is still a valid value, update it to 'registered'
        if (in_array('unchecked_in', $currentValues)) {
            DB::statement("UPDATE attendees SET registration_status = 'registered' WHERE registration_status = 'unchecked_in'");
        }

        // Revert the ENUM type
        DB::statement("ALTER TABLE attendees MODIFY COLUMN registration_status ENUM('registered', 'checked_in', 'cancelled') DEFAULT 'registered'");
    }
}; 