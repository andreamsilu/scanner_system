<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'app:create-admin {email} {--name=Administrator}';

    protected $description = 'Create an administrator using a password entered privately';

    public function handle(): int
    {
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $input = ['email' => $this->argument('email'), 'name' => $this->option('name'), 'password' => $password, 'password_confirmation' => $confirmation];
        $validator = Validator::make($input, [
            'email' => 'required|email|unique:users,email', 'name' => 'required|string|max:255',
            'password' => 'required|string|min:12|confirmed',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($input) {
            $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Administrator']);
            Role::firstOrCreate(['slug' => 'scanner'], ['name' => 'Scanner']);
            $user = User::create(['name' => $input['name'], 'email' => $input['email'], 'password' => $input['password'], 'is_active' => true]);
            $user->roles()->attach($role);
        });
        $this->info('Administrator created.');

        return self::SUCCESS;
    }
}
