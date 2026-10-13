# Revisi Final Empat Status SIPAKARBUN

Verifikasi: 13 Oktober 2026. Repository: `/Users/hoesnicodes/Developer/SIPAKARBUN`.

Implementasi kode dan pemeriksaan otomatis selesai secara lokal. UAT visual browser dan rekonsiliasi data historis yang memerlukan bukti belum dilakukan. Tidak dilakukan commit, push, merge, deploy, reset database aktif, atau perubahan `.env`. Perubahan lokal dari tugas sebelumnya tetap dipertahankan.

## 1. Hasil audit status lama

Baseline saat tugas dimulai sudah memiliki lima status presentasi dalam `config/monitoring-statuses.json`, dibaca PHP dan JavaScript. Kategori Menunggu Penanganan memuat kasus yang telah ditugaskan tetapi belum mulai kerja; progress atau status teknis pekerjaan baru menaikkannya ke Dalam Penanganan. Persetujuan Operator sebelumnya membuat keputusan dan kasus, sedangkan penugasan/target dilakukan lewat aksi terpisah.

Status teknis permohonan tetap `diajukan`, `sedang_direview`, `diterima`, `ditolak`. Status teknis kasus tetap `diterima`, `ditugaskan`, `sedang_direview`, `siap_dieksekusi`, `dalam_pelaksanaan`, `ditunda`, `selesai`. Penugasan, perpanjangan, progress, riwayat, dan laporan/foto mempunyai tabel serta siklus sendiri.

Audit memeriksa migration dan indeks SQLite yang benar-benar terbentuk, model/relasi, service, Form Request, controller API/web, route/RBAC, dashboard lima role, WebGIS, laporan, provider/statistik JavaScript, seeder, dan test. `foreignId()->constrained()->unique()` pada beberapa migration lama tidak menghasilkan indeks unik `permohonan_id` pada SQLite yang diperiksa; implementasi tidak mengandalkan asumsi indeks tersebut untuk mencegah approval ganda.

Database aktif berisi 15 akun, 18 diagnosis, 14 permohonan, 11 kasus, 10 penugasan, satu laporan akhir, dan 5.658 referensi Kelompok Tani. Distribusi teknis tetap: permohonan 3 diajukan / 10 diterima / 1 ditolak; kasus 5 dalam_pelaksanaan / 1 diterima / 1 ditugaskan / 1 ditunda / 1 sedang_direview / 2 selesai.

Demo V2 sebelumnya berisi 20 permohonan, 14 kasus dan penugasan, tiga perpanjangan, empat laporan akhir, serta delapan PNG. Empat kasus kategori lama `007`–`010` mempunyai keputusan diterima, POPT aktif, dan target valid; semuanya dapat dipresentasikan sebagai Dalam Penanganan tanpa mengubah riwayat.

## 2. Perubahan business logic

`MonitoringStatusService` menjadi sumber pemetaan, validasi kelengkapan historis, filter utama, dan indikator waktu. `MonitoringCaseService` mempertahankan satu entri per permohonan, diperkaya kasus setelah penerimaan. Filter, API, KPI, dashboard, dan laporan memakai predikat yang sama.

Kasus belum selesai hanya menjadi Dalam Penanganan jika permohonannya diterima, keputusan Operator diterima tersedia, serta penugasan aktif mempunyai POPT dan tanggal target valid. Keberadaan progress tidak menggantikan bukti persetujuan/penugasan/target. Status ini menunjukkan tanggung jawab penanganan, bukan waktu mulai pekerjaan fisik.

Kasus historis yang prasyaratnya belum lengkap dipresentasikan sebagai Menunggu Review disertai `history_incomplete`, daftar kekurangan, dan pesan tinjauan Operator. Tidak ada pembaruan status teknis maupun pembuatan riwayat otomatis. Tanggal target historis yang tidak dapat diparse dipresentasikan sebagai belum tersedia agar halaman/API tetap dapat dibaca.

