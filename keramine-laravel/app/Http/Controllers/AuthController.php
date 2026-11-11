<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('keramine.login');
    }

    public function login(Request $request): RedirectResponse
    {
        if ($request->filled('quick_role')) {
            $email = match ($request->input('quick_role')) {
                'admin' => 'admin@keramine.id',
                'robby' => 'robby@keramine.id',
                default => 'dian@keramine.id',
            };
            $user = User::query()->where('email', $email)->first();
            if ($user) {
                Auth::login($user);
                $request->session()->regenerate();

                return redirect()->route('home')->with(
                    'status',
                    "Masuk sebagai {$user->name} (Role: {$user->role} · POF: {$user->pof_name})."
                );
            }
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('home'))->with('status', 'Berhasil masuk ke portal KeraMine B2B.');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi tidak cocok.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Anda telah keluar dari sesi portal.');
    }
}

