# RBAC Access Contract — Mahasiswa 3

Status: contract dan guard yang dapat diverifikasi pada branch `mahasiswa-3-webgis`. Contract ini tidak menambah role, endpoint kasus, migration, atau workflow baru.

## Prinsip enforcement

1. Authentication tetap menggunakan session Laravel dan `auth` middleware.
2. Role dibaca dari relasi role user melalui Spatie Permission di backend.
3. Middleware `role:` adalah enforcement utama untuk route; sidebar hanya membantu navigasi dan bukan security boundary.
4. Akses URL langsung harus menghasilkan `403` untuk role yang tidak memiliki akses.
5. UI M3 bersifat read-only untuk `pimpinan`; filter/reset pada Dashboard dan WebGIS bukan mutasi data.
6. Ownership, assignment, dan scope wilayah untuk data kasus belum boleh ditebak dari mock data; aturan final menunggu kontrak M2.

`pimpinan` sudah termasuk role login yang valid dan diarahkan ke Dashboard Monitoring. `poktan` tetap merupakan role resmi, tetapi belum dimasukkan ke landing login M3 karena modul `case-submit`/ownership belum tersedia; mengarahkannya ke Dashboard umum atau WebGIS akan membuka data di luar scope kepemilikannya.

## Role yang tersedia

| Role | Capability contract |
| --- | --- |
| `admin` | Akses penuh sesuai route yang tersedia, termasuk monitoring, WebGIS, knowledge, dan manajemen pengguna. |
| `operator_uptd` | Monitoring/WebGIS dan modul kerja Knowledge yang sudah tersedia; verifikasi, assignment, serta scope wilayah kasus masih menunggu endpoint M2. |
| `pimpinan` | Membaca Dashboard Monitoring, KPI, peta, detail, dan WebGIS; tidak memiliki aksi mutasi. |
| `popt` | WebGIS dan modul Knowledge yang sudah tersedia; halaman kasus assigned, update status, dan catatan penanganan masih menunggu backend M2. |
| `poktan` | Role resmi project untuk submit dan membaca permohonan miliknya sendiri; route submit/ownership belum tersedia pada M3 sehingga tidak diberi akses ke Dashboard Monitoring atau WebGIS umum. |
| `pakar` | Knowledge/referensi sesuai route yang tersedia; tidak dapat mengakses Dashboard Monitoring atau WebGIS umum. |

## Route dan module contract

| Module/route contract | Allowed roles | Status pada branch |
| --- | --- | --- |
| `dashboard-monitoring` | `admin`, `operator_uptd`, `pimpinan` | Implemented dan dijaga middleware backend. |
| `case-verification` | `admin`, `operator_uptd` | TODO; menunggu backend/API M2. |
| `case-assignment` | `admin`, `operator_uptd` | TODO; menunggu backend/API M2. |
| `case-monitoring` | `admin`, `operator_uptd`, `pimpinan` | Dashboard Monitoring/WebGIS read-only yang tersedia; detail case API belum final. |
| `case-assigned` | `admin`, `popt` | TODO; scope assignment menunggu M2. |
| `case-submit` | `admin`, `poktan` | TODO; submit dan ownership menunggu M2. |
| `user-management` | `admin` | Implemented di bawah prefix `knowledge/pengguna` dengan nested `role:admin`. |
| `knowledge` | `admin`, `pakar` sebagai contract utama M3 | Route legacy yang sudah ada juga melayani `operator_uptd` dan `popt` untuk menjaga behavior Knowledge existing; tidak diubah dalam slice ini. |
| `webgis` | `admin`, `operator_uptd`, `popt`, `pimpinan` | Implemented dan dipertahankan. `pakar` dan `poktan` ditolak. |

## Aksi dan kepemilikan

| Capability | Allowed roles sekarang | Constraint |
| --- | --- | --- |
| Read monitoring/KPI/map/detail | `admin`, `operator_uptd`, `pimpinan` pada Dashboard; `admin`, `operator_uptd`, `popt`, `pimpinan` pada WebGIS | Data masih mock/read model M3. |
| Read assigned case | `admin`, `popt` | Belum diimplementasikan; harus dibatasi assignment dari backend M2, bukan filter browser. |
| Update handling status/note | `admin`, `popt` | Belum diimplementasikan; M3 tidak mengubah status pada slice ini. |
| Create request | `admin`, `poktan` | Belum diimplementasikan; ownership harus berasal dari authenticated user. |
| Review/accept/reject/request-revision | `admin`, `operator_uptd` | Menunggu endpoint dan status contract M2. |
| Create assignment POPT | `admin`, `operator_uptd` | Menunggu assignment API dan aturan scope UPTD M2. |
| User management | `admin` | Route backend nested guard tetap aktif. |
| Knowledge CRUD | Sesuai route/request policy existing | Domain Knowledge tidak diubah oleh M3. |

M3 tidak boleh menerima `user_id`, role, ownership, assignment, atau scope wilayah dari input browser sebagai sumber otorisasi. Semua keputusan tersebut harus diverifikasi oleh backend/API yang memiliki data kasus.

## Dashboard Monitoring vs halaman kerja POPT

Dashboard Monitoring adalah read-only aggregation untuk `admin`, `operator_uptd`, dan `pimpinan`. Pimpinan tidak mendapatkan tombol perubahan status, assignment, catatan, approval, atau delete.

Halaman kerja POPT (`case-assigned`) adalah contract terpisah. POPT hanya boleh melihat kasus yang ditugaskan kepadanya dan memperbarui status/catatan melalui backend M2. Dashboard umum tidak boleh menjadi pengganti scope assignment tersebut.

## API authorization contract untuk M2

Hal-hal berikut wajib dikonfirmasi sebelum `ApiCaseProvider` atau provider write-side dihubungkan ke API nyata:

- role yang boleh membaca daftar dan detail kasus;
- role yang boleh mengubah `handling_status`/`status` dan catatan;
- role yang boleh membuat atau mengubah assignment;
- scope kasus POPT berdasarkan assignment;
- scope permohonan Poktan berdasarkan authenticated owner;
- scope wilayah Operator UPTD;
- apakah authorization menggabungkan role, ownership, assignment, wilayah, atau kombinasi;
- response `401` untuk unauthenticated dan `403` untuk unauthorized;
- pagination, filtering, dan policy saat data tidak boleh dikirim ke client.

M3 tidak boleh mengambil seluruh dataset lalu mengandalkan filter JavaScript sebagai security control. Filter provider hanya untuk presentasi; authorization dan data scoping harus terjadi di backend M2.

## Verifikasi yang tersedia

Test feature RBAC berada di `tests/Feature/WebGIS/MonitoringDashboardTest.php` dan `tests/Feature/WebGIS/WebGISTest.php`. Test tersebut mencakup guest redirect, role allowed/forbidden, direct URL guard, sidebar role visibility, dan read-only assertion pimpinan.

Perintah:

```bash
php artisan test --filter=MonitoringDashboardTest
php artisan test --filter=WebGISTest
npm run test:provider
```

Jika test feature terhenti sebelum assertion karena baseline migration Knowledge SQLite, kegagalan tersebut harus dilaporkan terpisah dari hasil audit RBAC dan tidak diperbaiki dalam slice M3 ini.
