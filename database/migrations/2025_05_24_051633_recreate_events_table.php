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
        // Disable foreign key checks so that we can drop the events table even if it is referenced by foreign keys (e.g. from tickets).
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Drop the table if it exists
        Schema::dropIfExists('events');
        // Re-enable foreign key checks.
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create the events table with the correct structure
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('venue');
            $table->string('location');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('capacity')->unsigned();
            $table->string('status')->default('pending');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('events');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
