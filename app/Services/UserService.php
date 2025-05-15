<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function getAllUsers(): LengthAwarePaginator
    {
        return User::with('roles')->paginate(10);
    }

    public function getUserById(int $id): User
    {
        $user = User::find($id);
        
        if (!$user) {
            throw new ModelNotFoundException('User not found.');
        }

        return $user;
    }

    public function createUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true
            ]);

            $user->roles()->sync($data['roles']);

            return $user;
        });
    }

    public function updateUser(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->getUserById($id);

            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'] ?? $user->is_active
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            $user->update($updateData);
            $user->roles()->sync($data['roles']);

            return $user;
        });
    }

    public function deleteUser(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = $this->getUserById($id);
            
            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                throw new \Exception('You cannot delete your own account.');
            }

            $user->roles()->detach();
            return $user->delete();
        });
    }

    public function toggleUserStatus(int $id): User
    {
        return DB::transaction(function () use ($id) {
            $user = $this->getUserById($id);
            
            // Prevent self-deactivation
            if ($user->id === auth()->id()) {
                throw new \Exception('You cannot deactivate your own account.');
            }

            $user->update(['is_active' => !$user->is_active]);
            return $user;
        });
    }
} 