<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        $query = User::with('roles');

        // Apply role filter if selected
        if (request('role')) {
            $query->whereHas('roles', function($q) {
                $q->where('roles.id', request('role'));
            });
        }

        // Apply status filter if selected
        if (request('status') !== null) {
            $query->where('is_active', request('status'));
        }

        // Apply search filter if provided
        if (request('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $user->roles()->attach($validated['roles']);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User creation failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::with(['roles', 'activityLogs' => function($query) {
            $query->latest()->take(10);
        }])->findOrFail($id);

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($id): View
    {
        $user = User::with('roles')->findOrFail($id);
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => $validated['is_active'] ?? $user->is_active,
            ]);

            $user->roles()->sync($validated['roles']);

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User update failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy($id): RedirectResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent self-deletion
            if ($user->id === auth()->id()) {
                return back()->with('error', 'You cannot delete your own account.');
            }
            if ($user->scanLogs()->exists()) {
                return back()->with('error', 'This user has admission history. Deactivate the account instead.');
            }

            DB::beginTransaction();
            
            // Remove role associations
            $user->roles()->detach();
            
            // Delete the user
            $user->delete();

            DB::commit();

            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('User deletion failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete user. Please try again.');
        }
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus($id): RedirectResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent self-deactivation
            if ($user->id === auth()->id()) {
                return back()->with('error', 'You cannot deactivate your own account.');
            }

            $user->update(['is_active' => !$user->is_active]);

            return back()->with('success', 
                'User ' . ($user->is_active ? 'activated' : 'deactivated') . ' successfully.');
        } catch (\Exception $e) {
            Log::error('User status toggle failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to update user status. Please try again.');
        }
    }

    /**
     * Export users to CSV.
     */
    public function export()
    {
        $users = User::with('roles')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['ID', 'Name', 'Email', 'Roles', 'Status', 'Created At']);
            
            // Add data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->roles->pluck('name')->implode(', '),
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
