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
        Schema::table('events', function (Blueprint $table) {
            // Check and drop each column if it exists
            if (Schema::hasColumn('events', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('events', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('events', 'requires_registration')) {
                $table->dropColumn('requires_registration');
            }
            if (Schema::hasColumn('events', 'is_public')) {
                $table->dropColumn('is_public');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Add back columns only if they don't exist
            if (!Schema::hasColumn('events', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('events', 'type')) {
                $table->string('type')->default('conference');
            }
            if (!Schema::hasColumn('events', 'requires_registration')) {
                $table->boolean('requires_registration')->default(true);
            }
            if (!Schema::hasColumn('events', 'is_public')) {
                $table->boolean('is_public')->default(false);
            }
        });
    }
};
