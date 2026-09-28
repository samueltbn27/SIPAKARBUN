# Penentuan Nilai CF SIPAKARBUN

## Konsep

Nilai `cf_pakar` pada hubungan gejala–penyakit disimpan sebagai angka yang dikonsumsi mesin diagnosis. Untuk aturan baru berbasis penilaian pakar, angka itu berasal dari metode elicitation yang terdokumentasi:

`Referensi/metode CF → pertanyaan elicitation → istilah keyakinan → pemetaan CF → rationale dan provenance → review Operator → publish → diagnosis`.

## CF Rule

`aturan_cf` tetap menyimpan `penyakit_id`, `gejala_id`, dan `cf_pakar`. Kolom tambahan menunjuk ke `cf_method_id`, menyimpan `expert_term`, `expert_rationale`, `expert_name`, `expert_institution`, dan `elicited_at`. Diagnosis tetap membaca `cf_pakar`; ia tidak membaca istilah linguistik secara langsung.

## Expert Elicitation

Pertanyaan disimpan pada master `cf_methods` dan dapat memakai `{gejala}` serta `{penyakit}`. SIPAKARBUN menampilkan pertanyaan setelah relasi dipilih. Penilai memilih tingkat keyakinan terhadap dukungan gejala bagi penyakit, bukan tingkat keparahan gejala.

Pakar adalah sumber expert judgment. Satu pakar tidak disebut memvalidasi penilaiannya sendiri. Label yang digunakan adalah “Penilaian Pakar” atau “CF hasil elicitation pakar”.

## Linguistic Scale

Metode bawaan `Expert Elicitation – Linguistic CF Scale`, versi `1.0`, memakai skala yang diadopsi untuk SIPAKARBUN:

| Penilaian | CF |
| --- | ---: |
| Pasti Tidak | -1.0 |
| Hampir Pasti Tidak | -0.8 |
| Kemungkinan Tidak | -0.6 |
| Mungkin Tidak | -0.4 |
| Tidak Tahu / Netral | 0.0 |
| Mungkin | 0.4 |
| Kemungkinan Besar | 0.6 |
| Hampir Pasti | 0.8 |
| Pasti | 1.0 |

Ini adalah konfigurasi metodologi yang diadopsi untuk penelitian dan sistem SIPAKARBUN, bukan klaim bahwa skala tersebut satu-satunya standar universal CF.

## Conversion to Numeric CF

Saat `expert_term` tersedia, server mencari term pada `scale_definition` metode yang dipilih dan menulis hasil pemetaan ke `cf_pakar`. Nilai numerik dari browser hanya preview; nilai yang bertentangan tidak dipercaya. Versi metode disimpan agar perubahan skala dilakukan melalui versi baru, bukan mengubah arti histori.

## Method Reference

Master metode mempunyai judul, penulis, tahun, DOI/URL, dan status aktif. Data bawaan sengaja tidak mengisi bibliografi karena referensi metodologi CF yang dipakai harus diverifikasi oleh pemilik penelitian terlebih dahulu. UI menampilkan “Belum tersedia” sampai data tersebut terverifikasi. Referensi metodologi ini berbeda dari referensi penyakit.

## Provenance

Aturan berbasis expert elicitation menyimpan metode, istilah keyakinan, CF hasil pemetaan, rationale, nama pakar penilai, instansi bila ada, dan tanggal elicitation. Draft boleh belum lengkap. Sebelum aktif, metadata tersebut harus lengkap. Metode simulasi/UAT dan aturan legacy dipertahankan agar data demo serta histori tidak rusak.

## Why Expert Does Not Validate Himself

Pakar memberikan judgment. Operator memeriksa kelengkapan, konsistensi term dengan skala, rationale, dan provenance, lalu memublikasikan Knowledge. Operator tidak menyatakan bahwa judgment pakar benar secara ilmiah.

## Operator Review vs Expert Judgment

POPT dapat membuat dan mengedit draft. Operator UPTD atau Admin mengelola lifecycle publish. POPT tidak dapat publish. Tidak ada role baru bernama Pakar.

## Legacy/Simulation Data

Aturan lama tanpa `cf_method_id` tetap menampilkan `Legacy / Belum tercatat`, mempertahankan `cf_pakar`, dan tetap dapat digunakan bila statusnya aktif. Data demo diberi label `Simulation / UAT`; sistem tidak mengarang pakar, institusi, rationale, atau referensi.

## Example

Untuk penyakit “Karat Daun Kopi” dan gejala “Bercak jingga”, penilai menjawab pertanyaan elicitation dan memilih “Hampir Pasti”. Server menyimpan `expert_term = Hampir Pasti` dan `cf_pakar = 0.800`, bersama rationale, identitas penilai, tanggal, dan method versi 1.0.

## Future Multi-Expert Consensus

Model method dan provenance membuka ruang untuk menyimpan beberapa penilai dan mengagregasi consensus di masa depan. Weighting atau consensus multi-pakar belum diimplementasikan pada revisi ini.
