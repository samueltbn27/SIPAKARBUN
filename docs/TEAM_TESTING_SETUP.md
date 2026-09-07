# SIPAKARBUN — Team Demo & Testing Setup

Panduan ini menyiapkan database demo lokal yang konsisten untuk UAT lima role.
Dataset demo tidak dikirim ke production dan tidak membutuhkan akses ke API
Disbun untuk setup awal.

> **Peringatan:** semua credential pada dokumen ini hanya untuk local/demo.
> Jangan gunakan pada production atau data nyata.

## 1. Clone dan checkout

```bash
git clone https://github.com/samueltbn27/SIPAKARBUN.git
cd SIPAKARBUN
git checkout mahasiswa-3-webgis
```

Branch tersebut harus sudah tersedia di GitHub. Setelah branch digabung ke
`main`, checkout branch khusus tidak lagi diperlukan untuk setup normal.

## 2. Prasyarat

Project ini mendeklarasikan PHP `^8.2` dan Laravel 12 pada `composer.json`.
`package.json` memakai Vite 7, yang mendeklarasikan Node `^20.19.0 || >=22.12.0`.
Siapkan Composer, npm, dan PHP extension PDO SQLite karena konfigurasi demo
menggunakan SQLite.

## 3. Install dan konfigurasi lokal

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
npm ci
```

Pastikan `.env` memuat konfigurasi lokal berikut. Nilai password demo hanya
digunakan oleh seeder eksplisit dan akan disimpan sebagai hash.

```dotenv
APP_ENV=local
DB_CONNECTION=sqlite
KNOWLEDGE_API_BASE_URL=
SIPAKARBUN_DEMO_PASSWORD=SIPAKARBUN-Tester-2026!
```

`KNOWLEDGE_API_BASE_URL` yang kosong memilih adapter Knowledge lokal untuk
monolith development. Sinkronisasi Disbun tidak diperlukan untuk demo dasar.

## 4. Migrasi, seed demo, storage, dan build

```bash
php artisan migrate
php artisan db:seed --class=SipakarbunDemoSeeder
php artisan storage:link
npm run build
```

`SipakarbunDemoSeeder` sengaja tidak dipanggil oleh `DatabaseSeeder`. Seeder
menolak environment `production`, dapat dijalankan berulang kali, dan tidak
menghapus data existing. Ia memasang reference fixture offline, Knowledge UAT,
akun tester, gambar lokal, serta study case monitoring.

Jalankan aplikasi:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Buka <http://127.0.0.1:8000/login>. Untuk hot reload, jalankan `npm run dev` di
terminal kedua. Jika port 8000 terpakai, gunakan `php artisan serve --port=8001`.

## 5. Akun tester

| Role | Email | Password | UAT utama |
|---|---|---|---|
| Admin | `admin.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` | WebGIS, arsip kasus selesai, Knowledge |
| Operator UPTD | `operator.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` | Review, terima/tolak, assign POPT, publish |
| POPT | `popt.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` | Penugasan, status teknis, Draft Knowledge |
| Poktan | `poktan.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` | Diagnosis dan permohonan |
| Pimpinan | `pimpinan.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` | WebGIS dan monitoring read-only |

Tidak ada role `pakar`. Admin demo dibuat hanya oleh demo seeder; role admin
tetap tidak tersedia pada form register/user provisioning biasa.

## 6. Study case demo

Kode stabil berikut dibuat idempotent oleh seeder. Semua koordinat kasus
berbeda dari koordinat reference Poktan agar kontrak lokasi kasus dapat diuji.

| Scenario | Request | Case | Status | Assigned POPT | Tujuan |
|---|---|---|---|---|---|
| Accepted / unassigned | `PM-20260101-9001` | `KS-20260101-9001` | diterima / diterima | — | Operator assignment UAT |
| Assigned | `PM-20260101-9002` | `KS-20260101-9002` | diterima / ditugaskan | POPT Demo | Penugasan Saya |
| In progress | `PM-20260101-9003` | `KS-20260101-9003` | diterima / dalam_pelaksanaan | POPT Demo | Monitoring aktif |
| Completed | `PM-20260101-9004` | `KS-20260101-9004` | diterima / selesai | POPT Demo | History, WebGIS, delete UAT |
| Rejected | `PM-20260101-9005` | — | ditolak | — | Keputusan Operator |
| Ditunda | `PM-20260101-9006` | `KS-20260101-9006` | diterima / ditunda | POPT Demo | KPI Ditunda |

Kasus `KS-20260101-9004` boleh dipakai untuk UAT Hapus Kasus karena sudah
selesai. Setelah dihapus/diarsipkan, jalankan ulang seeder demo untuk
memulihkannya melalui `withTrashed()->restore()`.

Reference penting:

- `ANUGRAH TANI`, external ID `5004`, KAB GARUT / Tarogong Kaler,
  reference `-7.1719511, 107.8224774`.
- `AROSTA`, external ID `5488`, KAB SUMEDANG / Wado,
  reference `-7.0029856, 108.1337070`.
- Lokasi kasus `KS-20260101-9001` adalah `-7.0250000, 107.5190000`, bukan
  koordinat reference Poktan.

## 7. Study Case UAT end-to-end

1. Login sebagai Poktan, buka **Diagnosis**, pilih `Kopi Arabika`, lalu pilih
   gejala `Muncul bercak kuning muda pada permukaan bawah daun` dan `Pada
   bercak terlihat serbuk seperti tepung berwarna jingga`. Hasil yang
   diharapkan memuat **Karat Daun Kopi**.
2. Pilih **Ajukan Penanganan**, pilih Kelompok Tani, pastikan reference
   kecamatan/kabupaten dan koordinat tampil, lalu klik titik aktual pada map
   picker sebelum submit.
3. Login Operator UPTD, buka permohonan, mulai review lalu terima. Checkpoint:
   `request_status=diterima`, `handling_status=diterima`, dan POPT belum ada.
4. Assign `POPT Demo`; status handling menjadi `ditugaskan`.
5. Login POPT dan lanjutkan status melalui `sedang_direview`,
   `siap_dieksekusi`, `dalam_pelaksanaan`, lalu `selesai`.
6. Login kembali sebagai Poktan untuk membaca status dan history milik sendiri.
7. Login Pimpinan atau Admin, buka `/webgis`, lalu periksa filter, marker,
   popup, drawer, KPI, dan grafik. Pastikan marker memakai koordinat kasus,
   bukan koordinat Poktan, serta tidak ada marker `[0,0]`.
8. Sebagai Admin, uji pembatalan modal Hapus Kasus lalu, bila diperlukan,
   hapus kasus selesai yang ditandai di atas.

### POPT sebagai kontributor Knowledge

Login POPT → Knowledge → **Tambah Draft** → isi data → simpan. Login Operator
→ buka Knowledge → tinjau dan **Publikasikan**. POPT dapat membaca record aktif,
tetapi tidak dapat mengubah record published, menghapus, atau mempublikasikan.

### Contoh CF

Mesin menggunakan `CF_gejala = CF_user × CF_pakar` dan kombinasi positif
`CFcombine = CF1 + CF2 × (1 - CF1)`. Nilai `CF_user` berasal dari keyakinan
tester. Nilai `CF_pakar` pada dataset demo hanya untuk simulasi/pengujian dan
bukan nilai yang telah divalidasi pakar lapangan.

## 8. Optional: sinkronisasi Disbun

```bash
php artisan disbun:sync-references
```

Perintah ini membutuhkan network dan API eksternal Disbun. Ia tidak diperlukan
untuk setup demo deterministik; browser tetap membaca endpoint internal
SIPAKARBUN, bukan API Disbun secara langsung.

## 9. Verifikasi

```bash
php artisan test
npm run test:provider
npm run build
php artisan view:clear
php artisan view:cache
```

Untuk simulasi database kosong, gunakan file SQLite sementara, bukan
`database/database.sqlite` milik development. Detail pengujian ini juga ada di
`tests/Feature/SipakarbunDemoSeederTest.php`.
