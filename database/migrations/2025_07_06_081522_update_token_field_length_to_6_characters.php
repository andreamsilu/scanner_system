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
            $table->string('token', 64)->change();
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('token', 64)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendees', function (Blueprint $table) {
            $table->string('token', 64)->change();
        });
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('token', 64)->change();
        });
    }
};
