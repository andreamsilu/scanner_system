<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSmsLogsTableStructure extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            // Rename columns
            $table->renameColumn('to', 'recipient');
            
            // Add new columns
            $table->string('sender_id')->after('recipient');
            $table->string('reference')->nullable()->after('message_id');
            $table->string('provider_status')->nullable()->after('status');
            $table->string('provider_message')->nullable()->after('provider_status');
            $table->timestamp('delivered_at')->nullable()->after('provider_message');
            $table->timestamp('last_status_check')->nullable()->after('delivered_at');
            $table->integer('status_check_count')->default(0)->after('last_status_check');
            
            // Drop old columns
            $table->dropColumn(['type', 'provider']);
            
            // Drop old indexes (using the actual index names from the database)
            $table->dropIndex(['sms_logs_type_index']);
            $table->dropIndex(['sms_logs_provider_index']);
            $table->dropIndex(['sms_logs_to_index']);
            
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
            // Restore old columns
            $table->renameColumn('recipient', 'to');
            $table->string('type')->after('message');
            $table->string('provider')->nullable()->after('metadata');
            
            // Drop new columns
            $table->dropColumn([
                'sender_id',
                'reference',
                'provider_status',
                'provider_message',
                'delivered_at',
                'last_status_check',
                'status_check_count'
            ]);
            
            // Restore old indexes
            $table->index(['type'], 'sms_logs_type_index');
            $table->index(['provider'], 'sms_logs_provider_index');
            $table->index(['to'], 'sms_logs_to_index');
            
            // Drop new indexes
            $table->dropIndex(['sms_logs_reference_index']);
            $table->dropIndex(['sms_logs_status_created_at_index']);
            $table->dropIndex(['sms_logs_recipient_created_at_index']);
            $table->dropIndex(['sms_logs_sender_id_created_at_index']);
        });
    }
}
