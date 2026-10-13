# Data Historis dan Demo Empat Status SIPAKARBUN

Verifikasi: 13 Oktober 2026. Fixture: **DEMO V2** dengan identitas tetap; artefak verifikasi empat status: **demo-status-v3**. Seluruh kasus fiktif dan bukan laporan resmi Disbun.

## 1. Audit database dan keputusan pelaksanaan

Database aktif berada di `/Users/hoesnicodes/Developer/SIPAKARBUN/database/database.sqlite`, driver SQLite, environment `local`. Audit menemukan 15 akun, 18 diagnosis, 14 permohonan, 11 kasus, serta 5.658 referensi Kelompok Tani. Ada akun yang terhubung ke kelompok hasil integrasi; database ini tidak dapat dianggap seluruhnya dummy.

**Database aktif tidak di-reset dan tidak dijalankan seeder baru. `.env` tidak diubah. Tidak ada `migrate:fresh`, penghapusan migration, atau migration schema baru.** Ini mengikuti ketentuan tugas bahwa reset destruktif memerlukan database dummy yang terverifikasi dan persetujuan eksplisit.

Database demo sebelumnya `storage/app/demo-status-v2/validated-20261013.sqlite` diaudit baca saja. Keempat kasus `007`–`010` memiliki persetujuan, penugasan POPT, dan target valid. Dengan kode terbaru, distribusinya menjadi 3/3/10/4 tanpa perubahan baris, tanggal, atau waktu mulai.

Untuk menguji seeder terbaru, dibuat file baru dalam mode eksklusif, diverifikasi nol tabel, lalu dijalankan migration biasa dan seeder pada environment `testing`:

`/Users/hoesnicodes/Developer/SIPAKARBUN/storage/app/demo-status-v3/validated-20261013.sqlite`

File baru ini sudah berisi dataset siap dipakai. Aplikasi pada koneksi aktif tidak dialihkan ke demo dan `.env` tetap sama.

Perbandingan fingerprint **seluruh 35 tabel database aktif identik** sebelum/sesudah revisi ini, termasuk akun/password, 5.658 referensi Kelompok Tani, diagnosis, keputusan, progress, dan laporan. Seluruh 35 tabel demo V2 juga identik. Tidak ada reset, backfill, atau migrasi data terhadap keduanya.

Backup untuk reset tidak diperlukan karena tidak dilakukan perubahan data aktif. Fingerprint audit digunakan untuk memeriksa integritas; ini bukan pengganti backup bila nanti melakukan reset yang telah disetujui.

## 2. Seeder dan file yang diperbarui

| File | Perubahan |
| --- | --- |
| [SipakarbunDemoSeeder](/Users/hoesnicodes/Developer/SIPAKARBUN/database/seeders/SipakarbunDemoSeeder.php) | Dataset offline V2, pemeriksaan database campuran sebelum menulis, transaksi, tanggal historis, role/ownership, dan preservasi pengulangan. |
| [Definisi 20 skenario](/Users/hoesnicodes/Developer/SIPAKARBUN/database/seeders/data/sipakarbun-status-cases.php) | Fixture sebelumnya: skenario beridentitas stabil dengan offset tanggal relatif, dipertahankan. |
| [Generator PNG fixture](/Users/hoesnicodes/Developer/SIPAKARBUN/database/seeders/assets/laporan-akhir/generate-demo-placeholders.php) | Fixture sebelumnya: menggambar ilustrasi lokal deterministik tanpa foto/font eksternal. GD hanya diperlukan untuk membuat ulang fixture. |
| [Ilustrasi awal](/Users/hoesnicodes/Developer/SIPAKARBUN/database/seeders/assets/laporan-akhir/demo-status-awal.png) | Fixture sebelumnya: PNG 920 × 600 dengan label DATA DEMO. |
| [Ilustrasi akhir](/Users/hoesnicodes/Developer/SIPAKARBUN/database/seeders/assets/laporan-akhir/demo-status-akhir.png) | Fixture sebelumnya: PNG evaluasi berlabel placeholder, bukan foto tanaman nyata. |
| [Test seeder](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/SipakarbunDemoSeederTest.php) | 16 test, termasuk dua tanggal demo alternatif, keselamatan database, login/password, histori, evidence, serta konsistensi monitoring. |
| [README](/Users/hoesnicodes/Developer/SIPAKARBUN/README.md) | Status, jumlah, password awal, dan perilaku seeder terbaru. |
| [Panduan pengujian tim](/Users/hoesnicodes/Developer/SIPAKARBUN/docs/TEAM_TESTING_SETUP.md) | Akun tambahan, skenario baru, UAT empat status, dan strategi database aman. |
| [Laporan/panduan ini](/Users/hoesnicodes/Developer/SIPAKARBUN/docs/DEMO_STATUS_TERPADU.md) | Audit, hasil aktual, distribusi, dan perintah. |

