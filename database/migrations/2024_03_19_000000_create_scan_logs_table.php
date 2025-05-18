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
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('scanner_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['success', 'duplicate', 'error'])->default('success');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for faster queries
            $table->index(['ticket_id', 'created_at']);
            $table->index(['scanner_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
}; 