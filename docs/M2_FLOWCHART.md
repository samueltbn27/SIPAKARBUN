# SIPAKARBUN M2 — Flowchart Diagnosis & Penanganan Kasus

Alur end-to-end Mahasiswa 2: Poktan → Diagnosis → Permohonan → Operator → POPT → Penanganan → Selesai.

> Catatan vocabulary: status `diterima` dan `sedang_direview` muncul di DUA
> tabel dengan makna berbeda (permohonan vs kasus). Flowchart memakai prefix
> `Permohonan.` / `Kasus.` agar tidak tertukar. Kasus tidak mengenal status
> `ditolak` — penolakan hanya terjadi di level permohonan.

```mermaid
flowchart TD
    A["Poktan terdaftar<br/>(Kode Poktan Disbun + password)"] --> B{"Akun aktif?"}
    B -- "Belum (is_active=false)" --> C["Admin: Pengguna → Setujui<br/>(guard referensi Disbun)"]
    C --> D["Login via Kode Poktan"]
    B -- "Sudah" --> D

    D --> E["Diagnosis: pilih komoditas → gejala → keyakinan<br/>(autosave localStorage + Lanjutkan)"]
    E --> F{"Hasil CF ada?"}
    F -- "Tidak" --> E
    F -- "Ya (status=selesai)" --> G["Ajukan Permohonan<br/>(Poktan terkunci + titik lokasi kasus)"]

    G --> H["Permohonan.diajukan"]
    H --> I["Operator: review (WAJIB)"]
    I --> J{"Keputusan Operator<br/>(hanya dari Sedang Direview)"}
    J -- "Tolak + catatan wajib" --> K["Permohonan.ditolak<br/>(terminal)"]
    J -- "Terima" --> L["Permohonan.diterima<br/>+ Kasus lahir (Kasus.diterima)"]

    L --> M["Operator: assign POPT aktif"]
    L -- "Salah terima? (masih diterima/ditugaskan)" --> X["Operator: batalkan kasus<br/>(tutup penugasan, Permohonan → Sedang Direview,<br/>kasus diarsip, bisa diputuskan ulang)"]
    M --> N["Kasus.ditugaskan"]
    N --> O["POPT: sedang_direview"]
    O --> P{"Verifikasi lapangan"}
    P -- "Tunda" --> Q["Kasus.ditunda"]
    Q --> O
    P -- "Siap" --> R["Kasus.siap_dieksekusi"]
    R --> S["Kasus.dalam_pelaksanaan"]
    S --> T["POPT: selesai<br/>(HANYA dari dalam_pelaksanaan)"]
    T --> V["Operator/Admin: verifikasi selesai"]
    V --> U["Kasus selesai terverifikasi (final)"]
```

## Gate penting

| Titik | Aturan |
|---|---|
| Diagnosis → Permohonan | Diagnosis harus milik sendiri + `status=selesai` + punya hasil (`PermohonanService`) |
| Permohonan → review | `review()` mengubah `diajukan` → `sedang_direview` + mencatat reviewer |
| Permohonan → Kasus | Hanya via `terima()` dari `sedang_direview` (tanpa review = 422); lahir `Kasus.diterima` |
| Kasus salah terima | `batalkanKasus()` hanya saat `diterima`/`ditugaskan`: penugasan dicabut, permohonan → `sedang_direview`, keputusan dihapus, kasus soft-delete, kode lama tetap dipakai (bisa diputuskan ulang jadi kasus baru) |
| Kasus.diterima → ditugaskan | Hanya via `assignPopt()` oleh Admin/Operator ke user `popt` aktif |
| Progres kasus | POPT pemilik penugasan aktif ATAU Admin/Operator (intervensi), mengikuti `config/kasus.php` transitions; `selesai` hanya dari `dalam_pelaksanaan` |
| Kasus.selesai | Belum final sampai `verifikasiSelesai()` oleh Admin/Operator (`verified_by/at`); Poktan/POPT melihat badge verifikasi |

## Padanan vocabulary M2 ↔ kontrak M3

| M2 (`config/kasus.php`) | M3 (kontrak API) |
|---|---|
| `ditugaskan` | `assigned` |
| `sedang_direview` | `under_review` |
| `siap_dieksekusi` | `ready` |
| `dalam_pelaksanaan` | `in_progress` |
| `selesai` | `completed` |
| `ditunda` | `deferred` |
| `diterima` | `accepted` |

Sumber state machine: `config/kasus.php`, penegak `StatusTransitionService`.