Seeder menolak `production`, `staging`, dan environment selain `local`/`testing`. Database yang berisi akun, transaksi, referensi integrasi, atau dataset lama di luar fixture V2 ditolak sebelum akun/file/data diubah.

Password kelima akun utama tetap dipertahankan jika akun sudah ada. `SIPAKARBUN_DEMO_PASSWORD` hanya digunakan saat membuat akun baru. Pengulangan mempertahankan tanggal, keputusan, target, progress, laporan akhir immutable, serta kasus arsip; tidak mengembalikan kasus yang dihapus dan tidak memaksa kasus kembali ke status awal.

Knowledge UAT memakai seeder yang sudah tersedia. Nilai hasil diagnosis dihitung memakai `CertaintyFactorService` yang sudah ada berdasarkan aturan fixture; formula CF, RBAC, serta API Knowledge/Disbun dipertahankan. Workflow persetujuan mengikuti revisi empat status pada laporan implementasi.

## 3. Jumlah data yang benar-benar dibuat

| Data | Jumlah |
| --- | ---: |
| Akun, mencakup lima role | 10 |
| Kelompok Tani dummy, masing-masing memiliki akun Poktan | 5 |
| Referensi komoditas offline | 41 |
| Komoditas yang dipakai skenario | 4 |
| Penyakit Knowledge UAT | 4 |
| Gejala / aturan CF simulasi | 24 / 24 |
| Diagnosis / gejala diagnosis / hasil diagnosis | 20 / 40 / 20 |
| Permohonan / keputusan Operator | 20 / 17 |
| Kasus diterima / penugasan POPT | 14 / 14 |
| Petugas POPT berbeda | 2 |
| Catatan progress | 24 |
| Pengajuan perpanjangan | 3 |
| Laporan akhir / file bukti PNG | 4 / 8 |

Lima akun utama tetap memakai email `admin.tester`, `operator.tester`, `popt.tester`, `poktan.tester`, dan `pimpinan.tester` pada domain `sipakarbun.local`. Tambahan akun:

- `popt.dua.tester@sipakarbun.local`
- `poktan.kopi-garut.tester@sipakarbun.local`
- `poktan.kakao.tester@sipakarbun.local`
- `poktan.cengkeh.tester@sipakarbun.local`
- `poktan.kelapa.tester@sipakarbun.local`

Seluruh kelompok bernama **DEMO FIKTIF ...**. Identitasnya memakai namespace `DEMO-SIPAKARBUN-V2-...`, bukan ID kelompok Disbun yang nyata. `source=disbun` hanya mempertahankan kompatibilitas kontrak adapter lokal yang sudah ada; tidak menunjukkan bahwa fixture disinkronkan atau disahkan Disbun. `disbun_record_id` komoditas tetap NULL. Seeder tidak menghubungi API Disbun.

## 4. Distribusi empat status dan skenario historis

| Status utama | Entri | Kasus penanganan yang sudah terbentuk |
| --- | ---: | ---: |
| Menunggu Review | 3 | 0 |
| Ditolak | 3 | 0 |
| Dalam Penanganan | 10 | 10 |
| Selesai | 4 | 4 |
| **Total** | **20** | **14** |

