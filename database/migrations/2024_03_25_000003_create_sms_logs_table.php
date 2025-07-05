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
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->text('message');
            $table->string('type')->default('notification'); // invitation, notification, reminder
            $table->string('status')->nullable(); // sent, delivered, failed
            $table->string('message_id')->nullable(); // Provider's message ID
            $table->json('metadata')->nullable(); // Additional data like event_id, ticket_id, etc.
            $table->string('provider')->nullable(); // SMS provider name
            $table->text('error')->nullable(); // Error message if any
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('to');
            $table->index('type');
            $table->index('status');
            $table->index('message_id');
            $table->index('provider');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
}; 