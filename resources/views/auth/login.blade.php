<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f7faf8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk — SIPAKARBUN</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="flex h-full items-center justify-center bg-[#173b29] p-4">
    <div class="w-full max-w-md">
        <div class="soft-card rounded-2xl border border-[#e4ece7] bg-white p-8">
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#173b29] text-white">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9M12 9c-3 0-5.5-2.5-5.5-5.5 3 0 5.5 2.5 5.5 5.5zM12 9c3 0 5.5-2.5 5.5-5.5C14.5 3.5 12 6 12 9zM8 15h8M9 18h6" />
                    </svg>
                </div>
                <h1 class="mt-4 text-xl font-extrabold tracking-tight text-[#173b29]">SIPAKARBUN</h1>
                <p class="mt-1 text-sm text-[#66746c]">Sistem Pakar Diagnosis &amp; Penanganan Kasus Sawit/Karet</p>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-[#173b29]">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                           class="w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm text-[#17211b] outline-none transition-colors placeholder:text-[#a0aba4] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-[#173b29]">Kata Sandi</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm text-[#17211b] outline-none transition-colors placeholder:text-[#a0aba4] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-[#66746c]">
                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-[#dbe5df] text-[#176b45] focus:ring-[#176b45]/15">
                    Ingat saya
                </label>

                <button type="submit"
                        class="w-full rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#173b29]">
                    Masuk
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#8a9990]">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-[#176b45] hover:underline">Daftar</a>
            </p>
        </div>
    </div>
</body>
</html>