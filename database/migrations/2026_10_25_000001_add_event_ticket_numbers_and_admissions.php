<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('next_ticket_number')->default(1);
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('legacy_ticket_number')->nullable();
            $table->unsignedTinyInteger('entries_used')->default(0);
            $table->string('token', 64)->nullable()->change();
            $table->dropUnique('tickets_ticket_number_unique');
        });
        Schema::table('attendees', fn (Blueprint $table) => $table->string('token', 64)->change());
        Schema::table('scan_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('entries_admitted')->default(1);
            $table->index(['ticket_id', 'status']);
            $table->dropForeign(['ticket_id']);
            $table->dropForeign(['scanner_id']);
            $table->foreign('ticket_id')->references('id')->on('tickets')->restrictOnDelete();
            $table->foreign('scanner_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::table('bulk_uploads', function (Blueprint $table) {
            $table->text('error_log')->nullable();
            $table->timestamp('completed_at')->nullable();
        });
        Schema::table('sms_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('sms_logs', 'type')) {
                $table->string('type')->default('notification');
            }
            if (! Schema::hasColumn('sms_logs', 'provider')) {
                $table->string('provider')->nullable();
            }
        });

        // Run upgrades in maintenance mode. Preserve old ticket numbers and all QR credentials.
        DB::transaction(function () {
            foreach (DB::table('events')->orderBy('id')->cursor() as $event) {
                $next = 1;
                foreach (DB::table('tickets')->where('event_id', $event->id)->orderBy('id')->cursor() as $ticket) {
                    DB::table('tickets')->where('id', $ticket->id)->update([
                        'legacy_ticket_number' => $ticket->ticket_number,
                        'ticket_number' => (string) $next++,
                        // Previously consumed tickets stay consumed, including double tickets.
                        'entries_used' => $ticket->status === 'used' ? ($ticket->type === 'double' ? 2 : 1) : 0,
                    ]);
                }
                DB::table('events')->where('id', $event->id)->update(['next_ticket_number' => $next]);
            }
        });
        Schema::table('tickets', fn (Blueprint $table) => $table->unique(['event_id', 'ticket_number']));
    }

    public function down(): void
    {
        // Renumbering is an intentional data migration. Do not silently discard admission history.
        throw new RuntimeException('Restore a verified pre-upgrade backup to reverse ticket numbering and admission tracking.');
    }
};