KPI Total Permohonan mengikuti definisi aplikasi: satu entri per permohonan, dilengkapi kasus setelah diterima. Dengan demikian, 20 adalah jumlah entri monitoring; jumlah baris kasus penanganan yang diterima adalah 14. Tidak ada penghitungan ganda.

Kode permohonan `PM-DEMO-V2-NNN` dan kode kasus `KS-DEMO-V2-NNN` menggunakan nomor berikut:

| Nomor | Skenario | Usia permohonan saat seed pertama |
| --- | --- | ---: |
| 001 | Kopi baru diajukan | 1 hari |
| 002 | Kakao sedang direview, belum diputuskan | 3 hari |
| 003 | Kelapa menunggu keputusan | 21 hari |
| 004 | Kopi ditolak karena bukti belum memadai | 10 hari |
| 005 | Cengkeh ditolak karena lokasi belum sesuai | 45 hari |
| 006 | Kakao ditolak karena cakupan layanan | 90 hari |
| 007 | Kopi ditugaskan, belum diterima POPT | 7 hari |
| 008 | Kelapa diterima POPT, belum mulai bekerja | 12 hari |
| 009 | Kakao belum dimulai dan melewati target | 20 hari |
| 010 | Tinjauan cengkeh melewati target | 10 hari |
| 011 | Kopi sedang ditangani, target belum terlewati | 14 hari |
| 012 | Kakao sedang ditangani, target terlewati | 35 hari |
| 013 | Cengkeh dengan perpanjangan pending | 21 hari |
| 014 | Kopi Garut dengan perpanjangan approved | 30 hari |
| 015 | Kelapa dengan perpanjangan rejected | 24 hari |
| 016 | Pemantauan kakao, target belum terlewati | 10 hari |
| 017 | Kopi selesai sebelum target | 60 hari |
| 018 | Kelapa selesai sesudah target | 100 hari |
| 019 | Cengkeh selesai sebelum target | 150 hari |
| 020 | Kopi Garut selesai sesudah target | 210 hari |

Tanggal aktual dihitung dari waktu pertama seeding. Pada database baru, keputusan diterima dan assignment memakai waktu yang sama; penerimaan POPT dan waktu mulai tetap terpisah. Kasus `007`–`010` tidak memiliki progress atau event mulai kerja. Seeder tidak menulis ulang tanggal fixture yang sudah tersedia. Urutan diagnosis, permohonan, review, keputusan, assignment, aktivitas POPT, laporan, foto, dan selesai telah diuji. Kode tidak berpura-pura memuat tanggal historis tetap.

Tidak dibuat kasus baru berstatus Ditunda atau contoh selesai tanpa laporan. Pengujian kompatibilitas data legacy tetap berada pada suite status/workflow yang sudah tersedia.

## 5. Keterlambatan dan KPI

KPI awal pada database baru: **total 20, menunggu review 3, dalam penanganan 10, selesai 4, melewati target 5**.

Lima kasus melewati target adalah `009`, `010`, `012`, `013`, dan `015`: seluruhnya Dalam Penanganan. Keterlambatan tetap indikator tambahan, bukan status utama tambahan.

Kasus selesai `018` dan `020` menyimpan waktu penyelesaian sesudah target sebagai histori. Keduanya tidak dihitung aktif atau terlambat setelah selesai. Kasus `017` dan `019` selesai sebelum target.

Pengulangan seeder tidak menggeser target agar histori tidak berubah. Bila database dibiarkan berjalan beberapa hari, indikator waktu wajar berkembang sesuai waktu nyata. Gunakan database baru bila ingin memperoleh kembali kondisi awal demo 20 total / 3 review / 10 penanganan / 4 selesai / 5 terlambat; jangan menulis ulang histori untuk memaksa KPI tetap.

## 6. Perpanjangan

Terdapat satu pengajuan untuk masing-masing status:

- `013` **pending**: target lama telah terlewati; usulan baru belum berlaku dan penanganan tetap berjalan.
- `014` **approved**: target sebelumnya telah lewat, tetapi target terbaru yang disetujui masih di masa depan; indikator tidak terlambat.
- `015` **rejected**: keputusan Operator dan catatan tersimpan; target lama tetap berlaku sehingga indikator tetap terlambat.

