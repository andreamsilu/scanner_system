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
            // Add token column if it doesn't exist
            if (!Schema::hasColumn('attendees', 'token')) {
                $table->string('token', 6)->unique()->after('position');
            }
            
            // Add qr_code column if it doesn't exist
            if (!Schema::hasColumn('attendees', 'qr_code')) {
                $table->string('qr_code')->nullable()->after('token');
            }
        });

        // Generate tokens for existing attendees
        DB::table('attendees')
            ->whereNull('token')
            ->orderBy('id')
            ->each(function ($attendee) {
                do {
                    $token = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
                } while (DB::table('attendees')->where('token', $token)->exists());
                
                DB::table('attendees')
                    ->where('id', $attendee->id)
                    ->update(['token' => $token]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendees', function (Blueprint $table) {
            $table->dropColumn(['token', 'qr_code']);
        });
    }
}; 