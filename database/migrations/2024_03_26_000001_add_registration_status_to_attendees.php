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
        Schema::table('attendees', function (Blueprint $table) {
            $table->enum('registration_status', ['registered', 'checked_in', 'cancelled'])
                  ->default('registered')
                  ->after('position');
            $table->timestamp('checked_in_at')->nullable()->after('registration_status');
        });

        // Update existing attendees based on their ticket status
        DB::table('attendees')
            ->join('tickets', 'attendees.id', '=', 'tickets.attendee_id')
            ->where('tickets.status', 'used')
            ->update([
                'attendees.registration_status' => 'checked_in',
                'attendees.checked_in_at' => DB::raw('tickets.used_at')
            ]);

        DB::table('attendees')
            ->join('tickets', 'attendees.id', '=', 'tickets.attendee_id')
            ->where('tickets.status', 'cancelled')
            ->update([
                'attendees.registration_status' => 'cancelled'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendees', function (Blueprint $table) {
            $table->dropColumn(['registration_status', 'checked_in_at']);
        });
    }
}; 