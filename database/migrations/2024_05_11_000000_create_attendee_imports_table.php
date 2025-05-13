<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendee_imports', function (Blueprint $table) {
            $table->id();
            $table->integer('total_records');
            $table->integer('success_count');
            $table->integer('error_count');
            $table->string('status');
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendee_imports');
    }
}; 