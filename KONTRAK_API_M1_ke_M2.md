# Kontrak API — Mahasiswa 1 (Knowledge) → Mahasiswa 2 (Diagnosis)

Sesuai PRD §23.2, M1-FR-011, M1-FR-012, M1-FR-013.
Versi kontrak: v1 — 13 Agustus 2026.

> Perubahan apa pun pada bentuk response di bawah ini WAJIB dikomunikasikan
> ke Mahasiswa 2 sebelum di-deploy (§23.1 PRD).

---

## Autentikasi

Semua endpoint di bawah butuh header:

```
Authorization: Bearer {token}
```

Token didapat lewat Sanctum. **Masih perlu disepakati tim**: apakah Mahasiswa 2
pakai token akun "service" khusus, atau mekanisme lain — belum final.

---

## 1. `GET /api/penyakit`

Daftar penyakit **aktif**, lengkap dengan rule CF dan solusi — cukup untuk
menjalankan mesin diagnosis tanpa panggilan tambahan.

### Query parameter

| Parameter | Wajib | Keterangan |
|---|---|---|
| `komoditas_id` | Tidak | Filter penyakit yang terkait komoditas tertentu. ID ini merujuk ke `ref_komoditas.id` milik Shared Integration (§23.4), bukan ID lokal Mahasiswa 1. |

### Contoh response

```json
{
  "data": [
    {
      "id": 1,
      "kode": "PY-001",
      "nama": "Karat Daun Kopi",
      "deskripsi": "Penyakit jamur (Hemileia vastatrix) ...",
      "komoditas_id": [1, 2],
      "aturan_cf": [
        { "gejala_id": 1, "gejala_nama": "Bercak jingga di bawah permukaan daun", "cf_pakar": 0.9 },
        { "gejala_id": 2, "gejala_nama": "Daun menguning dan gugur", "cf_pakar": 0.7 }
      ],
      "solusi": [
        { "judul": "Pangkas & musnahkan daun terinfeksi", "deskripsi": "..." }
      ],
      "updated_at": "2026-08-12T10:00:00+00:00"
    }
  ]
}
```

### Catatan penting

- Hanya penyakit dengan `is_active = true` yang muncul.
- Hanya `aturan_cf` dan `solusi` dengan `is_active = true` yang ikut ditampilkan.
- `cf_pakar` bertipe desimal, rentang **-1 s.d 1** (asumsi, lihat catatan di migration `aturan_cf` — belum dikonfirmasi final ke pakar).

---

## 2. `GET /api/gejala`

Daftar gejala **aktif**, untuk mengisi form pilih-gejala di modul diagnosis.

### Query parameter

| Parameter | Wajib | Keterangan |
|---|---|---|
| `komoditas_id` | Tidak | Kalau diisi: hanya gejala yang pernah dipakai di rule CF aktif milik penyakit yang terkait komoditas itu. Kalau kosong: semua gejala aktif. |

### Contoh response

```json
{
  "data": [
    { "id": 1, "kode": "GJ-001", "nama": "Bercak jingga di bawah permukaan daun", "deskripsi": null }
  ]
}
```

---

## Yang BUKAN tanggung jawab Mahasiswa 1

| Kebutuhan | Disediakan oleh | Endpoint |
|---|---|---|
| Daftar komoditas valid | Shared Integration | `GET /api/referensi/komoditas` |
| Daftar kelompok tani | Shared Integration | `GET /api/referensi/kelompok-tani` |

---

## ⚠️ Isu terbuka yang perlu dikonfirmasi ke tim

1. **"Knowledge version"** disebut dibutuhkan Mahasiswa 2 di PRD §23.2, tapi
   model data final (§22.4) tidak punya tabel `knowledge_versions` — hanya
   kolom `is_active` per baris. Endpoint di atas **tidak** menyediakan data
   versi terpisah. Perlu dikonfirmasi: apakah ini memang sudah tidak
   dibutuhkan, atau ada gap yang harus ditambal.
2. **Mekanisme autentikasi service-to-service** (M2 memanggil API M1) belum
   disepakati final — saat ini diasumsikan token Sanctum biasa.
3. **Rentang nilai `cf_pakar`** (-1 s.d 1) masih asumsi, belum dikonfirmasi
   ke Pakar/pembimbing.
