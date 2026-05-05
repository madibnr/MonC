<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Show the settings page.
     */
    public function index(): View
    {
        $user = Auth::user();

        return view('settings.index', compact('user'));
    }

    /**
     * Update the current user's profile (name, email).
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
        ]);

        $user->update($validated);

        ActivityLog::log('profile_updated', 'User updated their profile');

        return redirect()
            ->route('settings.index')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the current user's password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8', 'confirmed'],
        ]);

        // Verify current password
        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => $validated['new_password'],
        ]);

        ActivityLog::log('password_changed', 'User changed their password');

        return redirect()
            ->route('settings.index')
            ->with('success', 'Password changed successfully.');
    }
}
