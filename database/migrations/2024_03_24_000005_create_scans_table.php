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
        Schema::create('scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('scanner_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('scanned_at');
            $table->string('status')->default('success'); // success, failed, duplicate
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for better query performance
            $table->index(['event_id', 'scanned_at']);
            $table->index(['ticket_id', 'scanned_at']);
            $table->index(['scanner_id', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scans');
    }
}; 