Tidak diperlukan migration baru karena empat status merupakan proyeksi baca, bukan enum pengganti pada database. API Knowledge, formula CF, integrasi Disbun, dan data diagnosis tidak diubah oleh revisi ini.

## 3. Workflow Operator

Form persetujuan pada detail permohonan sekarang wajib memilih POPT aktif dan target masa depan. Input, pesan validasi, old input, loading saat menyimpan, dan kondisi tanpa POPT tersedia mengikuti pola form project. Review tetap dilakukan sebelum keputusan. Penolakan wajib mempunyai alasan; alasan tersedia untuk Poktan dan tidak membuat penugasan.

`TerimaPermohonanRequest` menggunakan validasi penugasan yang sudah tersedia. Service kembali memeriksa role/keaktifan POPT dan tanggal, sehingga kegagalan jalur service pun tidak meninggalkan data sebagian.

Satu transaksi menyimpan status/keputusan diterima, snapshot kasus, dua event awal teknis, penugasan POPT, dan target. Jika penugasan gagal, seluruh perubahan di-rollback. Hasilnya langsung Dalam Penanganan; tidak ada `accepted_at` POPT, event mulai kerja, atau progress buatan.

Pencegahan approval ganda memakai conditional UPDATE sebagai operasi pertama transaksi: request harus masih direview dan belum mempunyai keputusan/kasus hidup. Model kemudian dimuat ulang dengan row lock. Review/penolakan yang menggunakan model lama tidak dapat menimpa keputusan pemenang. Transaksi menyediakan retry terbatas untuk konflik database. Uji nyata dua proses dengan koneksi SQLite terpisah menghasilkan satu sukses dan satu error validasi, dengan masing-masing satu keputusan, kasus, dan penugasan.

Aksi penugasan ulang lama tetap tersedia untuk kewenangan Operator dan kasus historis yang sesuai; transaksi mengunci kasus sebelum pemeriksaan terminal maupun pergantian penugasan. Approval baru tidak membutuhkan aksi penugasan kedua.

## 4. Workflow POPT

Alur terima tugas, tinjauan, mulai/lanjutkan penanganan, progress, permintaan perpanjangan, laporan akhir, dan bukti foto tetap tersedia. Status utama tetap Dalam Penanganan selama belum selesai dan prasyarat kasus lengkap. Tidak ditambahkan tombol menolak tugas.

Penerimaan tugas menyimpan `accepted_at`; aksi mulai mencatat waktu aktual melalui riwayat teknis. Progress tetap append-only dan dapat memulai penanganan melalui service yang sudah ada. Informasi penerimaan/mulai/progress tidak dipalsukan oleh persetujuan Operator. Detail Poktan kini juga menampilkan event mulai aktual yang tersimpan.

Pengajuan perpanjangan tidak mengubah status utama atau target. Persetujuan Operator memperbarui target aktif dan mempertahankan riwayat; penolakan mempertahankan target lama. Penugasan historis dengan target kosong/tidak valid tidak dapat mengajukan perpanjangan sampai datanya ditinjau.

Penyelesaian baru tetap wajib melalui laporan akhir lengkap dan minimal satu foto valid. Guard service transisi terhadap bypass endpoint umum tetap dipertahankan. Kasus selesai terminal; POPT hanya dapat melakukan aksi pada assignment miliknya yang berlaku.

## 5. Tampilan Poktan

Daftar/detail permohonan, aktivitas dashboard, target, POPT, progress, perpanjangan, laporan akhir, dan foto menggunakan empat status yang sama. Alasan penolakan berasal dari keputusan yang benar-benar tersimpan. Salinan Dalam Penanganan menjelaskan bahwa kegiatan lapangan ditampilkan sesuai catatan aktual.

Indikator Melewati Target Waktu terpisah dari badge utama. Data historis tidak lengkap menampilkan alasan kekurangan, bukan klaim pekerjaan berjalan atau tepat waktu. Scope kepemilikan tetap berlaku; catatan internal perpanjangan mengikuti pembatasan yang sudah ada.

