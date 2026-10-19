<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (config('invoice.local_mode', true) || session('authenticated') === true) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $configuredPassword = config('invoice.app_password', 'secret123');

        if (hash_equals((string) $configuredPassword, (string) $request->input('password'))) {
            $request->session()->put('authenticated', true);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'))->with('success', __('app.login_success'));
        }

        return back()->withErrors(['password' => __('app.login_failed')]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', __('app.logout_success'));
    }
}
