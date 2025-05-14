<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendees', function (Blueprint $table) {
            // Add event_id if not exists
            if (!Schema::hasColumn('attendees', 'event_id')) {
                $table->foreignId('event_id')->after('id')->constrained()->onDelete('cascade');
            }
            
            // Add ticket_id if not exists
            if (!Schema::hasColumn('attendees', 'ticket_id')) {
                $table->foreignId('ticket_id')->after('event_id')->nullable()->constrained()->onDelete('set null');
            }
            
            // Add registration status if not exists
            if (!Schema::hasColumn('attendees', 'registration_status')) {
                $table->enum('registration_status', ['registered', 'checked_in', 'cancelled'])->default('registered')->after('phone');
            }
            
            // Add registration date if not exists
            if (!Schema::hasColumn('attendees', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('registration_status');
            }
        });
    }

    public function down()
    {
        Schema::table('attendees', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropForeign(['ticket_id']);
            $table->dropColumn(['event_id', 'ticket_id', 'registration_status', 'registered_at']);
        });
    }
}; 