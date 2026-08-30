# Handoff M3 → M2: Provider dan Kontrak Kasus

Dokumen ini mencatat kebutuhan integrasi yang masih menunggu konfirmasi Mahasiswa 2. M3 saat ini tetap berjalan menggunakan `MockCaseProvider` dan tidak melakukan request API nyata.

## Yang sudah siap di M3

- `resources/js/webgis/data-provider.js` menyediakan `getCases()` asynchronous.
- `MockCaseProvider` menjadi provider aktif untuk WebGIS dan Dashboard Monitoring.
- Semua hasil provider melewati `normalizeCases()`.
- `ApiCaseProvider` tersedia sebagai skeleton adapter yang endpoint-nya wajib diberikan oleh caller; tidak ada endpoint default.
- Koordinat invalid dinormalisasi menjadi `null`, tidak dibuat marker, tetapi kasus tetap tersedia untuk Dashboard.
- `request_status` dipisahkan dari `status`.
- `status` selalu bermakna status penanganan POPT.
- Metadata komoditas dapat membawa `id`, `nama`, `source_commodity_id`, dan `mapping_status`.

## Konfirmasi yang dibutuhkan dari M2

1. Nama dan tipe identifier kasus (`case_id` dan `case_code`).
2. Nama field koordinat kasus dan kepastian bahwa koordinat tersebut adalah lokasi kejadian/penanganan.
3. Daftar final machine value untuk `status` penanganan POPT.
4. Daftar machine value `request_status`, bila status permohonan ikut dikirim.
5. Bentuk response API: array langsung atau envelope `{ data: [] }`.
6. Nama field relasi Poktan, penyakit, wilayah, dan POPT.
7. Relasi komoditas internal SIPAKARBUN serta arti `source_commodity_id` dan `mapping_status`.
8. Format timestamp dan timezone.
9. Aturan nullable POPT sebelum assignment.
10. Pagination atau batas jumlah data bila endpoint mengembalikan dataset besar.

## Aturan yang tidak boleh berubah tanpa kesepakatan

- M3 tidak mengubah status, catatan, penugasan, atau `status_history`.
- `request_status` tidak boleh dipakai sebagai `status` penanganan.
- Koordinat Poktan tidak boleh menjadi fallback koordinat kasus.
- ID numeric eksternal Disbun tidak boleh dipakai sebagai ID komoditas internal tanpa mapping eksplisit dari M2.
- Payload percakapan Poktan–POPT tidak termasuk dalam read model ini.

## Cara menjalankan verifikasi lokal

```bash
npm run test:provider
npm run build
```

Review kontrak ini bersama M2 terlebih dahulu. Setelah schema/API disepakati, langkah berikutnya adalah menghubungkan `ApiCaseProvider` melalui konfigurasi yang disetujui dan menambahkan integration test terhadap response nyata/staging.
