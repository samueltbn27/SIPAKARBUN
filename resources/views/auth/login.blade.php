<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f7faf8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SIPAKARBUN</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full flex items-center justify-center bg-gradient-to-br from-[#e8f4ed] to-[#f0f7f2] p-4" x-data="{ showPassword: false }">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-6">
            <span class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#176b45] text-white mb-3 shadow-lg">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 4C10 4 5 8 5 15c0 2.2 1.8 4 4 4 7 0 11-6 11-15ZM4 20c3-4 6-6 10-8"/></svg>
            </span>
            <h1 class="text-2xl font-extrabold tracking-tight text-[#173b29]">SIPAKARBUN</h1>
            <p class="text-sm text-[#8a9990] mt-1">Knowledge Management</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-xl border border-[#e4ece7] p-8">
            @if(session('success'))
                <div class="mb-5 rounded-xl bg-[#e8f4ed] border border-[#b8ddc7] p-4 text-sm text-[#176b45] flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-semibold text-[#314239] mb-1.5">Email</label>
                    <input type="email" id="email" name="email" required autofocus
                           value="{{ old('email') }}"
                           class="w-full rounded-lg border border-[#d6e0d9] px-3.5 py-2.5 text-sm outline-none transition focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15"
                           placeholder="Masukkan email Anda">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-[#314239] mb-1.5">Password</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                               class="w-full rounded-lg border border-[#d6e0d9] px-3.5 py-2.5 pr-10 text-sm outline-none transition focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15"
                               placeholder="••••••••">
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9aa59e] hover:text-[#176b45]">
                            <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit"
                        class="w-full rounded-lg bg-[#176b45] px-4 py-3 text-sm font-bold text-white hover:bg-[#115a39] transition-colors shadow-sm">
                    Masuk
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#77847c]">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-[#176b45] hover:underline">Buat akun</a>
            </p>
        </div>

        <p class="text-center text-xs text-[#a0aba4] mt-6">
            &copy; 2026 SIPAKARBUN — Dinas Perkebunan
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