Identitas pemohon perpanjangan sesuai assignment POPT. Tanggal pengajuan mendahului keputusan; target usulan sesudah target lama. Catatan review internal tetap mengikuti kebijakan privasi aplikasi.

## 7. Kasus selesai, laporan, dan foto

Keempat kasus selesai memiliki progress, laporan akhir lengkap, waktu penyelesaian yang valid, dan **dua PNG nyata per laporan**. Tidak ada kasus selesai tanpa laporan akhir.

File disimpan pada `laporan-akhir/demo-status-v2/KS-DEMO-V2-NNN/bukti-awal.png` dan `bukti-akhir.png` di disk `public`. Semua delapan file ditemukan melalui symlink `public/storage`, mempunyai MIME PNG, resolusi 920 × 600, serta lolos validasi evidence aplikasi termasuk batas ukuran 5 MB.

Gambar berupa ilustrasi lokal bertuliskan **DATA DEMO SIPAKARBUN** dan **BUKAN FOTO KASUS NYATA**. Tidak ada gambar eksternal yang diunduh. Generator PHP/GD disediakan untuk reproduksi; seeder cukup menyalin PNG yang tersedia dan tidak memerlukan GD saat dijalankan.

URL dibuat lewat mekanisme `PublicStorageUrl` yang sudah digunakan aplikasi. Test HTTP memastikan detail Poktan memuat URL tersebut. Tidak diklaim ada UAT browser visual atau pengujian HTTP static-file melalui browser pada tugas ini.

## 8. Integritas relasi dan pengulangan

- `PRAGMA foreign_key_check` pada SQLite hasil seeding mengembalikan **nol pelanggaran**.
- Relasi tanpa foreign key fisik juga diuji: pemilik diagnosis/permohonan, kelompok akun, komoditas kelompok, penyakit/gejala, koordinat, actor progress, petugas assignment, reviewer, laporan, dan pengunggah foto.
- Semua transisi histori sesuai state machine yang sudah ada; tanggal tidak terbalik dan penyelesaian tidak berada di masa depan.
- Kelima akun Poktan masing-masing memiliki kelompok sendiri; Poktan utama memiliki lima permohonan dengan contoh keempat status. Akses lintas pemilik ditolak. Dua POPT mempunyai assignment berbeda; aksi pada tugas petugas lain ditolak.
- Pimpinan dapat membaca monitoring global tetapi aksi mulai/hapus tetap ditolak.
- Pemuatan resource hasil seed melalui normalizer/filter/statistik JavaScript menghasilkan KPI yang sama: 20 total / 3 review / 10 penanganan / 4 selesai / 5 terlambat, empat kelompok 3/3/10/4, empat komoditas, empat penyakit, lima wilayah, dan 20 titik aktual yang valid.
- Seeder CLI dijalankan dua kali pada database terisolasi. Fingerprint **seluruh 35 tabel identik** sebelum/sesudah pengulangan: tidak ada duplikasi atau perubahan data.
- Test tambahan mengubah password dan mengarsipkan satu kasus, lalu mengulang seed 30 hari kemudian. Password, tanggal, histori, laporan, serta arsip tetap sama.

Artefak lokal hasil verifikasi, di luar Git:

- [SQLite demo](/Users/hoesnicodes/Developer/SIPAKARBUN/storage/app/demo-status-v3/validated-20261013.sqlite)
- [Resource monitoring hasil seed](/Users/hoesnicodes/Developer/SIPAKARBUN/storage/app/demo-status-v3/monitoring-validated.json)
- [Ringkasan pemeriksaan integritas](/Users/hoesnicodes/Developer/SIPAKARBUN/storage/app/demo-status-v3/integrity-validated.json)
- [Hasil provider/KPI/grafik JavaScript](/Users/hoesnicodes/Developer/SIPAKARBUN/storage/app/demo-status-v3/frontend-validated.json)

## 9. Hasil pengujian

