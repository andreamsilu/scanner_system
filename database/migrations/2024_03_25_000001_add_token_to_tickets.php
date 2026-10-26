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
            // Preserve credentials if this migration is applied to an existing table.
            if (!Schema::hasColumn('tickets', 'token')) {
                $table->string('token', 64)->nullable()->after('ticket_number');
            }
        });

        // Copy tokens from attendees to tickets
        DB::table('tickets')->whereNull('token')->orderBy('id')->each(function ($ticket) {
            DB::table('tickets')->where('id', $ticket->id)->update([
                'token' => DB::table('attendees')->where('id', $ticket->attendee_id)->value('token'),
            ]);
        });
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
