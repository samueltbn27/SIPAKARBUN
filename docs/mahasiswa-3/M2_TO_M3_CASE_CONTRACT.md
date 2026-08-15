# Kontrak Data Kasus M2 → M3

Status dokumen: rancangan kontrak read-only untuk integrasi Mahasiswa 3. Dokumen ini bukan API final dan tidak mengubah database atau endpoint pada branch ini.

## Tujuan dan batasan

M3 mengonsumsi read model kasus yang disediakan M2 untuk marker WebGIS, popup, filter, drawer detail, timeline audit, KPI, dan chart. M3 tidak mengubah status, catatan, penugasan, atau riwayat kasus.

Sumber data saat ini adalah `MockCaseProvider` di `resources/js/webgis/data-provider.js`. Provider tersebut adalah boundary yang kelak dapat diganti dengan adapter API M2 tanpa mengubah komponen WebGIS dan Dashboard.

## Bentuk data yang dinormalisasi

Field wajib untuk setiap kasus:

- `case_id`: identifier unik kasus.
- `case_code`: kode kasus yang dapat ditampilkan.
- `latitude`, `longitude`: koordinat lokasi kasus.
- `status`: machine value dari status workflow yang disepakati.

Field opsional yang boleh bernilai `null` atau tidak tersedia:

- `kelompok_tani`: `{ id, nama }`.
- `komoditas`: `{ id, kode, nama }`.
- `penyakit`: `{ id, nama }`.
- `wilayah`: `{ kode_kabupaten, kabupaten, kode_kecamatan, kecamatan }`.
- `popt`: `{ id, nama }`; nullable sebelum penugasan jika workflow mengizinkan.
- `last_note`, `last_status_at`.
- `status_history`: array audit `{ status, note, changed_at }`.

Contoh payload normalized:

```json
{
  "case_id": 123,
  "case_code": "KAS-2026-123",
  "latitude": -6.595,
  "longitude": 106.8167,
  "kelompok_tani": { "id": 10, "nama": "Poktan Sinar Tani" },
  "komoditas": { "id": 4, "kode": "KP-012", "nama": "Kopi Arabika" },
  "penyakit": { "id": 8, "nama": "Karat Daun" },
  "wilayah": {
    "kode_kabupaten": "3201",
    "kabupaten": "KABUPATEN BOGOR",
    "kode_kecamatan": "3201010",
    "kecamatan": "Cibinong"
  },
  "popt": { "id": 7, "nama": "Nama POPT" },
  "status": "under_review",
  "last_note": "Hasil identifikasi sedang ditinjau.",
  "last_status_at": "2026-08-15T10:00:00+07:00",
  "status_history": [
    {
      "status": "assigned",
      "note": "POPT ditugaskan.",
      "changed_at": "2026-08-14T09:30:00+07:00"
    },
    {
      "status": "under_review",
      "note": "Hasil identifikasi sedang ditinjau.",
      "changed_at": "2026-08-15T10:00:00+07:00"
    }
  ]
}
```

M2 menyediakan identifier, kode, koordinat, referensi Poktan, referensi komoditas yang digunakan kasus, diagnosis/penyakit, POPT, status, catatan terbaru, waktu perubahan, dan riwayat. M3 menganggap payload ini sebagai read-only.

## Kebijakan koordinat

`latitude` dan `longitude` merepresentasikan **lokasi kasus/kejadian penanganan**, bukan otomatis koordinat Poktan. M3 tidak boleh melakukan fallback ke koordinat Poktan ketika koordinat kasus kosong atau tidak valid.

Kasus dengan koordinat hilang atau di luar rentang geografis tetap dipertahankan dalam dataset untuk KPI, statistik, dan chart, tetapi dikategorikan non-mappable dan tidak dibuatkan marker WebGIS.

## Status dan kepemilikan workflow

Nilai machine status yang disepakati:

`assigned`, `under_review`, `postponed`, `ready_for_execution`, `in_progress`, `completed`.

M2 memiliki workflow dan perubahan status. M3 hanya memetakan nilai tersebut ke label, warna, marker, filter, KPI, dan timeline audit. Nilai `rejected` tidak ditambahkan sebagai status eksekusi pada kontrak ini.

`status_history` adalah audit/read model berisi transisi status, catatan singkat, dan timestamp. Field ini bukan payload percakapan Poktan–POPT dan tidak memodelkan chat, thread, reply, atau comment.

Timestamp sebaiknya ISO 8601 dan menyertakan timezone. Riwayat dikirim terurut atau setidaknya memiliki `changed_at` yang dapat digunakan untuk pengurutan deterministik.

## Catatan integrasi komoditas eksternal

`kelompok_tani.id_komoditi` dari sistem Disbun eksternal **tidak dijamin sama** dengan `komoditas.id` pada SIPAKARBUN. M3 tidak boleh melakukan join berdasarkan numeric ID eksternal tersebut. M2 harus menyediakan relasi komoditas normalized/internal yang dipakai oleh contract M3, termasuk `komoditas.id`, `komoditas.kode`, dan `komoditas.nama` bila tersedia.

Tidak ada pemanggilan langsung dari browser ke `dev.disbun.jabarprov.go.id`. Alur yang diharapkan:

```text
Disbun eksternal
    → backend/integration SIPAKARBUN
    → normalized database/read model
    → M3 WebGIS dan Dashboard melalui provider
```

## Proposal endpoint masa depan (belum final)

Ini hanya proposal untuk adapter berikutnya, bukan implementasi branch ini:

- `GET /api/webgis/cases` atau `GET /api/webgis`.
- Filter opsional: `status`, `commodity_id`, `regency_code`, `district_code`, `disease_id`, `popt_id`.
- Usulan response envelope:

```json
{
  "data": [],
  "meta": { "count": 10 }
}
```

URL, autentikasi, pagination, filter server-side, dan detail envelope harus disepakati setelah schema/API M2 tersedia. Branch ini tidak menambahkan endpoint API, fetch browser, model, migration, polling, atau realtime.

## Checklist kesiapan kontrak

- [ ] `case_id` unik.
- [ ] `case_code` tersedia.
- [ ] `status` menggunakan machine value yang disepakati.
- [ ] Koordinat merepresentasikan lokasi kasus.
- [ ] Koordinat invalid boleh ada tetapi ditandai non-mappable oleh consumer.
- [ ] POPT nullable sebelum assignment jika workflow mengizinkan.
- [ ] Riwayat terurut atau memiliki timestamp.
- [ ] Tidak ada payload percakapan Poktan–POPT.
- [ ] Timestamp menggunakan ISO 8601 dan menyertakan timezone.
