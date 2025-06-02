<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToSmsLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            // Add new indexes
            $table->index(['reference'], 'sms_logs_reference_index');
            $table->index(['status', 'created_at'], 'sms_logs_status_created_at_index');
            $table->index(['recipient', 'created_at'], 'sms_logs_recipient_created_at_index');
            $table->index(['sender_id', 'created_at'], 'sms_logs_sender_id_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            // Drop the indexes
            $table->dropIndex('sms_logs_reference_index');
            $table->dropIndex('sms_logs_status_created_at_index');
            $table->dropIndex('sms_logs_recipient_created_at_index');
            $table->dropIndex('sms_logs_sender_id_created_at_index');
        });
    }
}
