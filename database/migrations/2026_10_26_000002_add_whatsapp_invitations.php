<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendees', function (Blueprint $table) {
            $table->timestamp('whatsapp_opted_in_at')->nullable();
            $table->string('whatsapp_phone', 20)->nullable()->index();
            $table->string('whatsapp_opt_in_source')->nullable();
        });
        Schema::create('whatsapp_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('attendee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient', 20);
            $table->string('template_name');
            $table->string('language', 20);
            $table->string('status', 20)->default('queued')->index();
            $table->string('message_id')->nullable()->unique();
            $table->string('error_code', 40)->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
            $table->index(['event_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_invitations');
        Schema::table('attendees', fn (Blueprint $table) => $table->dropColumn([
            'whatsapp_opted_in_at', 'whatsapp_phone', 'whatsapp_opt_in_source',
        ]));
    }
};
