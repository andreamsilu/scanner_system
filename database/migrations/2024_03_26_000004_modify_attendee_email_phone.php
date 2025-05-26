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
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop foreign key constraints that depend on the unique index
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['attendee_id']);
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });

        // Now modify the attendees table
        Schema::table('attendees', function (Blueprint $table) {
            // Make email nullable
            $table->string('email')->nullable()->change();
            
            // Make phone required
            $table->string('phone')->nullable(false)->change();
            
            // Update unique constraint to only apply when email is not null
            $table->dropUnique(['event_id', 'email']);
            $table->unique(['event_id', 'email'], 'attendees_event_email_unique')->where('email IS NOT NULL');
        });

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop foreign key constraints
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['attendee_id']);
        });

        Schema::table('scans', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
        });

        // Revert the attendees table changes
        Schema::table('attendees', function (Blueprint $table) {
            // Revert email to required
            $table->string('email')->nullable(false)->change();
            
            // Revert phone to nullable
            $table->string('phone')->nullable()->change();
            
            // Restore original unique constraint
            $table->dropUnique('attendees_event_email_unique');
            $table->unique(['event_id', 'email']);
        });

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