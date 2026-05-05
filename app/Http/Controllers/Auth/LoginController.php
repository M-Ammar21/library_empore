<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::guard('member')->check()) {
            return redirect()->route('member.dashboard');
        }

        return view('auth.login', [
            'title' => 'Login Library',
            'heading' => 'Masuk Library',
            'action' => route('login.store'),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        return $this->attemptLogin($request);
    }

    public function logout(Request $request, string $guard): RedirectResponse
    {
        abort_unless(in_array($guard, ['admin', 'member'], true), 404);

        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function attemptLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        foreach (['admin' => 'admin.dashboard', 'member' => 'member.dashboard'] as $guard => $redirectRoute) {
            if (Auth::guard($guard)->attempt($credentials, $remember)) {
                $request->session()->regenerate();

                return redirect()->intended(route($redirectRoute));
            }
        }

        throw ValidationException::withMessages([
            'email' => 'Email atau password tidak sesuai.',
        ]);
    }
}
