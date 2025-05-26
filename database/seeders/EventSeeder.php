<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // (Removed (or commented out) the query (or insert) that used a “role” column (or a “role” where clause).)
        /*
        $admin = DB::table('users')->where('role', 'admin')->first();
        if ($admin) {
            DB::table('events')->insert([
                'name' => 'Demo Event',
                'description' => 'A demo event.',
                 'start_date' => now(),
                 'end_date' => now()->addDays(1),
                 'venue' => 'Demo Venue',
                 'status' => 'draft',
                 'is_public' => false,
                 'location' => 'Demo Location',
                 'capacity' => 100,
                 'banner' => null,
                 'created_by' => $admin->id,
                 'created_at' => now(),
                 'updated_at' => now(),
            ]);
        }
        */
    }
} 