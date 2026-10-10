<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\ActivityLog;
use App\Models\RefKelompokTani;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
        $data = $request->validate([
            'identitas' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'string', 'max:150'],
            'password' => ['required'],
        ]);

        // Kompatibilitas: form lama mengirim `email`, form baru `identitas`.
        $identitas = trim((string) ($data['identitas'] ?? $data['email'] ?? ''));

        if ($identitas === '') {
            return back()->withErrors(['identitas' => 'Email atau Kode Poktan wajib diisi.'])->withInput();
        }

        $user = null;

        if (filter_var($identitas, FILTER_VALIDATE_EMAIL)) {
            if (auth()->attempt(['email' => $identitas, 'password' => $data['password']], $request->boolean('remember'))) {
                $user = auth()->user();
            }
        } else {
            // Login Poktan memakai Kode Poktan (kode_kelompok/kode Disbun).
            $user = User::query()
                ->where('kelompok_tani_kode', $identitas)
                ->first();

            if ($user === null) {
                $kelompokTani = RefKelompokTani::query()
                    ->tersedia()
                    ->where(function ($query) use ($identitas): void {
                        $query->where('kode_kelompok', $identitas)->orWhere('kode', $identitas);
                    })
                    ->first();

                $user = $kelompokTani === null
                    ? null
                    : User::query()->where('kelompok_tani_id', $kelompokTani->id)->first();
            }

            if ($user === null || ! Hash::check((string) $data['password'], $user->password)) {
                $user = null;
            } else {
                auth()->login($user, $request->boolean('remember'));
                $user = auth()->user();
            }
        }

        if ($user === null) {
            return back()->with('error', 'Email/Kode Poktan atau password salah.')->withInput();
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
        if (!$user->hasRole(['admin', 'operator_uptd', 'popt', 'poktan', 'pimpinan'])) {
            auth()->logout();
            $request->session()->invalidate();
            return back()->with('error', 'Role akun belum memiliki modul aplikasi yang tersedia.');
        }

        if ($user->hasRole('pimpinan')) {
            return redirect()->route('monitoring.dashboard');
        }

        // Semua role operasional memakai dashboard shell bersama; modul yang
        // tampil tetap dibatasi oleh role di dalam dashboard dan route backend.
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
        $roles = auth()->user()?->hasRole('admin')
            ? RegisterRequest::ROLE_OPTIONS
            : RegisterRequest::PUBLIC_ROLE_OPTIONS;

        // Muat SELURUH referensi tersedia agar dropdown menampilkan A-Z
        // lengkap; penyaringan dilakukan di browser (daftar ~5.600 baris).
        $kelompokTaniList = $this->cariKelompokTaniTersedia('', null, null);

        return view('auth.register', compact('roles', 'kelompokTaniList'));
    }

    /**
     * Opsi kelompok tani untuk form registrasi publik (tanpa login).
     * Hanya mengembalikan referensi yang tersedia dari data Disbun.
     */
    public function kelompokTaniOptions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $selected = $request->integer('selected');

        return response()->json([
            'data' => $this->cariKelompokTaniTersedia($query, $selected > 0 ? $selected : null),
        ]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $role = (string) $request->input('role', 'poktan');

        if ($role === 'poktan') {
            // Akun Poktan hanya butuh Kelompok Tani + Password.
            // Nama/email dibuat otomatis dari data referensi Disbun.
            $kelompokTani = RefKelompokTani::query()->tersedia()->findOrFail($request->integer('kelompok_tani_id'));
            $kode = (string) ($kelompokTani->kode_kelompok ?: $kelompokTani->kode);

            $user = User::create([
                'name' => (string) $kelompokTani->nama,
                'email' => 'poktan-'.$kelompokTani->disbun_record_id.'@sipakarbun.local',
                'password' => Hash::make($request->password),
                'phone' => null,
                'is_active' => false,
                'kelompok_tani_id' => $kelompokTani->id,
                'kelompok_tani_kode' => $kode,
                'kelompok_tani_nama' => (string) $kelompokTani->nama,
            ]);

            $detail = "Poktan: \"{$kelompokTani->nama}\" ({$kode})";
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => false,
            ]);

            $detail = (string) $request->email;
        }

        $user->assignRole($role);

        ActivityLog::record(
            'User',
            'created',
            $user->name,
            $user->id,
            "Registrasi akun baru: \"{$user->name}\" ({$detail}) sebagai {$role} — menunggu persetujuan Admin",
        );

        return redirect()->route('login')
            ->with('success', 'Registrasi berhasil. Akun Anda menunggu persetujuan Admin sebelum dapat digunakan untuk login.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cariKelompokTaniTersedia(string $query, ?int $selected, ?int $limit = 25): array
    {
        $fields = ['id', 'disbun_record_id', 'kode', 'kode_kelompok', 'nama', 'jenis_komoditi', 'kabupaten', 'kecamatan', 'desa', 'kelurahan'];

        $rows = RefKelompokTani::query()
            ->tersedia()
            ->when($query !== '', function ($builder) use ($query): void {
                $like = '%'.$query.'%';
                $builder->where(function ($search) use ($like): void {
                    $search->where('nama', 'like', $like)
                        ->orWhere('kode', 'like', $like)
                        ->orWhere('kode_kelompok', 'like', $like)
                        ->orWhere('kabupaten', 'like', $like)
                        ->orWhere('kecamatan', 'like', $like)
                        ->orWhere('kelurahan', 'like', $like);
                });
            })
            ->orderBy('nama')
            ->when($limit !== null, fn ($builder) => $builder->limit($limit))
            ->get($fields);

        if ($selected !== null && $selected > 0 && ! $rows->contains('id', $selected)) {
            $chosen = RefKelompokTani::query()->tersedia()->whereKey($selected)->first($fields);
            if ($chosen !== null) {
                $rows->push($chosen);
            }
        }

        return $rows->map(fn (RefKelompokTani $row): array => [
            'id' => (int) $row->id,
            'kode' => (string) ($row->kode_kelompok ?: $row->kode ?? ''),
            'nama' => (string) $row->nama,
            'jenis_komoditi' => $row->jenis_komoditi,
            'kabupaten' => $row->kabupaten,
            'kecamatan' => $row->kecamatan,
            'desa' => $row->desa,
            'kelurahan' => $row->kelurahan,
        ])->values()->all();
    }
}
