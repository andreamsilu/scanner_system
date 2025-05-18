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
        Schema::table('tickets', function (Blueprint $table) {
            // Drop the existing token column if it exists
            if (Schema::hasColumn('tickets', 'token')) {
                $table->dropColumn('token');
            }
            
            // Add token column with 7 characters to match attendee tokens
            $table->string('token', 7)->after('ticket_number');
        });

        // Copy tokens from attendees to tickets
        DB::table('tickets')
            ->join('attendees', 'tickets.attendee_id', '=', 'attendees.id')
            ->whereNull('tickets.token')
            ->update(['tickets.token' => DB::raw('attendees.token')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
}; 