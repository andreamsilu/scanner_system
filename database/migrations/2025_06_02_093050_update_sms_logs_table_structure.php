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
        // Only modify the table if it exists
        if (!Schema::hasTable('sms_logs')) {
            return;
        }

        Schema::table('sms_logs', function (Blueprint $table) {
            // Rename columns if the old column exists
            if (Schema::hasColumn('sms_logs', 'to')) {
                $table->renameColumn('to', 'recipient');
            }

            // Add new columns if they don't already exist
            if (!Schema::hasColumn('sms_logs', 'sender_id')) {
                $table->string('sender_id')->after('recipient');
            }
            if (!Schema::hasColumn('sms_logs', 'reference')) {
                $table->string('reference')->nullable()->after('message_id');
            }
            if (!Schema::hasColumn('sms_logs', 'provider_status')) {
                $table->string('provider_status')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sms_logs', 'provider_message')) {
                $table->string('provider_message')->nullable()->after('provider_status');
            }
            if (!Schema::hasColumn('sms_logs', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('provider_message');
            }
            if (!Schema::hasColumn('sms_logs', 'last_status_check')) {
                $table->timestamp('last_status_check')->nullable()->after('delivered_at');
            }
            if (!Schema::hasColumn('sms_logs', 'status_check_count')) {
                $table->integer('status_check_count')->default(0)->after('last_status_check');
            }

            // Drop old columns if they exist
            $columnsToDrop = [];
            foreach (['type', 'provider'] as $col) {
                if (Schema::hasColumn('sms_logs', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            // Note: index creation is safe here; dropping is handled via raw SQL below.
            try {
                $table->index(['reference'], 'sms_logs_reference_index');
            } catch (\Throwable $e) {}
            try {
                $table->index(['status', 'created_at'], 'sms_logs_status_created_at_index');
            } catch (\Throwable $e) {}
            try {
                $table->index(['recipient', 'created_at'], 'sms_logs_recipient_created_at_index');
            } catch (\Throwable $e) {}
            try {
                $table->index(['sender_id', 'created_at'], 'sms_logs_sender_id_created_at_index');
            } catch (\Throwable $e) {}
        });

        // Drop old indexes (best-effort, using raw SQL so we can catch DB errors)
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_type_index`');
        } catch (\Throwable $e) {}
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_provider_index`');
        } catch (\Throwable $e) {}
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_to_index`');
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('sms_logs')) {
            return;
        }

        Schema::table('sms_logs', function (Blueprint $table) {
            // Restore old columns and names where appropriate
            if (Schema::hasColumn('sms_logs', 'recipient') && !Schema::hasColumn('sms_logs', 'to')) {
                $table->renameColumn('recipient', 'to');
            }

            if (!Schema::hasColumn('sms_logs', 'type')) {
                $table->string('type')->after('message');
            }
            if (!Schema::hasColumn('sms_logs', 'provider')) {
                $table->string('provider')->nullable()->after('metadata');
            }

            // Drop new columns if they exist
            $newCols = [
                'sender_id',
                'reference',
                'provider_status',
                'provider_message',
                'delivered_at',
                'last_status_check',
                'status_check_count',
            ];
            $colsToDrop = [];
            foreach ($newCols as $col) {
                if (Schema::hasColumn('sms_logs', $col)) {
                    $colsToDrop[] = $col;
                }
            }
            if (!empty($colsToDrop)) {
                $table->dropColumn($colsToDrop);
            }

            // Restore old indexes (ignore failures)
            try {
                $table->index(['type'], 'sms_logs_type_index');
            } catch (\Throwable $e) {}
            try {
                $table->index(['provider'], 'sms_logs_provider_index');
            } catch (\Throwable $e) {}
            try {
                $table->index(['to'], 'sms_logs_to_index');
            } catch (\Throwable $e) {}
        });

        // Drop new indexes (ignore if they don't exist), using raw SQL
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_reference_index`');
        } catch (\Throwable $e) {}
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_status_created_at_index`');
        } catch (\Throwable $e) {}
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_recipient_created_at_index`');
        } catch (\Throwable $e) {}
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_sender_id_created_at_index`');
        } catch (\Throwable $e) {}
    }
}
