<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function getAllUsers(): LengthAwarePaginator
    {
        return User::with('roles')->paginate(10);
    }

    public function getUserById(int $id): User
    {
        return User::with('roles')->findOrFail($id);
    }

    public function createUser(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        return $user;
    }

    public function updateUser(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user->syncRoles([$data['role']]);

        return $user;
    }

    public function deleteUser(int $id): bool
    {
        $user = User::findOrFail($id);
        return $user->delete();
    }
} 