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
        // Nullable email permits multiple missing emails while retaining uniqueness for supplied addresses.
        // Changing these columns does not require dropping any foreign keys or indexes.
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop foreign key constraints if they exist
        if (Schema::hasTable('tickets')) {
            try {
                DB::statement('ALTER TABLE `tickets` DROP FOREIGN KEY `tickets_attendee_id_foreign`');
            } catch (\Throwable $e) {
                // Ignore if it doesn't exist
            }
        }

        if (Schema::hasTable('scans')) {
            try {
                DB::statement('ALTER TABLE `scans` DROP FOREIGN KEY `scans_ticket_id_foreign`');
            } catch (\Throwable $e) {
                // Ignore if it doesn't exist
            }
        }

        // Revert the attendees table changes (best-effort, protected by try/catch)
        if (Schema::hasTable('attendees')) {
            try {
                Schema::table('attendees', function (Blueprint $table) {
                    // Revert email to required
                    $table->string('email')->nullable(false)->change();

                    // Revert phone to nullable
                    $table->string('phone')->nullable()->change();

                    // Restore original unique constraint where possible
                    try {
                        $table->dropUnique('attendees_event_email_unique');
                    } catch (\Throwable $e) {
                        // Ignore if that named index doesn't exist
                    }

                    try {
                        $table->unique(['event_id', 'email']);
                    } catch (\Throwable $e) {
                        // Ignore if index already exists or conflicts
                    }
                });
            } catch (\Throwable $e) {
                // Ignore any remaining schema issues on rollback
            }
        }

        // Recreate the foreign key constraints
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreign('attendee_id')
                  ->references('id')
                  ->on('attendees')
                  ->onDelete('cascade');
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->foreign('ticket_id')
                  ->references('id')
                  ->on('tickets')
                  ->onDelete('cascade');
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
