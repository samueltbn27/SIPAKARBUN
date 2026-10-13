<?php

// Relative days are measured from the first seed. Stable IDs never encode a
// misleading fixed date. Existing scenarios are preserved on subsequent seeds.
return [
    ['id' => '001', 'scenario' => 'Permohonan kopi baru', 'group' => 'kopi', 'age' => 1, 'status' => 'diajukan'],
    ['id' => '002', 'scenario' => 'Permohonan kakao sedang diperiksa', 'group' => 'kakao', 'age' => 3, 'status' => 'sedang_direview'],
    ['id' => '003', 'scenario' => 'Permohonan kelapa menunggu keputusan', 'group' => 'kelapa', 'age' => 21, 'status' => 'diajukan'],

    ['id' => '004', 'scenario' => 'Permohonan kopi ditolak', 'group' => 'kopi', 'age' => 10, 'status' => 'ditolak',
        'reason' => 'Foto awal tidak memperlihatkan bagian daun yang dilaporkan. Lengkapi foto daun dan keterangan petak sebelum membuat pengajuan baru.'],
    ['id' => '005', 'scenario' => 'Permohonan cengkeh ditolak', 'group' => 'cengkeh', 'age' => 45, 'status' => 'ditolak',
        'reason' => 'Keterangan petak dan titik serangan belum sesuai. Konfirmasi lokasi kebun sebelum mengajukan penanganan kembali.'],
    ['id' => '006', 'scenario' => 'Permohonan kakao ditolak', 'group' => 'kakao', 'age' => 90, 'status' => 'ditolak',
        'reason' => 'Lokasi yang diajukan berada di luar cakupan layanan pada simulasi ini. Hubungi petugas wilayah yang sesuai.'],

    ['id' => '007', 'scenario' => 'Tugas kopi belum diterima POPT', 'group' => 'kopi', 'age' => 7, 'status' => 'diterima', 'case_status' => 'ditugaskan', 'deadline' => 5, 'popt' => 'popt'],
    ['id' => '008', 'scenario' => 'Tinjauan kelapa sebelum pekerjaan', 'group' => 'kelapa', 'age' => 12, 'status' => 'diterima', 'case_status' => 'sedang_direview', 'deadline' => 3, 'popt' => 'popt_dua'],
    ['id' => '009', 'scenario' => 'Tugas kakao belum dimulai melewati target', 'group' => 'kakao', 'age' => 20, 'status' => 'diterima', 'case_status' => 'ditugaskan', 'deadline' => -2, 'popt' => 'popt_dua'],
    ['id' => '010', 'scenario' => 'Tinjauan cengkeh melewati target', 'group' => 'cengkeh', 'age' => 10, 'status' => 'diterima', 'case_status' => 'sedang_direview', 'deadline' => -1, 'popt' => 'popt'],

    ['id' => '011', 'scenario' => 'Penanganan kopi sesuai target', 'group' => 'kopi', 'age' => 14, 'status' => 'diterima', 'case_status' => 'dalam_pelaksanaan', 'deadline' => 7, 'popt' => 'popt'],
    ['id' => '012', 'scenario' => 'Penanganan kakao melewati target', 'group' => 'kakao', 'age' => 35, 'status' => 'diterima', 'case_status' => 'dalam_pelaksanaan', 'deadline' => -3, 'popt' => 'popt_dua'],
    ['id' => '013', 'scenario' => 'Penanganan cengkeh dengan usulan perpanjangan', 'group' => 'cengkeh', 'age' => 21, 'status' => 'diterima', 'case_status' => 'dalam_pelaksanaan', 'deadline' => -2, 'popt' => 'popt',
        'extension' => 'pending', 'proposed_deadline' => 6, 'reason' => 'Akses petak terhambat hujan. Pemeriksaan lanjutan tetap berlangsung pada bagian kebun yang dapat dijangkau.'],
    ['id' => '014', 'scenario' => 'Penanganan kopi dengan perpanjangan disetujui', 'group' => 'kopi_garut', 'age' => 30, 'status' => 'diterima', 'case_status' => 'dalam_pelaksanaan', 'deadline' => -3, 'popt' => 'popt_dua',
        'extension' => 'approved', 'proposed_deadline' => 10, 'reason' => 'Diperlukan pemantauan tambahan pada daun baru sebelum mengevaluasi hasil sanitasi.'],
    ['id' => '015', 'scenario' => 'Penanganan kelapa dengan perpanjangan ditolak', 'group' => 'kelapa', 'age' => 24, 'status' => 'diterima', 'case_status' => 'dalam_pelaksanaan', 'deadline' => -1, 'popt' => 'popt',
        'extension' => 'rejected', 'proposed_deadline' => 8, 'reason' => 'Petugas mengusulkan waktu tambahan untuk kunjungan evaluasi kedua.'],
    ['id' => '016', 'scenario' => 'Pemantauan kakao sesuai target', 'group' => 'kakao', 'age' => 10, 'status' => 'diterima', 'case_status' => 'dalam_pelaksanaan', 'deadline' => 4, 'popt' => 'popt_dua'],

    ['id' => '017', 'scenario' => 'Kopi selesai sebelum target', 'group' => 'kopi', 'age' => 60, 'status' => 'diterima', 'case_status' => 'selesai', 'late_completion' => false, 'popt' => 'popt'],
    ['id' => '018', 'scenario' => 'Kelapa selesai sesudah target', 'group' => 'kelapa', 'age' => 100, 'status' => 'diterima', 'case_status' => 'selesai', 'late_completion' => true, 'popt' => 'popt_dua'],
    ['id' => '019', 'scenario' => 'Cengkeh selesai sebelum target', 'group' => 'cengkeh', 'age' => 150, 'status' => 'diterima', 'case_status' => 'selesai', 'late_completion' => false, 'popt' => 'popt'],
    ['id' => '020', 'scenario' => 'Kopi Garut selesai sesudah target', 'group' => 'kopi_garut', 'age' => 210, 'status' => 'diterima', 'case_status' => 'selesai', 'late_completion' => true, 'popt' => 'popt_dua'],
];
