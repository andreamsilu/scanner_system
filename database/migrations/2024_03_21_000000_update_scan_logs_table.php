<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            // Drop the old foreign key and column
            $table->dropForeign(['event_card_id']);
            $table->dropColumn('event_card_id');

            // Add the new ticket_id column
            $table->foreignId('ticket_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            // Drop the new foreign key and column
            $table->dropForeign(['ticket_id']);
            $table->dropColumn('ticket_id');

            // Add back the old event_card_id column
            $table->foreignId('event_card_id')->nullable()->constrained()->onDelete('set null');
        });
    }
}; 