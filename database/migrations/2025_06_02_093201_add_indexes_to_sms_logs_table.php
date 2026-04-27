<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIndexesToSmsLogsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sms_logs')) {
            return;
        }

        // Use raw SQL with try/catch so duplicate indexes never break migrations.
        try {
            DB::statement('ALTER TABLE `sms_logs` ADD INDEX `sms_logs_reference_index`(`reference`)');
        } catch (\Throwable $e) {
            // Ignore duplicate or missing column errors
        }

        try {
            DB::statement('ALTER TABLE `sms_logs` ADD INDEX `sms_logs_status_created_at_index`(`status`, `created_at`)');
        } catch (\Throwable $e) {
            // Ignore if index already exists or columns differ
        }

        try {
            DB::statement('ALTER TABLE `sms_logs` ADD INDEX `sms_logs_recipient_created_at_index`(`recipient`, `created_at`)');
        } catch (\Throwable $e) {
            // Ignore if index already exists or columns differ
        }

        try {
            DB::statement('ALTER TABLE `sms_logs` ADD INDEX `sms_logs_sender_id_created_at_index`(`sender_id`, `created_at`)');
        } catch (\Throwable $e) {
            // Ignore if index already exists or columns differ
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('sms_logs')) {
            return;
        }

        // Drop the indexes (ignore if they don't exist)
        try {
            DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_reference_index`');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_status_created_at_index`');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_recipient_created_at_index`');
        } catch (\Throwable $e) {
        }

        try {
            DB::statement('ALTER TABLE `sms_logs` DROP INDEX `sms_logs_sender_id_created_at_index`');
        } catch (\Throwable $e) {
        }
    }
}
