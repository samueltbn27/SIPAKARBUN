<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

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

        if (!auth()->attempt($credentials, $request->boolean('remember'))) {
            return back()->with('error', 'Email atau password salah.')->withInput();
        }

        $request->session()->regenerate();

        $user = auth()->user();

        // Cek akun aktif
        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            return back()->with('error', 'Akun Anda menunggu persetujuan Admin. Silakan hubungi Admin untuk mengaktifkan akun Anda.')->withInput();
        }

        // Role tetap diverifikasi dari relasi user di backend, bukan dari input browser.
        if (!$user->hasRole(['admin', 'popt', 'operator_uptd', 'pimpinan'])) {
            auth()->logout();
            $request->session()->invalidate();
            return back()->with('error', 'Role akun belum memiliki modul aplikasi yang tersedia.');
        }

        if ($user->hasRole('pimpinan')) {
            return redirect()->route('monitoring.dashboard');
        }

        return redirect()->route('knowledge.dashboard');
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
            'admin' => 'Admin',
            'popt' => 'POPT (Pengamat Organisme Pengganggu Tumbuhan)',
            'operator_uptd' => 'OP (Operator)',
        ];

        return view('auth.register', compact('roles'));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'is_active' => false,
        ]);

        $user->assignRole($request->role);

        ActivityLog::record(
            'User',
            'created',
            $user->name,
            $user->id,
            "Registrasi akun baru: \"{$user->name}\" ({$user->email}) sebagai {$request->role} — menunggu persetujuan Admin",
        );

        return redirect()->route('login')
            ->with('success', 'Registrasi berhasil. Akun Anda menunggu persetujuan Admin sebelum dapat digunakan untuk login.');
    }
}
