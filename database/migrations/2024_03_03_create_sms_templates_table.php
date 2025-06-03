<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('notification'); // invitation, notification, reminder
            $table->text('content');
            $table->text('variables')->nullable(); // JSON array of available variables
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Insert default templates
        DB::table('sms_templates')->insert([
            [
                'name' => 'Default Invitation',
                'type' => 'invitation',
                'content' => "MWALIKO: {attendee_name}\nSENDOFF ya ALICE PIENZI CHAMBILA\nTarehe: {event_date}\nSaa: {event_time}\nMahali: {event_location}\nToken: {ticket_token}",
                'variables' => json_encode(['attendee_name', 'event_date', 'event_time', 'event_location', 'ticket_token']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Default Reminder',
                'type' => 'reminder',
                'content' => "Reminder: {event_name} is tomorrow! Date: {event_date}. Location: {event_location}. Your ticket code: {ticket_code}. Visit {ticket_url} to view your ticket.",
                'variables' => json_encode(['event_name', 'event_date', 'event_location', 'ticket_code', 'ticket_url']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Default Notification',
                'type' => 'notification',
                'content' => "Dear {attendee_name}, your ticket for {event_name} has been used at {check_in_time}. Thank you for attending!",
                'variables' => json_encode(['attendee_name', 'event_name', 'check_in_time']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
}; 