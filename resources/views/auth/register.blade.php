<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f7faf8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar — SIPAKARBUN</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-full items-center justify-center bg-[#173b29] p-4">
    <div class="w-full max-w-md">
        <div class="soft-card rounded-2xl border border-[#e4ece7] bg-white p-8">
            <div class="mb-8 flex flex-col items-center text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#173b29] text-white">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21V9M12 9c-3 0-5.5-2.5-5.5-5.5 3 0 5.5 2.5 5.5 5.5zM12 9c3 0 5.5-2.5 5.5-5.5C14.5 3.5 12 6 12 9zM8 15h8M9 18h6" />
                    </svg>
                </div>
                <h1 class="mt-4 text-xl font-extrabold tracking-tight text-[#173b29]">Daftar Akun Baru</h1>
                <p class="mt-1 text-sm text-[#66746c]">Akun akan aktif setelah disetujui Admin.</p>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-700">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-[#173b29]">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm text-[#17211b] outline-none transition-colors placeholder:text-[#a0aba4] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                </div>

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-[#173b29]">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                           class="w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm text-[#17211b] outline-none transition-colors placeholder:text-[#a0aba4] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                </div>

                <div>
                    <label for="role" class="mb-1.5 block text-sm font-semibold text-[#173b29]">Peran</label>
                    <select id="role" name="role" required
                            class="w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm text-[#17211b] outline-none transition-colors focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                        <option value="" disabled {{ old('role') ? '' : 'selected' }}>Pilih peran…</option>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-[#173b29]">Kata Sandi</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           class="w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm text-[#17211b] outline-none transition-colors placeholder:text-[#a0aba4] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-[#173b29]">Ulangi Kata Sandi</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"
                           class="w-full rounded-xl border border-[#dbe5df] bg-white px-4 py-2.5 text-sm text-[#17211b] outline-none transition-colors placeholder:text-[#a0aba4] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-[#176b45] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#173b29]">
                    Daftar
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#8a9990]">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-[#176b45] hover:underline">Masuk</a>
            </p>
        </div>
    </div>
</body>
</html>