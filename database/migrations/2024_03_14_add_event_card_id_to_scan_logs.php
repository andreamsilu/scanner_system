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
        if (!Schema::hasTable('scan_logs')) {
            Schema::create('scan_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scanner_id')->constrained()->onDelete('cascade');
                $table->foreignId('attendee_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('event_card_id')->nullable()->constrained()->onDelete('set null');
                $table->timestamp('scanned_at');
                $table->timestamps();
            });
        } else {
            Schema::table('scan_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('scan_logs', 'event_card_id')) {
                    $table->foreignId('event_card_id')->nullable()->constrained()->onDelete('set null')->after('attendee_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->dropForeign(['event_card_id']);
            $table->dropColumn('event_card_id');
        });
    }
}; 