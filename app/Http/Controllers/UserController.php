<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(): View
    {
        $users = User::with('roles')
            ->latest()
            ->paginate(15);

        $roles = \App\Models\Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:admin,scanner,organizer'],
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->assignRole($validated['role']);

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', 'in:admin,scanner,organizer'],
        ]);

        try {
            DB::beginTransaction();

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            
            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            // Update role
            $user->syncRoles([$validated['role']]);

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                throw new \Exception('You cannot delete your own account.');
            }

            $user->delete();

            DB::commit();

            return redirect()
                ->route('users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Prevent self-deactivation
            if ($user->id === auth()->id()) {
                throw new \Exception('You cannot deactivate your own account.');
            }

            $user->is_active = !$user->is_active;
            $user->save();

            DB::commit();

            $status = $user->is_active ? 'activated' : 'deactivated';
            return back()->with('success', "User {$status} successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User status toggle failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    public function export()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }
} 