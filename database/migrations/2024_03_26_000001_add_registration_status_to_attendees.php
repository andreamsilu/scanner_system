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
        DB::table('tickets')->whereIn('status', ['used', 'cancelled'])->orderBy('id')->each(function ($ticket) {
            DB::table('attendees')->where('id', $ticket->attendee_id)->update([
                'registration_status' => $ticket->status === 'used' ? 'checked_in' : 'cancelled',
                'checked_in_at' => $ticket->status === 'used' ? $ticket->used_at : null,
            ]);
        });
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
