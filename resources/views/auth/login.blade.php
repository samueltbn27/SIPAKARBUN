<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f2f5f1]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIPAKARBUN</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        [x-cloak] { display: none !important; }
        .auth-shell { display: grid; }
        .auth-hero { min-height: clamp(620px, calc(100vh - 5.6rem), 710px); }
        .auth-form-logo { width: 4.85rem; height: 4.85rem; }
        .auth-form-logo > svg { width: 2.35rem; height: 2.35rem; }
        @media (max-width: 1100px) { .auth-shell { grid-template-columns: 1fr; } .auth-hero { min-height: 24rem; } }
        @media (max-width: 640px) { .auth-hero { display: none; } .auth-form-logo { width: 4rem; height: 4rem; } }
    </style>
    @vite(['resources/css/app.css', 'resources/js/page-loader.js'])
</head>
<body class="auth-page" x-data="{ showPassword: false, submitting: false }">
    <x-page-loader />
    <main class="auth-shell">
        <section class="auth-hero" aria-label="Tentang SIPAKARBUN">
            <div class="auth-hero-content">
                <div class="auth-brand-glass">
                    <div class="auth-brand-row">
                        <span class="auth-brand-mark">
                            <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 4C10 4 5 8 5 15c0 2.2 1.8 4 4 4 7 0 11-6 11-15ZM4 20c3-4 6-6 10-8"/></svg>
                        </span>
                        <div>
                            <div class="auth-brand-name">SIPAKARBUN</div>
                            <div class="auth-brand-subtitle">Sistem Pakar Perkebunan</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-form-panel" aria-labelledby="login-title">
            <div class="auth-form-inner">
                <div class="auth-form-heading">
                    <span class="auth-form-logo">
                        <svg width="38" height="38" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 4C10 4 5 8 5 15c0 2.2 1.8 4 4 4 7 0 11-6 11-15ZM4 20c3-4 6-6 10-8"/></svg>
                    </span>
                    <h2 id="login-title" class="auth-form-title">SIPAKARBUN</h2>
                    <p class="auth-form-subtitle">Sistem Pakar Perkebunan</p>
                </div>

                <div class="auth-form-intro">
                    <h3>Masuk ke SIPAKARBUN</h3>
                    <p>Gunakan akun yang telah terdaftar.</p>
                </div>

                @if(session('success'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-[#b8ddc7] bg-[#e8f4ed] p-4 text-sm text-[#176b45]" role="status">
                        <svg width="20" height="20" class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
                        <svg width="20" height="20" class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="auth-form" @submit="submitting = true">
                    @csrf
                    <div>
                        <label for="email" class="auth-field-label">Email</label>
                        <div class="auth-field-control">
                            <svg width="20" height="20" class="auth-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 6.75A1.75 1.75 0 014.75 5h14.5A1.75 1.75 0 0121 6.75v10.5A1.75 1.75 0 0119.25 19H4.75A1.75 1.75 0 013 17.25V6.75z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m4 6 8 6 8-6"/></svg>
                            <input type="email" id="email" name="email" required autofocus autocomplete="email" value="{{ old('email') }}" class="auth-field-input" placeholder="Masukkan email Anda">
                        </div>
                    </div>
                    <div>
                        <label for="password" class="auth-field-label">Password</label>
                        <div class="auth-field-control">
                            <svg width="20" height="20" class="auth-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 10V7a5 5 0 0110 0v3m-11 0h12a1 1 0 011 1v9a1 1 0 01-1 1H6a1 1 0 01-1-1v-9a1 1 0 011-1z"/></svg>
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required autocomplete="current-password" class="auth-field-input" placeholder="Masukkan password Anda">
                            <button type="button" @click="showPassword = !showPassword" class="auth-field-toggle" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                                <svg x-show="!showPassword" x-cloak width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6z"/><circle cx="12" cy="12" r="2.5" stroke-width="1.7"/></svg>
                                <svg x-show="showPassword" x-cloak width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m3 3 18 18M10.6 10.6a2.5 2.5 0 003.8 3.8M6.3 6.4C3.8 8 2.5 12 2.5 12s3.3 6 9.5 6c1.3 0 2.5-.3 3.5-.8M9.6 6.1C10.3 6 11.1 6 12 6c6.2 0 9.5 6 9.5 6s-.9 1.7-2.5 3.2"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="auth-options">
                        <label class="auth-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label>
                        <span class="auth-muted-action">Lupa password?</span>
                    </div>
                    <button type="submit" class="auth-submit" :disabled="submitting">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0-12 4 4m-4-4L8 7M5 13v5a2 2 0 002 2h10a2 2 0 002-2v-5"/></svg>
                        <span x-text="submitting ? 'Memproses...' : 'Masuk'">Masuk</span>
                    </button>
                </form>

                <p class="auth-register">
                    Belum punya akun?
                    <a href="{{ route('register') }}">Daftar akun</a>
                </p>
            </div>
        </section>
    </main>

    <footer class="auth-footer">
        <div class="auth-footer-items">
            <span class="auth-footer-item"><i></i> Berbasis Data</span>
            <span class="auth-footer-item"><i></i> Akurat &amp; Terpercaya</span>
            <span class="auth-footer-item"><i></i> Terintegrasi</span>
        </div>
        <span>&copy; 2026 SIPAKARBUN — Dinas Perkebunan Provinsi Jawa Barat</span>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
