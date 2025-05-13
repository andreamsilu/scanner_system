<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index(): View
    {
        $users = User::with('roles')->latest()->paginate(15);
        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user = $this->userService->createUser($validated);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function show(int $id): View
    {
        $user = User::with('roles')->findOrFail($id);
        return view('users.show', compact('user'));
    }

    public function edit(int $id): View
    {
        $user = $this->userService->getUserById($id);
        return view('users.edit', compact('user'));
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

        $user = $this->userService->updateUser($id, $validated);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->userService->deleteUser($id);

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
} 