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
            $table->boolean('rsvp_enabled')->default(false)->after('capacity');
            $table->string('rsvp_option_1_label')->nullable()->after('rsvp_enabled');
            $table->string('rsvp_option_1_phone')->nullable()->after('rsvp_option_1_label');
            $table->string('rsvp_option_2_label')->nullable()->after('rsvp_option_1_phone');
            $table->string('rsvp_option_2_phone')->nullable()->after('rsvp_option_2_label');
            $table->text('rsvp_message')->nullable()->after('rsvp_option_2_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'rsvp_enabled',
                'rsvp_option_1_label',
                'rsvp_option_1_phone',
                'rsvp_option_2_label',
                'rsvp_option_2_phone',
                'rsvp_message'
            ]);
        });
    }
};
