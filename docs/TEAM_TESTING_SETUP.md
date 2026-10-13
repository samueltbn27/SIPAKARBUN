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

Gunakan database demo terisolasi. Panduan membuat SQLite baru tanpa mengubah
`.env` atau database aktif tersedia di
[panduan demo status terpadu](/Users/hoesnicodes/Developer/SIPAKARBUN/docs/DEMO_STATUS_TERPADU.md).

Setelah konfigurasi koneksi mengarah ke database demo kosong/khusus fixture V2:

```bash
php artisan migrate
php artisan db:seed --class=SipakarbunDemoSeeder
php artisan storage:link
npm run build
```

`SipakarbunDemoSeeder` tidak dipanggil oleh `DatabaseSeeder`. Seeder hanya
menerima environment `local`/`testing` dan menolak database yang bercampur
akun, transaksi, atau referensi di luar fixture V2. Tidak ada reset otomatis.
Jangan memakai `migrate:fresh` untuk mengatasi penolakan tersebut.

Seeder memasang fixture komoditas offline, Knowledge UAT, akun tester,
ilustrasi lokal, dan 20 skenario monitoring. Pengulangan tidak mengubah
password, tanggal, laporan/progress, keputusan, atau arsip yang sudah ada.
Gunakan database terisolasi baru bila ingin memulai simulasi dari awal.

Jalankan aplikasi dengan koneksi database demo yang sudah dipilih:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Buka halaman login pada port tersebut. Jika port 8000 terpakai, gunakan 8001.

## 5. Akun tester

| Role | Email | Password awal contoh |
| --- | --- | --- |
| Admin | `admin.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` |
| Operator UPTD | `operator.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` |
| POPT | `popt.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` |
| Poktan | `poktan.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` |
| Pimpinan | `pimpinan.tester@sipakarbun.local` | `SIPAKARBUN-Tester-2026!` |

Password berasal dari `SIPAKARBUN_DEMO_PASSWORD` saat akun pertama dibuat;
seeder tidak mengganti password akun yang sudah ada. Akun tambahan:

- `popt.dua.tester@sipakarbun.local`
- `poktan.kopi-garut.tester@sipakarbun.local`
- `poktan.kakao.tester@sipakarbun.local`
- `poktan.cengkeh.tester@sipakarbun.local`
- `poktan.kelapa.tester@sipakarbun.local`

Lima akun Poktan masing-masing memiliki kelompok sendiri. Tidak ada role
`pakar`; Admin tidak tersedia pada pendaftaran biasa.

## 6. Study case demo

Kode `PM-DEMO-V2-NNN` stabil untuk identifikasi skenario; tanggal historisnya
relatif terhadap waktu seed pertama. Kasus yang telah diterima menggunakan
kode `KS-DEMO-V2-NNN`. Namespace identitas fixture tetap dipertahankan.
Database hasil verifikasi empat status ada pada `storage/app/demo-status-v3/validated-20261013.sqlite`;
database V2 sebelumnya tidak ditulis ulang. Detail 20 skenario terdapat pada panduan demo.

| Nomor | Kondisi | Status utama |
| --- | --- | --- |
| 001–003 | Baru diajukan / masih direview, belum ada keputusan atau penugasan | Menunggu Review |
| 004–006 | Keputusan penolakan dengan alasan | Ditolak |
| 007–008 | Ditugaskan / diterima POPT, belum bekerja | Dalam Penanganan |
| 009–010 | Belum bekerja dan target terlewati | Dalam Penanganan + indikator terlambat |
| 011–012 | Progress sesuai target / melewati target | Dalam Penanganan |
| 013 | Perpanjangan pending, target lama tetap | Dalam Penanganan + indikator terlambat |
| 014 | Perpanjangan approved, target baru berlaku | Dalam Penanganan |
| 015 | Perpanjangan rejected, target lama tetap | Dalam Penanganan + indikator terlambat |
| 016 | Pemantauan masih sesuai target | Dalam Penanganan |
| 017–020 | Laporan akhir lengkap, dua PNG setiap laporan | Selesai |

KPI awal: total permohonan 20, menunggu review 3, dalam penanganan 10, selesai 4, melewati target 5.
Jumlah kategori utama adalah 3 + 3 + 10 + 4 = 20. Keterlambatan adalah
subset kasus aktif dan tidak ditambahkan lagi ke total.

Kelompok menggunakan nama `DEMO FIKTIF ...` dan identitas bernamespace
`DEMO-SIPAKARBUN-V2-...`, tanpa memakai ID asli kelompok Disbun. Nilai source
`disbun` dipertahankan hanya untuk kompatibilitas kontrak adapter lokal;
fixture tidak berasal dari sinkronisasi API dan bukan data resmi Disbun.
Koordinat kasus disimpan terpisah dan berbeda dari titik administratif kelompok.

Mengarsipkan `KS-DEMO-V2-017` tidak akan dipulihkan oleh seeding ulang.
Gunakan database demo baru untuk mengulang UAT hapus/arsip dari awal.

## 7. Study Case UAT end-to-end

1. Login Poktan utama. Kelompok yang terhubung adalah DEMO FIKTIF Kopi
   Sumedang. Riwayatnya memiliki contoh dari keempat status utama.
2. Jalankan Diagnosis kopi dengan gejala fixture lalu ajukan penanganan.
   Konfirmasi lokasi kasus aktual pada peta.
3. Login Operator, review permohonan. Penolakan wajib disertai alasan.
   Persetujuan wajib sekaligus memilih POPT aktif dan target masa depan;
   keputusan, kasus, penugasan, dan target tersimpan dalam satu transaksi.
4. Login POPT: Terima Penugasan → tinjau → Mulai Penanganan → Tambah Progress.
   Status sudah Dalam Penanganan sejak persetujuan dan penugasan Operator.
   Terima tugas dan mulai kerja mencatat aktivitas aktual tanpa menambah status utama.
5. Ajukan perpanjangan jika perlu. Operator memutuskan; hanya persetujuan
   yang mengubah target aktif. Pekerjaan tetap berjalan selama menunggu.
6. Selesaikan melalui laporan akhir lengkap dan minimal satu foto valid.
7. Login Poktan untuk membaca progress/laporan miliknya dan pastikan data
   Poktan lain tidak dapat diakses.
8. Login Pimpinan: buka WebGIS dan Laporan Monitoring; bandingkan status,
   KPI, marker, grafik, serta filter. Pimpinan tidak mempunyai aksi perubahan.

UAT khusus waktu dan histori:

- `KS-DEMO-V2-009`: Dalam Penanganan dan Melewati Target Waktu.
- `KS-DEMO-V2-012`: Dalam Penanganan dan Melewati Target Waktu.
- `KS-DEMO-V2-013`/`014`/`015`: pending/approved/rejected perpanjangan.
- `KS-DEMO-V2-017`: selesai sebelum target; `018`: selesai sesudah target.
  Keduanya tidak dihitung aktif/terlambat setelah selesai.
- Semua laporan akhir berisi dua ilustrasi lokal berlabel DATA DEMO,
  bukan foto kasus nyata. Data simulasi bukan rekomendasi budidaya resmi.

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
