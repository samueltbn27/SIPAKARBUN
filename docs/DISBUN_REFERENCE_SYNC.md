# Sinkronisasi Referensi Disbun

SIPAKARBUN mengambil referensi komoditas dan kelompok tani melalui backend,
menormalisasinya, lalu menyimpan entitas unik yang berhasil dilayani API ke
database lokal. Browser hanya membaca endpoint internal SIPAKARBUN; browser
tidak mengakses host Disbun secara langsung.

## Operasional

- Sinkronisasi manual: `php artisan disbun:sync-references`
- Audit pagination read-only: `php artisan disbun:audit-kelompok-tani`
- Audit mapping komoditas: `php artisan disbun:audit-commodity-mappings`
- Jadwal otomatis: setiap hari pukul 02.00 `Asia/Jakarta`, dengan pencegahan
  overlap selama 120 menit.

Semua halaman harus selesai diambil sebelum database dimutasi. Kegagalan
jaringan, metadata yang tidak konsisten, atau duplicate ID dengan payload yang
berkonflik membuat sinkronisasi gagal tertutup dan mempertahankan snapshot
terakhir yang baik. Mock hanya dipakai oleh test yang memilihnya secara
eksplisit.

## Peringatan sumber yang pernah diamati

Pada snapshot live yang diaudit 29 Agustus 2026, API melaporkan `count=6015`,
melayani 5.927 baris mentah, dan menghasilkan 5.650 external ID unik. Terdapat
277 kemunculan duplikat exact, tanpa duplicate berkonflik. SIPAKARBUN menyimpan
entitas unik yang benar-benar dapat diambil, tidak membuat 88 entitas untuk
menutup selisih metadata, dan menyimpan hasil sebagai `SUCCESS WITH SOURCE
WARNING`.

Angka tersebut adalah observasi satu snapshot, bukan konstanta aplikasi. Nilai
dapat berubah pada sinkronisasi berikutnya dan selalu harus dinilai dari laporan
command terbaru.

Mapping komoditas hanya memakai normalisasi exact atau alias eksplisit yang
dapat diaudit. Numeric ID kelompok tani tidak dianggap kompatibel dengan
numeric ID master komoditas, dan nilai ambigu tidak dipetakan secara fuzzy.