## 6. WebGIS dan Monitoring Pimpinan

Definisi JSON bersama sekarang hanya berisi empat kategori. Filter, legenda, ikon marker, popup, drawer, grafik status, serta tabel menggunakan metadata backend. Provider JavaScript membawa catatan historis tanpa membuat logika status baru. Grafik komoditas, penyakit, dan wilayah tetap memakai dataset/filter yang sama.

KPI monitoring/laporan: Total Permohonan, Menunggu Review, Dalam Penanganan, Selesai, dan Melewati Target Waktu. Ditolak tersedia dalam distribusi/filter/tabel. Dashboard Admin/Operator memakai ringkasan yang sama; dashboard Poktan/POPT tetap mempunyai scope milik sendiri. Jumlah aktif tidak mencakup kasus selesai atau data historis yang belum memenuhi prasyarat.

Satu permohonan dengan kasus tidak dihitung lagi sebagai entri terpisah. Aturan arsip sebelumnya dipertahankan. Marker hanya memakai koordinat aktual kasus/permohonan yang disimpan, tanpa fallback otomatis ke koordinat referensi kelompok. Pimpinan tetap read-only.

Export laporan tidak tersedia pada implementasi yang diaudit; revisi ini tidak menambahkan fitur export.

## 7. Pemetaan empat status utama

| Key | Label | Dasar |
| --- | --- | --- |
| `menunggu_review` | Menunggu Review | Permohonan belum diputuskan; termasuk pengecualian historis belum lengkap dengan catatan eksplisit. |
| `ditolak` | Ditolak | Keputusan penolakan, alasan wajib, tanpa kasus/assignment baru. |
| `dalam_penanganan` | Dalam Penanganan | Diterima + keputusan Operator + assignment POPT aktif + target valid, belum selesai. |
| `selesai` | Selesai | Penyelesaian baru melalui laporan akhir dan foto; data selesai historis tetap terminal. |

Kategori Menunggu Penanganan dihapus dari sumber status dan workflow aktif. API monitoring menolak filter key lama. Status teknis tetap tersedia pada API operasional dan timeline audit agar kontrak historis tidak rusak. Ditunda, disetujui, menunggu POPT, dan terlambat bukan kategori utama tambahan.

## 8. Indikator keterlambatan

`is_overdue` hanya true jika status utama Dalam Penanganan, target aktif valid, dan waktu sekarang lebih besar daripada target. Tepat pada batas target belum terlambat. Target berasal dari assignment aktif terbaru, termasuk perubahan yang sudah disetujui Operator.

Pengajuan perpanjangan pending atau rejected tidak menggunakan target usulan. Kasus selesai selalu tidak aktif/tidak terlambat, meskipun tanggal selesai historis lebih lambat dari target. Keterlambatan boleh beririsan dengan Dalam Penanganan dan tidak dijumlahkan sebagai kategori tambahan ke total.

## 9. Dampak terhadap data historis

Tidak dijalankan migrasi/reset/seed pada database aktif maupun demo V2. Fingerprint seluruh 35 tabel keduanya identik sebelum/sesudah tugas, termasuk password, referensi, keputusan, target, progress, laporan, dan foto. Tidak ada penghapusan atau backfill tanggal/persetujuan.

Pengecualian aktif yang ditemukan:

| Permohonan / kasus | Kekurangan | Presentasi aman |
| --- | --- | --- |
| `PM-20260919-4403` / `KS-20260919-6645` | Request masih diajukan, kasus teknis sedang_direview; keputusan diterima tidak tercatat lengkap. | Menunggu Review + catatan tinjauan; POPT/target/riwayat asli tetap terbaca. |
| `PM-UAT-093451` / `KS-UAT-093451` | Request diterima dan kasus dalam_pelaksanaan, tetapi keputusan formal Operator tidak tersedia. | Menunggu Review + catatan tinjauan; progress tidak dijadikan persetujuan pengganti. |
| `PM-20260101-9001` / `KS-20260101-9001` | Keputusan diterima tersedia, tetapi assignment aktif dan target tidak tersedia. | Menunggu Review + rincian kekurangan. |
| `PM-20260101-9010` / `KS-20260101-9010` | Sudah selesai secara historis tanpa laporan akhir/foto yang diwajibkan alur baru. | Tetap Selesai dengan metadata/peringatan kekurangan bukti; tidak dibuka kembali atau dibuatkan foto palsu. |

