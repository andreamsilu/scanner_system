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
        // Get the current column definition
        $column = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableDetails('attendees')
            ->getColumn('registration_status');

        $currentValues = $column->getType()->getValues();

        // If 'registered' is still a valid value, update it to 'unchecked_in'
        if (in_array('registered', $currentValues)) {
            DB::table('attendees')
                ->where('registration_status', 'registered')
                ->update(['registration_status' => 'unchecked_in']);
        }

        // Modify the ENUM type using Schema Builder
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
        // Get the current column definition
        $column = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableDetails('attendees')
            ->getColumn('registration_status');

        $currentValues = $column->getType()->getValues();

        // If 'unchecked_in' is still a valid value, update it to 'registered'
        if (in_array('unchecked_in', $currentValues)) {
            DB::table('attendees')
                ->where('registration_status', 'unchecked_in')
                ->update(['registration_status' => 'registered']);
        }

        // Revert the ENUM type using Schema Builder
        Schema::table('attendees', function (Blueprint $table) {
            $table->enum('registration_status', ['registered', 'checked_in', 'cancelled'])
                  ->default('registered')
                  ->change();
        });
    }
}; 