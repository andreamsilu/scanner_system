<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create roles table (if it does not already exist) (with columns id, name (unique), created_at, updated_at)
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        // Create pivot table (user_role) (with columns user_id, role_id, created_at, updated_at) (if it does not already exist)
        if (!Schema::hasTable('user_role')) {
            Schema::create('user_role', function (Blueprint $table) {
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
        // Drop pivot table (user_role) (if it exists)
        if (Schema::hasTable('user_role')) {
            Schema::dropIfExists('user_role');
        }
        // Drop roles table (if it exists) (and if you want to remove it on rollback)
        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }
    }
}; 