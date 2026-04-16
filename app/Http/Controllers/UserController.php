<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users with optional role filter.
     */
    public function index(Request $request): View
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        $roles = [
            User::ROLE_SUPERADMIN,
            User::ROLE_ADMIN_IT,
            User::ROLE_OPERATOR,
            User::ROLE_AUDITOR,
        ];

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $roles = [
            User::ROLE_SUPERADMIN,
            User::ROLE_ADMIN_IT,
            User::ROLE_OPERATOR,
            User::ROLE_AUDITOR,
        ];

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8', 'confirmed'],
            'role' => ['required', 'in:superadmin,admin_it,operator,auditor'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        ActivityLog::log('user_created', "User '{$user->name}' created with role '{$user->role}'", [
            'target_user_id' => $user->id,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $roles = [
            User::ROLE_SUPERADMIN,
            User::ROLE_ADMIN_IT,
            User::ROLE_OPERATOR,
            User::ROLE_AUDITOR,
        ];

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'min:8', 'confirmed'],
            'role' => ['required', 'in:superadmin,admin_it,operator,auditor'],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        ActivityLog::log('user_updated', "User '{$user->name}' updated", [
            'target_user_id' => $user->id,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user (prevent self-delete).
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;

        // Clean up related data
        $user->cameraPermissions()->delete();
        $user->streamSessions()->delete();
        $user->delete();

        ActivityLog::log('user_deleted', "User '{$name}' deleted");

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Toggle the user's is_active status.
     */
    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        ActivityLog::log('user_toggled', "User '{$user->name}' {$status}", [
            'target_user_id' => $user->id,
            'is_active' => $user->is_active,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "User {$status} successfully.");
    }
}