Pilihan tindak lanjut: Operator/Admin memeriksa dokumen sumber dan audit sebelum rekonsiliasi terkontrol. Kasus dengan keputusan hilang tidak boleh “diperbaiki” dengan klik approval baru yang menggandakan kasus. Assignment yang memang belum ada dapat dilengkapi melalui kewenangan penugasan berdasarkan bukti dan target baru yang sah. Pelengkapan arsip selesai memerlukan prosedur tersendiri. Rekonsiliasi otomatis maupun halaman khusus rekonsiliasi belum dibuat.

Hasil proyeksi baca database aktif pada waktu audit: total 14, review 5, ditolak 1, dalam penanganan 6, selesai 2, terlambat 6. Perubahan jumlah KPI berasal dari aturan pemetaan yang lebih ketat, bukan perubahan baris data. Angka waktu dapat berkembang setelah tanggal audit.

## 10. Seeder dan distribusi demo

Demo V2 lama terpetakan aman menjadi 3 review / 3 ditolak / 10 penanganan / 4 selesai tanpa perubahan database. Seeder terbaru menghasilkan distribusi sama pada SQLite baru `storage/app/demo-status-v3/validated-20261013.sqlite`. Namespace fixture `DEMO-V2` dipertahankan agar pengulangan tidak membuat identitas baru/duplikat.

Dataset baru memiliki total 20, review 3, penanganan 10, selesai 4, terlambat 5; tiga perpanjangan (pending/approved/rejected), empat laporan akhir, delapan PNG, empat komoditas, empat penyakit, lima wilayah, dan 20 koordinat kasus valid/berbeda. Kasus `007`–`010` tidak mendapat progress atau waktu mulai buatan.

Pada fixture baru, waktu persetujuan dan penugasan sama. Fixture yang telah tersimpan dilewati sebelum perhitungan tanggal; target/password/progress/histori lama tidak ditulis ulang. Seeder tetap menolak environment berbahaya dan database campuran. Seed CLI kedua pada database baru menghasilkan fingerprint seluruh 35 tabel identik, serta nol pelanggaran foreign key.

Provider/statistik JavaScript dijalankan terhadap resource nyata hasil seed dan menghasilkan KPI/distribusi sama. PNG diperiksa melalui disk public dan symlink `public/storage` (format, ukuran, keberadaan file). Tidak diklaim pengujian browser/static HTTP.

Artefak lokal di luar Git dan perintah menjalankannya dijelaskan pada [panduan demo](/Users/hoesnicodes/Developer/SIPAKARBUN/docs/DEMO_STATUS_TERPADU.md). Server demo belum dijalankan; koneksi aktif tidak dialihkan.

## 11. File yang berubah khusus revisi ini

Daftar berikut dibandingkan dengan salinan awal tugas, bukan seluruh `git diff` yang juga mencakup pekerjaan sebelumnya. File migration, Knowledge, Diagnosis, Disbun, serta palet tombol dari tugas lain tidak termasuk perubahan revisi ini. Model kasus hanya mendapat pembaruan komentar kontrak; tidak ada perubahan skema.

