<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
        $this->middleware('auth');
        $this->middleware('role:admin')->except(['show', 'edit', 'update']);
    }

    public function index(Request $request): View
    {
        $query = User::with(['roles', 'activityLogs'])
            ->when($request->search, function($q) use ($request) {
                $q->where(function($query) use ($request) {
                    $query->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->when($request->role, function($q) use ($request) {
                $q->whereHas('roles', function($query) use ($request) {
                    $query->where('id', $request->role);
                });
            })
            ->when($request->status !== null, function($q) use ($request) {
                $q->where('is_active', $request->status);
            });

        $users = $query->latest()->paginate(15)->withQueryString();
        $roles = \App\Models\Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        $roles = \App\Models\Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean'
        ]);

        try {
            $user = $this->userService->createUser($validated);
            
            Log::info('User created', [
                'user_id' => $user->id,
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to create user. Please try again.');
        }
    }

    public function show(int $id): View
    {
        $user = User::with(['roles', 'activityLogs' => function($query) {
            $query->latest()->take(10);
        }])->findOrFail($id);

        return view('users.show', compact('user'));
    }

    public function edit(int $id): View
    {
        $user = $this->userService->getUserById($id);
        $roles = \App\Models\Role::all();
        
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
            'is_active' => 'boolean'
        ]);

        try {
            $user = $this->userService->updateUser($id, $validated);
            
            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => auth()->id()
            ]);

            return redirect()
                ->route('users.show', $user)
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'data' => $validated
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update user. Please try again.');
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->userService->deleteUser($id);
            
            Log::info('User deleted', [
                'user_id' => $id,
                'deleted_by' => auth()->id()
            ]);

            return redirect()
                ->route('users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);

            return back()->with('error', 'Failed to delete user. Please try again.');
        }
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        try {
            $user = $this->userService->toggleUserStatus($id);
            
            Log::info('User status toggled', [
                'user_id' => $id,
                'new_status' => $user->is_active,
                'toggled_by' => auth()->id()
            ]);

            return back()->with('success', 'User status updated successfully.');
        } catch (\Exception $e) {
            Log::error('User status toggle failed', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);

            return back()->with('error', 'Failed to update user status. Please try again.');
        }
    }

    public function export()
    {
        return Excel::download(new UsersExport, 'users.xlsx');
    }
} 