| Pemeriksaan | Hasil |
| --- | --- |
| Suite Laravel `php artisan test --compact` | **595 test lulus, 4.411 assertion** |
| Test khusus seeder | **16 test** tercakup pada suite Laravel |
| `npm run test:provider` | **52 test JavaScript lulus** |
| Resource hasil seed → provider/filter/KPI/grafik JavaScript | **PASS**, data aktual database terisolasi |
| `npm run build` | **PASS**, 83 module |
| `php artisan view:clear` dan `php artisan view:cache` | **PASS** |
| Pint untuk file PHP revisi ini | **PASS** |
| `git diff --check` | **PASS** |
| Seluruh migration + seed pertama pada SQLite baru | **PASS** |
| Seed kedua / fingerprint 35 tabel / foreign key / file bukti | **PASS** |

Test PHP menggunakan SQLite `:memory:` dan fake storage. Eksekusi CLI tambahan memakai SQLite terisolasi yang disimpan sebagai artefak. Workflow teknis lama tetap diuji, ditambah approval atomik, rollback, stale request, konkurensi dua proses, serta keterbacaan tanggal historis tidak valid. Tidak ada test yang dihapus atau dinonaktifkan.

## 10. Perintah menjalankan dataset

### Menggunakan database demo yang sudah dibuat

Untuk membuka aplikasi dengan SQLite hasil verifikasi pada port berbeda, tanpa mengubah `.env` maupun aplikasi yang berjalan di port 8000:

```bash
cd /Users/hoesnicodes/Developer/SIPAKARBUN
APP_ENV=local \
DB_CONNECTION=sqlite \
DB_DATABASE="$PWD/storage/app/demo-status-v3/validated-20261013.sqlite" \
DB_URL= \
APP_CONFIG_CACHE="$PWD/storage/app/demo-status-v3/unused-config.php" \
CACHE_STORE=array \
SESSION_DRIVER=cookie \
KNOWLEDGE_API_BASE_URL= \
SHARED_API_BASE_URL= \
php artisan serve --host=127.0.0.1 --port=8001
```

Buka [login demo](http://127.0.0.1:8001/login) setelah server berjalan. Perintah serve ini disediakan untuk pengguna; server baru belum dijalankan oleh tugas ini. Session cookie mempertahankan login antar-request tanpa menulis sesi ke database aktif. `APP_ENV=local` memakai adapter referensi lokal, dan URL Knowledge kosong memakai Knowledge monolith.

### Membuat database demo baru

Gunakan nama file baru. Pembukaan mode `xb` menolak menimpa file yang sudah ada:

```bash
cd /Users/hoesnicodes/Developer/SIPAKARBUN
mkdir -p storage/app/demo-status-v3
python3 - <<'PY'
from pathlib import Path
with Path('storage/app/demo-status-v3/demo-baru.sqlite').open('xb'):
    pass
PY

demo_status() {
    APP_ENV=testing \
    DB_CONNECTION=sqlite \
    DB_DATABASE="$PWD/storage/app/demo-status-v3/demo-baru.sqlite" \
    DB_URL= \
    APP_CONFIG_CACHE="$PWD/storage/app/demo-status-v3/unused-config.php" \
    CACHE_STORE=array \
    SESSION_DRIVER=array "$@"
}

demo_status php artisan migrate
demo_status php artisan db:seed --class=SipakarbunDemoSeeder
```

`SIPAKARBUN_DEMO_PASSWORD` harus sudah dikonfigurasi untuk akun baru. Nilainya tidak diubah oleh implementasi ini. Jangan mengganti password akun existing untuk menjalankan ulang seeder.

Apabila koneksi memang sudah diarahkan secara eksplisit ke database demo khusus, perintah seed dasarnya adalah `php artisan db:seed --class=SipakarbunDemoSeeder`. Jangan menjalankannya pada koneksi aktif campuran lalu memakai `migrate:fresh` untuk mengatasi penolakan guard.

Regenerasi fixture gambar, bila diperlukan: `php database/seeders/assets/laporan-akhir/generate-demo-placeholders.php`.

Tidak dilakukan commit, push, merge, atau deploy. Perubahan lokal dari pekerjaan sebelumnya tetap dipertahankan.
