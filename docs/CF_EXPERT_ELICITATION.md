# Penentuan Nilai CF SIPAKARBUN

## Konsep

Nilai `cf_pakar` pada hubungan gejala–penyakit disimpan sebagai angka yang dikonsumsi mesin diagnosis. Untuk aturan baru berbasis penilaian pakar, angka itu berasal dari metode elicitation yang terdokumentasi:

`Referensi/metode CF → pertanyaan elicitation → istilah keyakinan → pemetaan CF → rationale dan provenance → review Operator → publish → diagnosis`.

## CF Rule

`aturan_cf` tetap menyimpan `penyakit_id`, `gejala_id`, dan `cf_pakar`. Kolom tambahan menunjuk ke `cf_method_id`, menyimpan `expert_term`, `expert_rationale`, `expert_name`, `expert_institution`, dan `elicited_at`. Diagnosis tetap membaca `cf_pakar`; ia tidak membaca istilah linguistik secara langsung.

## Expert Elicitation

Pertanyaan disimpan pada master `cf_methods` dan dapat memakai `{gejala}` serta `{penyakit}`. SIPAKARBUN menampilkan pertanyaan setelah relasi dipilih. Penilai memilih tingkat dukungan positif gejala bagi penyakit, bukan tingkat keparahan gejala. Form Aturan CF memakai satu metode baku SIPAKARBUN dan tidak meminta pengguna memilih metode lain.

Pakar adalah sumber expert judgment. Satu pakar tidak disebut memvalidasi penilaiannya sendiri. Label yang digunakan adalah “Penilaian Pakar” atau “CF hasil elicitation pakar”.

## Linguistic Scale

Metode bawaan `Expert Elicitation – Linguistic CF Scale`, versi `1.0`, memakai skala yang diadopsi untuk SIPAKARBUN:

| Penilaian | CF |
| --- | ---: |
| Sangat Lemah | 0.2 |
| Lemah | 0.4 |
| Cukup Kuat | 0.6 |
| Kuat | 0.8 |
| Sangat Kuat | 1.0 |

Jika gejala netral atau tidak mendukung, hubungan penyakit–gejala tidak dibuat. Nilai 0 dan nilai negatif tidak tersedia untuk aturan elicitation baru. Nilai negatif pada data legacy tetap dipertahankan agar histori dan diagnosis lama tidak berubah.

## Conversion to Numeric CF

Saat `expert_term` tersedia, server mencari term pada `scale_definition` metode yang dipilih dan menulis hasil pemetaan ke `cf_pakar`. Nilai numerik dari browser hanya preview; nilai yang bertentangan tidak dipercaya. Versi metode disimpan agar perubahan skala dilakukan melalui versi baru, bukan mengubah arti histori.

## Method Reference

Master metode mempunyai judul, penulis, tahun, DOI/URL, dan status aktif. Metode baku SIPAKARBUN saat ini merujuk artikel “Sistem Pakar Deteksi Dini HIV/AIDS Dengan Metode Forward Chaining Dan Certainty Factor” (Pamungkas, Voutama, Sari, dan Susilawati, 2021), DOI 10.31539/intecoms.v4i1.2461, dengan URL https://journal.ipm2kpe.or.id/index.php/INTECOM/article/view/2461. Referensi metodologi ini berbeda dari referensi penyakit.

## Provenance

Aturan berbasis expert elicitation menyimpan metode baku, istilah keyakinan, CF hasil pemetaan, rationale per gejala, nama pakar penilai, instansi bila ada, dan tanggal elicitation. Beberapa gejala pendukung dapat disimpan dalam satu pengiriman, tetapi tetap menjadi rule terpisah di database. Sebelum aktif, metadata tersebut harus lengkap. Metode simulasi/UAT dan aturan legacy dipertahankan agar data demo serta histori tidak rusak.

## Why Expert Does Not Validate Himself

Pakar memberikan judgment. Operator memeriksa kelengkapan, konsistensi term dengan skala, rationale, dan provenance, lalu memublikasikan Knowledge. Operator tidak menyatakan bahwa judgment pakar benar secara ilmiah.

## Operator Review vs Expert Judgment

POPT dapat membuat dan mengedit draft. Operator UPTD atau Admin mengelola lifecycle publish. POPT tidak dapat publish. Tidak ada role baru bernama Pakar.

## Legacy/Simulation Data

Aturan lama tanpa `cf_method_id` tetap menampilkan `Legacy / Belum tercatat`, mempertahankan `cf_pakar`, dan tetap dapat digunakan bila statusnya aktif. Data demo diberi label `Simulation / UAT`; sistem tidak mengarang pakar, institusi, rationale, atau referensi.

## Example

Untuk penyakit “Karat Daun Kopi” dan gejala “Bercak jingga”, penilai menjawab pertanyaan elicitation dan memilih “Kuat”. Server menyimpan `expert_term = Kuat` dan `cf_pakar = 0.800`, bersama rationale, identitas penilai, tanggal, dan method versi 1.0.

## Future Multi-Expert Consensus

Model method dan provenance membuka ruang untuk menyimpan beberapa penilai dan mengagregasi consensus di masa depan. Weighting atau consensus multi-pakar belum diimplementasikan pada revisi ini.
