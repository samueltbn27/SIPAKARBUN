<!DOCTYPE html>
<html lang="id" class="h-full bg-[#f7faf8]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi — SIPAKARBUN</title>
    <style>[x-cloak]{display:none!important}</style>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    @vite(['resources/css/app.css', 'resources/js/page-loader.js'])
</head>
<body class="h-full flex items-center justify-center bg-gradient-to-br from-[#e8f4ed] to-[#f0f7f2] p-4">
    <x-page-loader />
    <div class="w-full max-w-lg">
        {{-- Logo --}}
        <div class="text-center mb-6">
            <span class="brand-mark brand-mark--auth inline-flex items-center justify-center rounded-2xl bg-[#176b45] text-white mb-3 shadow-lg" style="width:3.5rem;height:3.5rem;">
                <svg width="28" height="28" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 4C10 4 5 8 5 15c0 2.2 1.8 4 4 4 7 0 11-6 11-15ZM4 20c3-4 6-6 10-8"/></svg>
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

            @if($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700 flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <p class="font-semibold mb-1">Terdapat kesalahan dalam formulir:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="space-y-5" x-init="if (kelompokTaniId && !kelompokTaniList.some((k) => String(k.id) === String(kelompokTaniId))) cariKelompokTani()" x-data="{
                showPassword: false,
                showConfirm: false,
                role: {{ Js::from(old('role', count($roles) === 1 ? array_key_first($roles) : '')) }},
                kelompokTaniList: {{ Js::from($kelompokTaniList ?? []) }},
                kelompokTaniId: {{ Js::from(old('kelompok_tani_id')) }},
                kelompokTaniQuery: '',
                showPassword: false,
                showConfirm: false,
                role: {{ Js::from(old('role', count($roles) === 1 ? array_key_first($roles) : '')) }},
                kelompokTaniList: {{ Js::from($kelompokTaniList ?? []) }},
                kelompokTaniId: {{ Js::from(old('kelompok_tani_id')) }},
                kelompokTaniQuery: '',
                kelompokTaniLoading: false,
                kelompokTaniSearchError: false,
                async cariKelompokTani() {
                    const query = this.kelompokTaniQuery.trim();
                    this.kelompokTaniLoading = true;
                    this.kelompokTaniSearchError = false;
                    try {
                        const params = new URLSearchParams({ q: query });
                        if (this.kelompokTaniId) params.set('selected', this.kelompokTaniId);
                        const response = await fetch('{{ route('register.kelompok-tani') }}?' + params.toString(), {
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (! response.ok) throw new Error('reference-search-failed');
                        const payload = await response.json();
                        this.kelompokTaniList = Array.isArray(payload.data) ? payload.data : [];
                    } catch (error) {
                        this.kelompokTaniList = [];
                        this.kelompokTaniSearchError = true;
                    } finally {
                        this.kelompokTaniLoading = false;
                    }
                },
                get kelompokTaniTerpilih() {
                    return this.kelompokTaniList.find((k) => String(k.id) === String(this.kelompokTaniId)) || null;
                },
                get kelompokTaniHasil() {
                    const query = this.kelompokTaniQuery.trim().toLowerCase();
                    if (! query) return this.kelompokTaniList;
                    return this.kelompokTaniList.filter((k) => [k.nama, k.kode, k.kabupaten, k.kecamatan, k.kelurahan, k.desa]
                        .filter(Boolean)
                        .some((value) => String(value).toLowerCase().includes(query)));
                }
            }">
                @csrf

                {{-- Role (hanya untuk Admin yang membuat akun; publik selalu Poktan) --}}
                @if(count($roles) > 1)
                <div>
                    <label for="role" class="block text-sm font-semibold text-[#314239] mb-1.5">Role <span class="text-red-500">*</span></label>
                    <select id="role" name="role" required x-model="role"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition bg-white @error('role') border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 @else border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 @enderror">
                        <option value="">— Pilih Role —</option>
                        @foreach($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                @endif

                {{-- Identitas manual (hanya untuk role non-Poktan oleh Admin) --}}
                @if(count($roles) > 1)
                <div x-show="role !== 'poktan'" x-cloak class="space-y-5">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-[#314239] mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name') }}"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition @error('name') border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 @else border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 @enderror"
                               placeholder="Contoh: Budi Santoso">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-[#314239] mb-1.5">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email') }}"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition @error('email') border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 @else border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 @enderror"
                               placeholder="Masukkan email">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-semibold text-[#314239] mb-1.5">No. HP/WhatsApp <span class="text-red-500">*</span></label>
                        <input type="tel" id="phone" name="phone"
                               value="{{ old('phone') }}"
                               class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition @error('phone') border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 @else border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 @enderror"
                               placeholder="081234567890">
                        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                @endif

                {{-- Password + Konfirmasi (2 kolom) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-semibold text-[#314239] mb-1.5">Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                   class="w-full rounded-lg border px-3.5 py-2.5 pr-10 text-sm outline-none transition @error('password') border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 @else border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 @enderror"
                                   placeholder="Min. 8 karakter (besar, kecil, angka, simbol)">
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9aa59e] hover:text-[#176b45]">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-[#314239] mb-1.5">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                                   class="w-full rounded-lg border px-3.5 py-2.5 pr-10 text-sm outline-none transition @error('password') border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 @else border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 @enderror"
                                   placeholder="Ulangi password">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#9aa59e] hover:text-[#176b45]">
                                <svg x-show="!showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg x-show="showConfirm" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                            </button>
                        </div>
                        @error('password')<p class="mt-1 text-xs text-red-600">&nbsp;</p>@enderror
                    </div>
                </div>

                {{-- Kelompok Tani (wajib untuk Poktan, dari referensi Disbun) --}}
                <div x-show="role === 'poktan'" x-cloak>
                    <label for="kelompok-tani-search" class="block text-sm font-semibold text-[#314239] mb-1.5">Kelompok Tani <span class="text-red-500">*</span></label>
                    <p class="mb-2 text-xs text-[#77847c]">Pilih kelompok tani Anda sesuai data referensi Disbun. Akun diverifikasi Admin berdasarkan kode ini.</p>
                    <input type="search" id="kelompok-tani-search" x-model="kelompokTaniQuery"
                           placeholder="Ketik untuk menyaring dari seluruh data (A–Z)..."
                           class="mb-2 w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15">
                    <select id="kelompok_tani_id" name="kelompok_tani_id" x-model="kelompokTaniId" :required="role === 'poktan'"
                            class="w-full rounded-lg border px-3.5 py-2.5 text-sm outline-none transition bg-white @error('kelompok_tani_id') border-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-100 @else border-[#d6e0d9] focus:border-[#176b45] focus:ring-2 focus:ring-[#176b45]/15 @enderror">
                        <option value="">— Pilih Kelompok Tani —</option>
                        <option value="" disabled x-show="kelompokTaniLoading">Memuat opsi...</option>
                        <template x-for="k in kelompokTaniHasil" :key="k.id">
                            <option :value="String(k.id)" x-text="[k.nama, k.kode, [k.kecamatan, k.kabupaten].filter(Boolean).join(' · ')].filter(Boolean).join(' — ')"></option>
                        </template>
                    </select>
                    <p x-show="kelompokTaniLoading" class="mt-1 text-xs text-[#77847c]">Mencari data kelompok tani...</p>
                    <p x-show="!kelompokTaniLoading && kelompokTaniSearchError" class="mt-1 text-xs text-red-600">Data kelompok tani tidak tersedia. Silakan coba lagi.</p>
                    <p x-show="!kelompokTaniLoading && !kelompokTaniSearchError" class="mt-1 text-xs text-[#77847c]">Menampilkan <span class="font-semibold" x-text="kelompokTaniHasil.length"></span> data.</p>
                    <p x-show="!kelompokTaniLoading && !kelompokTaniSearchError && kelompokTaniHasil.length === 0" class="mt-1 text-xs text-amber-700">Data tidak tersedia untuk penyaringan ini.</p>
                    @error('kelompok_tani_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    <template x-if="kelompokTaniTerpilih">
                        <div class="mt-2 rounded-lg bg-[#f3f8f4] p-3 text-xs text-[#526159]">
                            <p class="font-semibold text-[#173b29]" x-text="kelompokTaniTerpilih.nama"></p>
                            <p class="mt-0.5">Kode: <span class="font-semibold text-[#176b45]" x-text="kelompokTaniTerpilih.kode"></span></p>
                            <p x-show="kelompokTaniTerpilih.jenis_komoditi">Komoditas: <span x-text="kelompokTaniTerpilih.jenis_komoditi"></span></p>
                            <p x-show="kelompokTaniTerpilih.kecamatan || kelompokTaniTerpilih.kabupaten">Wilayah: <span x-text="[kelompokTaniTerpilih.kecamatan, kelompokTaniTerpilih.kabupaten].filter(Boolean).join(', ')"></span></p>
                        </div>
                    </template>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full rounded-lg bg-[#176b45] px-4 py-3 text-sm font-bold text-white hover:bg-[#115a39] transition-colors shadow-sm">
                    Daftar Sekarang
                </button>
            </form>

            {{-- Link ke login --}}
            <p class="mt-6 text-center text-sm text-[#77847c]">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-[#176b45] hover:underline">Masuk di sini</a>
            </p>
        </div>

        <p class="text-center text-xs text-[#a0aba4] mt-6">
            &copy; 2026 SIPAKARBUN — Dinas Perkebunan
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