- [README.md](/Users/hoesnicodes/Developer/SIPAKARBUN/README.md)
- [app/Http/Controllers/DashboardController.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Controllers/DashboardController.php)
- [app/Http/Controllers/OperatorPermohonanController.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Controllers/OperatorPermohonanController.php)
- [app/Http/Controllers/OperatorWorkflowController.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Controllers/OperatorWorkflowController.php)
- [app/Http/Controllers/PoptWorkflowController.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Controllers/PoptWorkflowController.php)
- [app/Http/Requests/TerimaPermohonanRequest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Requests/TerimaPermohonanRequest.php)
- [app/Http/Resources/KasusPenangananResource.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Resources/KasusPenangananResource.php)
- [app/Http/Resources/MonitoringCaseResource.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Resources/MonitoringCaseResource.php)
- [app/Http/Resources/PermohonanResource.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Http/Resources/PermohonanResource.php)
- [app/Models/KasusPenanganan.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Models/KasusPenanganan.php)
- [app/Services/KasusService.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Services/KasusService.php)
- [app/Services/MonitoringCaseService.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Services/MonitoringCaseService.php)
- [app/Services/MonitoringStatusService.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Services/MonitoringStatusService.php)
- [app/Services/PermohonanService.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Services/PermohonanService.php)
- [app/Services/PoktanHandlingViewService.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Services/PoktanHandlingViewService.php)
- [app/Services/PoptHandlingService.php](/Users/hoesnicodes/Developer/SIPAKARBUN/app/Services/PoptHandlingService.php)
- [config/monitoring-statuses.json](/Users/hoesnicodes/Developer/SIPAKARBUN/config/monitoring-statuses.json)
- [database/factories/KasusPenangananFactory.php](/Users/hoesnicodes/Developer/SIPAKARBUN/database/factories/KasusPenangananFactory.php)
- [database/seeders/SipakarbunDemoSeeder.php](/Users/hoesnicodes/Developer/SIPAKARBUN/database/seeders/SipakarbunDemoSeeder.php)
- [docs/DEMO_STATUS_TERPADU.md](/Users/hoesnicodes/Developer/SIPAKARBUN/docs/DEMO_STATUS_TERPADU.md)
- [docs/STATUS_TERPADU_IMPLEMENTATION_REPORT.md](/Users/hoesnicodes/Developer/SIPAKARBUN/docs/STATUS_TERPADU_IMPLEMENTATION_REPORT.md)
- [docs/TEAM_TESTING_SETUP.md](/Users/hoesnicodes/Developer/SIPAKARBUN/docs/TEAM_TESTING_SETUP.md)
- [resources/js/webgis/case-detail.js](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/js/webgis/case-detail.js)
- [resources/js/webgis/dashboard.js](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/js/webgis/dashboard.js)
- [resources/js/webgis/data-provider.js](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/js/webgis/data-provider.js)
- [resources/js/webgis/map.js](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/js/webgis/map.js)
- [resources/js/webgis/mock-cases.js](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/js/webgis/mock-cases.js)
- [resources/js/webgis/statistics.js](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/js/webgis/statistics.js)
- [resources/views/monitoring/_content.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/monitoring/_content.blade.php)
- [resources/views/monitoring/report/index.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/monitoring/report/index.blade.php)
- [resources/views/operator/kasus/index.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/operator/kasus/index.blade.php)
- [resources/views/operator/kasus/show.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/operator/kasus/show.blade.php)
- [resources/views/operator/permohonan/index.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/operator/permohonan/index.blade.php)
- [resources/views/operator/permohonan/show.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/operator/permohonan/show.blade.php)
- [resources/views/permohonan/show.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/permohonan/show.blade.php)
- [resources/views/popt/penugasan/index.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/popt/penugasan/index.blade.php)
- [resources/views/popt/penugasan/show.blade.php](/Users/hoesnicodes/Developer/SIPAKARBUN/resources/views/popt/penugasan/show.blade.php)
- [tests/Feature/FinalFourStatusWorkflowTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/FinalFourStatusWorkflowTest.php)
- [tests/Feature/KasusFlowTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/KasusFlowTest.php)
- [tests/Feature/KasusReadContractTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/KasusReadContractTest.php)
- [tests/Feature/MonitoringReportTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/MonitoringReportTest.php)
- [tests/Feature/OperatorApprovalConcurrencyTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/OperatorApprovalConcurrencyTest.php)
- [tests/Feature/OperatorPermohonanApiTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/OperatorPermohonanApiTest.php)
- [tests/Feature/OperatorWorkflowPhase3Test.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/OperatorWorkflowPhase3Test.php)
- [tests/Feature/PoktanHandlingVisibilityPhase5Test.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/PoktanHandlingVisibilityPhase5Test.php)
- [tests/Feature/PoptHandlingWorkflowPhase4Test.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/PoptHandlingWorkflowPhase4Test.php)
- [tests/Feature/SipakarbunDemoSeederTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/SipakarbunDemoSeederTest.php)
- [tests/Feature/UnifiedHandlingStatusTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/UnifiedHandlingStatusTest.php)
- [tests/Feature/WebDashboardTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/WebDashboardTest.php)
- [tests/Feature/WebOperationalWorkflowTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/WebOperationalWorkflowTest.php)
- [tests/Feature/WebPermohonanTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/WebPermohonanTest.php)
- [tests/Feature/WorkflowPenguatanTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Feature/WorkflowPenguatanTest.php)
- [tests/Unit/Services/MonitoringStatusServiceTest.php](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/Unit/Services/MonitoringStatusServiceTest.php)
- [tests/js/data-provider.test.js](/Users/hoesnicodes/Developer/SIPAKARBUN/tests/js/data-provider.test.js)

