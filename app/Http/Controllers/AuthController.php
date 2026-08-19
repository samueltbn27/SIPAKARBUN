<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Autentikasi web (guard session) untuk seluruh role SIPAKARBUN.
 *
 * Berbeda dari M1: gate login diperluas agar Poktan (petani modul M2)
 * bisa masuk. Setelah login, semua role diarahkan ke dashboard yang
 * menyajikan navigasi sesuai role masing-masing.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! auth()->attempt($credentials, $request->boolean('remember'))) {
            return back()->with('error', 'Email atau password salah.')->withInput();
        }

        $request->session()->regenerate();

        $user = auth()->user();

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();

            return back()->with('error', 'Akun Anda belum aktif. Silakan hubungi Admin untuk mengaktifkan akun Anda.')->withInput();
        }

        if (! $user->hasRole(['admin', 'popt', 'operator_uptd', 'poktan'])) {
            auth()->logout();
            $request->session()->invalidate();

            return back()->with('error', 'Anda tidak memiliki akses ke sistem SIPAKARBUN.');
        }

        Log::info('Login web berhasil.', [
            'user_id' => $user->getKey(),
            'role' => $user->roles->pluck('name')->first(),
        ]);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegister(): View
    {
        $roles = [
            'poktan' => 'Poktan (Kelompok Tani / Petani)',
            'operator_uptd' => 'Operator UPTD',
            'popt' => 'POPT (Pengamat OPT)',
        ];

        return view('auth.register', compact('roles'));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => false,
        ]);

        $user->assignRole($request->role);

        Log::info('Registrasi akun baru (menunggu persetujuan).', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'role' => $request->role,
        ]);

        return redirect()->route('login')
            ->with('success', 'Registrasi berhasil. Akun Anda menunggu persetujuan Admin sebelum dapat digunakan untuk login.');
    }
}
