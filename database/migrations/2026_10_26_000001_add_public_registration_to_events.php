<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('public_registration_enabled')->default(false);
            $table->uuid('public_registration_token')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['public_registration_token']);
            $table->dropColumn(['public_registration_enabled', 'public_registration_token']);
        });
    }
};