Factory kasus menyediakan state eksplisit `withApproval()` bagi fixture yang memang memenuhi prasyarat. Test state machine lama tetap memakai fixture historis eksplisit agar cakupan assignment/arsip/transisi tidak hilang akibat perubahan endpoint approval.

## 12. Hasil test dan build

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test --compact` | PASS — **595 test, 4.411 assertion**, tanpa kegagalan/skipped. |
| `npm run test:provider` | PASS — **52 test JavaScript**. |
| `npm run build` | PASS — Vite, 83 module. |
| `php artisan view:clear` | PASS. |
| `php artisan view:cache` | PASS. |
| Pint, termasuk `--test` pada seluruh PHP yang berubah khusus revisi | PASS. |
| `git diff --check` | PASS. |
| Migration biasa pada SQLite baru yang diverifikasi kosong | PASS. |
| Seed kedua / 35 fingerprint tabel / foreign key | PASS — identik / nol pelanggaran. |
| Resource demo → provider/statistik/grafik JavaScript | PASS — 20 / 3 / 10 / 4 / 5 dan distribusi 3/3/10/4. |
| Fingerprint DB aktif + DB demo V2 | PASS — masing-masing 35 tabel identik. |

Tidak ada test yang dihapus/dinonaktifkan. Baseline 580 bertambah menjadi 595: 13 kasus test workflow empat status, satu test konkurensi nyata, dan satu test tambahan histori tidak lengkap (enam variasi dalam test). Ekspektasi test lama diperbarui agar fixture approval valid menyertakan POPT/target atau keputusan formal.

Cakupan 25 skenario wajib:

| Kelompok | Bukti test |
| --- | --- |
| 1–3 pengajuan, penolakan, alasan wajib | `PermohonanApiTest`, `FinalFourStatusWorkflowTest`, `UnifiedHandlingStatusTest`. |
| 4–7 syarat approval, hasil langsung, kategori lama hilang | `FinalFourStatusWorkflowTest`, `OperatorPermohonanApiTest`, `WebOperationalWorkflowTest`, provider JS. |
| 8–12 penerimaan POPT, progress, pending/approved/rejected perpanjangan | `UnifiedHandlingStatusTest`, `PoptHandlingWorkflowPhase4Test`, `OperatorWorkflowPhase3Test`. |
| 13–16 overdue, selesai tidak aktif, foto wajib, laporan lengkap | `UnifiedHandlingStatusTest`, unit status, test POPT, test seeder. |
| 17–18 ownership Poktan / Pimpinan read-only | test Poktan, `MonitoringReportTest`, `UnifiedHandlingStatusTest`, test seeder. |
| 19–21 empat filter, KPI/grafik, koordinat kasus | `FinalFourStatusWorkflowTest`, `MonitoringReportTest`, unit status, provider/location JS, resource demo nyata. |
| 22 data historis tetap terbaca | `FinalFourStatusWorkflowTest` (termasuk tanggal invalid), `KasusReadContractTest`, unit status. |
| 23–24 distribusi dan idempotensi seeder | 16 test `SipakarbunDemoSeederTest`, dua eksekusi CLI terisolasi + fingerprint. |
| 25 request berulang, stale model, rollback, konkurensi | `FinalFourStatusWorkflowTest`, `OperatorApprovalConcurrencyTest` (dua proses SQLite). |

## 13. Keterbatasan yang tersisa

Tiga kasus aktif historis belum dapat dinyatakan memenuhi approval lengkap; satu arsip selesai kekurangan bukti. Keduanya dilaporkan terbuka pada bagian 9 dan tidak diperbaiki dengan data fiktif.

UAT visual/responsif browser belum dijalankan pada revisi ini. Test HTTP dan kompilasi Blade memverifikasi render/kontrak, bukan alignment visual atau klik browser nyata. Eksekusi konkurensi diuji pada SQLite sesuai environment lokal; MySQL/PostgreSQL belum diuji. Indeks unik fisik lama tetap seperti hasil audit; penulisan SQL di luar service tidak mendapat jaminan aplikasi yang sama.

Export laporan, halaman rekonsiliasi historis, notifikasi keterlambatan, dan deploy bukan hasil implementasi ini. Data dummy tetap berada pada SQLite terpisah; aplikasi aktif tidak otomatis menampilkan dataset baru.

## 14. Rekomendasi UAT browser

Gunakan database demo terpisah sesuai panduan dan ambil baseline sebelum melakukan aksi karena UAT akan mengubah datanya. Bandingkan Poktan, Operator, POPT, Admin, dan Pimpinan pada 1440×900, 1366×768, 1024×768, 768×1024, 390×844, serta 360×800.

1. Operator: mulai review; coba approval tanpa POPT/target, tanggal lampau, dan POPT tidak aktif. Pastikan error jelas, old input pulih, status masih review, tanpa kasus sebagian. Approval valid langsung menampilkan Dalam Penanganan dan POPT/target; klik/request ulang tidak menggandakan.
2. Penolakan: alasan kosong ditolak; alasan sah tampil pada detail Poktan, tanpa tugas baru.
3. POPT: terima tugas, mulai kerja, kirim beberapa progress. Status tetap Dalam Penanganan; waktu penerimaan/mulai dan urutan progress sesuai aksi, tanpa tombol menolak tugas.
4. Perpanjangan: uji pending, approved, rejected. Hanya approved mengubah target dan indikator terlambat; status utama tetap sama.
5. Penyelesaian: kirim tanpa foto lalu dengan foto valid. Hanya laporan lengkap menjadi Selesai; selesai tidak lagi masuk aktif/overdue. Pastikan foto dapat dimuat setelah reload.
6. Poktan: akses kasus milik sendiri, progress/laporan/foto; URL pemilik lain ditolak. Periksa catatan historis tidak lengkap dan kasus selesai tanpa bukti pada database salinan yang sesuai.
7. Pimpinan/Admin/Operator: sebelum aksi UAT, bandingkan WebGIS, dashboard, filter, legenda, tabel, dan grafik dengan 20 total / 3 review / 10 penanganan / 4 selesai / 5 terlambat; distribusi empat status 3/3/10/4. Pastikan Pimpinan tidak memiliki akses mutasi.
8. Peta: cocokkan 20 titik dengan koordinat kasus, bukan kelompok; filter kombinasi status/terlambat tidak menggandakan entri. Pastikan popup/drawer menampilkan status, alasan, target, dan catatan sesuai backend.
9. Semua ukuran: periksa wrapping label/target, input/select, loading, pesan kosong/error, scroll tabel/drawer, akses keyboard, tombol aksi, serta overlay sidebar di atas peta. Tidak boleh ada kategori Menunggu Penanganan atau horizontal overflow halaman.
