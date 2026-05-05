<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            ActivityLog::log('login', 'User logged in successfully');

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => __('auth.failed'),
            ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        ActivityLog::log('logout', 'User logged out');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
