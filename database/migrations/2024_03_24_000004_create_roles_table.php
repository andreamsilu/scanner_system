<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create roles table (if it does not already exist)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }

        // Create pivot table (role_user) (if it does not already exist)
        if (!Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
                $table->unique(['user_id', 'role_id']);
            });
        }
    }

    public function down(): void
    {
        // Drop pivot table (role_user) (if it exists)
        if (Schema::hasTable('role_user')) {
            Schema::dropIfExists('role_user');
        }
        // Drop roles table (if it exists)
        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }
    }
}; 