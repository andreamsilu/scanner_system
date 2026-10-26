<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration recreates the database structure (dropping all tables except "migrations") and then creates tables in the order they are defined (i.e. users, roles, events, attendees, tickets, scan_logs, activity_logs, ticket_types, notifications, and system tables). 
     * 
     * Note: No redundancy (i.e. no duplicate tables or foreign keys) is introduced. (For example, the old "scanner_operators" table (which was redundant with "users") and the old "scans" (redundant with "scan_logs") have been removed.) 
     */
    public function up(): void
    {
        // A baseline must never erase a populated database.
        foreach (['users', 'roles', 'events', 'attendees', 'tickets'] as $table) {
            if (Schema::hasTable($table)) {
                throw new RuntimeException('Baseline tables already exist. Reconcile migration history before continuing.');
            }
        }

        // Create tables in the order they are defined (i.e. follow the table structure)
        $this->createUsersTable();
        $this->createRolesTable();
        $this->createEventsTable();
        $this->createAttendeesTable();
        $this->createTicketsTable();
        $this->createScanLogsTable();
        $this->createActivityLogsTable();
        $this->createTicketTypesTable();
        $this->createNotificationsTable();
        $this->createSystemTables();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException('Restore a backup to reverse the database baseline.');
    }

    private function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createRolesTable(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['role_id', 'user_id']);
        });
    }

    private function createEventsTable(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('venue');
            $table->string('status')->default('pending');
            $table->string('location')->nullable();
            $table->integer('capacity');
            $table->string('banner')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createAttendeesTable(): void
    {
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->string('token', 7)->unique();
            $table->string('qr_code')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Ensure unique email per event
            $table->unique(['event_id', 'email']);
        });
    }

    private function createTicketsTable(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendee_id')->constrained()->onDelete('cascade');
            $table->string('ticket_number')->unique();
            $table->enum('type', ['regular', 'vip', 'single', 'double'])->default('regular');
            $table->enum('status', ['active', 'used', 'cancelled'])->default('active');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Ensure one ticket per attendee per event
            $table->unique(['event_id', 'attendee_id']);
        });
    }

    private function createScanLogsTable(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scanner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ticket_id')->constrained()->onDelete('cascade');
            $table->timestamp('scanned_at');
            $table->enum('status', ['success', 'invalid', 'duplicate'])->default('success');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    private function createActivityLogsTable(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->text('description');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    private function createTicketTypesTable(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function createNotificationsTable(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function createSystemTables(): void
    {
        // Cache tables
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Queue tables
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Session table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Password reset table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
