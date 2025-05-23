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
        Schema::table('attendees', function (Blueprint $table) {
            // Make email nullable
            $table->string('email')->nullable()->change();
            
            // Make phone required
            $table->string('phone')->nullable(false)->change();
            
            // Update unique constraint to only apply when email is not null
            $table->dropUnique(['event_id', 'email']);
            $table->unique(['event_id', 'email'], 'attendees_event_email_unique')->where('email IS NOT NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendees', function (Blueprint $table) {
            // Revert email to required
            $table->string('email')->nullable(false)->change();
            
            // Revert phone to nullable
            $table->string('phone')->nullable()->change();
            
            // Restore original unique constraint
            $table->dropUnique('attendees_event_email_unique');
            $table->unique(['event_id', 'email']);
        });
    }
}; 