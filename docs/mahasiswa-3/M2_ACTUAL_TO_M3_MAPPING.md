# M2 Actual → M3 Read Mapping

Status: `READY WITH WARNINGS` — mapping adapter tersedia, tetapi activation ke
production UI menunggu response M2 melengkapi field yang belum diekspos dan
menyepakati authorization global monitoring.

## Endpoint yang diaudit

M2 menyediakan `GET /api/kasus` melalui `KasusController@index`. Response
menggunakan `KasusPenangananResource::collection()` dan envelope `data`.
Endpoint saat ini dibatasi untuk `admin|operator_uptd`.

## Mapping

| M3 normalized field | Actual M2 source | Transformation | Status |
|---|---|---|---|
| `case_id` | `kasus_id` | Rename | Available |
| `case_code` | `kasus_code` | Rename | Available |
| `latitude` | `lokasi_kasus.latitude` | Numeric/range validation | Available |
| `longitude` | `lokasi_kasus.longitude` | Numeric/range validation | Available |
| `kelompok_tani.id` | `permohonan.kelompok_tani.id` | Read-only nested mapping | Missing from `/api/kasus` resource |
| `kelompok_tani.nama` | `permohonan.kelompok_tani.nama` | Read-only nested mapping | Missing from `/api/kasus` resource |
| `komoditas.id` | `komoditas.id` | Preserve internal reference ID | Available |
| `komoditas.kode` | `komoditas.kode` | Preserve snapshot | Available |
| `komoditas.nama` | `komoditas.nama` | Preserve snapshot | Available |
| `penyakit.id` | `penyakit.id` | Preserve snapshot ID | Available |
| `penyakit.nama` | `penyakit.nama` | Preserve snapshot | Available |
| `wilayah.*` | `permohonan.lokasi_kasus.*` | Read-only nested mapping | Missing from `/api/kasus` resource |
| `popt.id` | `penugasan_popt.popt_id` | Rename | Available when relation loaded |
| `popt.nama` | `penugasan_popt.popt_name` | Rename | Available when relation loaded |
| `status` | `current_status` serialized as `status` | Explicit M2→M3 mapping | Available |
| `request_status` | `permohonan.status` | Separate from handling status | Missing from `/api/kasus` resource |
| `last_note` | latest `riwayat_status.catatan` | Derive latest history entry | Missing on list response |
| `last_status_at` | latest `riwayat_status.created_at` | Derive latest history entry | Missing on list response |
| `status_history` | `riwayat_status` | Rename fields and map status | Missing on list response |

## Status mapping

| M2 `current_status` | M3 normalized status |
|---|---|
| `diterima` | `null` — accepted but not assigned; no M3 execution status equivalent |
| `ditugaskan` | `assigned` |
| `sedang_direview` | `under_review` |
| `ditunda` | `postponed` |
| `siap_dieksekusi` | `ready_for_execution` |
| `dalam_pelaksanaan` | `in_progress` |
| `selesai` | `completed` |

`ditolak` belongs to `permohonan.status` and is never mapped to the POPT
handling status.

## Integration decision

The existing `ApiCaseProvider` now understands the actual M2 resource names
and status values. It is not silently selected as the production provider yet:

1. `/api/kasus` does not expose the group and administrative region fields
   required by M3.
2. `/api/kasus` does not include request status or history/note data in its
   list resource.
3. `/api/kasus` authorization currently excludes `popt` and `pimpinan`, even
   though those roles can open the global M3 surfaces.

Until those contract/authorization gaps are resolved by the M2 owner, M3 keeps
the existing MockCaseProvider for local UI development. API failures remain
errors; the adapter never falls back from a configured API call to mock data.
