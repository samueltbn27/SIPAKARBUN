# Panduan Validasi Knowledge CF

## Tujuan

Panduan ini menetapkan cara mendokumentasikan asal-usul dan penilaian nilai Certainty Factor (CF) pada aturan Knowledge SIPAKARBUN. Tujuannya agar setiap nilai dapat ditelusuri, ditinjau, dan dibedakan dengan jelas antara data simulasi, data yang masih disusun, dan data yang benar-benar telah divalidasi. Panduan ini tidak menetapkan nilai CF untuk penyakit atau gejala tertentu.

## Bedakan keparahan gejala dan CF

Keparahan atau luas gejala adalah pengamatan kondisi tanaman, sedangkan CF adalah nilai keyakinan/dukungan evidensial untuk suatu hubungan atau aturan diagnosis. Keduanya bukan ukuran yang dapat saling menggantikan.

Contoh: **40% luas daun bergejala tidak berarti CF = 0,40.** Persentase luas adalah ukuran observasi dengan definisi dan cara ukur tersendiri. CF harus ditentukan melalui metode yang dinyatakan, berdasarkan sumber/bukti yang dapat ditelusuri serta penilaian yang terdokumentasi. Jangan mengonversi persentase area, jumlah gejala, atau tingkat keparahan menjadi CF secara langsung tanpa metode yang secara eksplisit dibenarkan dan divalidasi.

## Kriteria operasional gejala

Sebelum aturan dan CF dinilai, definisikan gejala agar pencatatan dan validasi merujuk pada hal yang sama. Untuk setiap gejala, dokumentasikan:

- nama/label gejala dan bagian tanaman yang diamati;
- deskripsi ciri yang dapat diamati, termasuk bentuk, warna, pola, atau tekstur jika relevan;
- batas inklusi dan eksklusi, termasuk kondisi yang mudah tertukar;
- cara dan waktu pengamatan, unit pengamatan, serta skala/ambang jika digunakan;
- cara mencatat tingkat keparahan atau proporsi area, jika memang dibutuhkan sebagai atribut observasi;
- kebutuhan dokumentasi pendukung dan kondisi ketika observasi dianggap tidak cukup.

Gunakan definisi dan instruksi ukur yang sama pada kasus yang dibandingkan. Jangan mencampur label seperti “ringan/sedang/berat” dengan nilai CF. Jika penilaian tingkat keparahan digunakan, simpan sebagai hasil observasi terpisah beserta definisinya.

## Provenance dan sumber terkendali

Setiap aturan Knowledge dan nilai CF harus mempunyai provenance yang dapat diperiksa. Catat setidaknya identitas aturan/relasi, versi, komoditas, penyakit, gejala, nilai CF, tanggal pencatatan, metode penetapan, justifikasi, status validasi, dan pihak yang mengusulkan serta memvalidasi. Catat sumber secara spesifik: judul atau nama dokumen/data, penerbit atau pemilik jika diketahui, tanggal/versi, halaman atau bagian, lokasi/ID rekaman, URL atau pengenal arsip bila tersedia, tanggal akses, serta batasan penggunaan. Jangan mengisi detail yang tidak diketahui dengan dugaan.

Sumber harus dikendalikan dan ditinjau sebelum dipakai. Kategori yang dapat dipertimbangkan, sesuai ketersediaan dan kewenangan, antara lain:

- publikasi atau pedoman teknis yang dapat diidentifikasi;
- catatan observasi lapangan dengan lokasi/konteks, tanggal, metode, dan identitas kasus yang memadai;
- hasil pemeriksaan atau pengujian yang memiliki rekaman dan interpretasi yang dapat ditelusuri;
- dataset terkurasi dengan definisi label dan proses pengumpulan yang terdokumentasi;
- penilaian ahli melalui proses elicitation yang dicatat.

Kategori di atas bukan klaim bahwa sumber tertentu telah tersedia atau digunakan. Catat relevansi dan keterbatasan setiap sumber; satu sumber tidak otomatis membuktikan nilai CF. Tandai data simulasi, data contoh, dan fixture UAT sebagai bukan bukti agronomis atau hasil validasi.

## Elicitation pakar

Elicitation digunakan hanya jika penilaian pakar memang menjadi dasar nilai. Catat identitas/role validator sesuai persetujuan dan kebijakan privasi, kompetensi yang relevan, tanggal, konteks, bahan yang ditinjau, pertanyaan yang diajukan, respons/penilaian, alasan, ketidakpastian, serta perbedaan pendapat dan cara penyelesaiannya. Simpan rekaman atau ringkasan yang dapat diaudit sesuai izin.

Jangan menganggap kata verbal seperti “mungkin”, “sering”, atau “kuat” memiliki konversi CF universal. Jika skala atau elicitation terstruktur digunakan, dokumentasikan definisi skala, instruksi, cara agregasi, dan alasan metodologisnya; skala tersebut perlu ditinjau dan divalidasi untuk penggunaan yang dimaksud. Jangan menyajikan angka hasil konversi seolah-olah baku tanpa dasar yang terdokumentasi.

## Alur draft, published, dan validated

1. **Draft** — Aturan disusun dengan definisi gejala, nilai/metode sementara, provenance, dan penanda keterbatasan. Nilai belum boleh dinyatakan tervalidasi. Kekurangan informasi harus tetap terlihat.
2. **Review/published** — Penanggung jawab meninjau kelengkapan, konsistensi, sumber, metode, dan justifikasi. Aturan dapat diterbitkan untuk penggunaan yang telah disetujui, tetapi status published hanya menyatakan status publikasi/operasional; itu sendiri bukan bukti validasi. Batas penggunaan dan versi harus dicatat.
3. **Validated** — Validator yang berwenang menilai bukti dan metode untuk ruang lingkup penggunaan yang dinyatakan. Simpan identitas/role validator, tanggal, hasil, alasan, batasan, dan versi yang dinilai. Status ini hanya digunakan bila proses validasi benar-benar dilakukan dan bukti pendukung tercatat.
4. **Perubahan dan peninjauan ulang** — Perubahan sumber, definisi gejala, metode, nilai, atau ruang lingkup memicu versi/revisi dan penilaian apakah validasi ulang diperlukan. Pertahankan riwayat status, bukan menimpa provenance sebelumnya.

Gunakan status yang tersedia di aplikasi bila berbeda, tetapi dokumentasikan pemetaan maknanya. Jangan mengubah label status aplikasi untuk menyiratkan validasi yang tidak terjadi.

## Disclaimer data demo

Nilai CF dan aturan Knowledge pada dataset demo SIPAKARBUN adalah **Simulation/UAT** dan merupakan fixture simulasi, bukan validasi pakar lapangan. Data tersebut ditujukan untuk pengujian alur aplikasi. Jangan mengutipnya sebagai rekomendasi diagnosis, bukti ilmiah, atau nilai tervalidasi. Hanya tandai aturan sebagai validated setelah proses dan rekaman validasi yang nyata selesai.

## Formulir pencatatan dan validasi

Isi satu formulir untuk setiap aturan/versi yang dinilai. Placeholder dalam tanda kurung siku harus diganti dengan informasi yang benar; bila belum diketahui, tulis “belum tersedia” dan jangan mengarang. Contoh struktur berikut adalah format, bukan data atau referensi faktual.

### Form kosong

| Bidang | Isian |
|---|---|
| ID aturan / versi | [isi] |
| Komoditas | [isi] |
| Penyakit atau kesimpulan aturan | [isi] |
| Gejala/relasi yang dinilai | [isi] |
| Definisi operasional gejala | [bagian tanaman, ciri, inklusi/eksklusi, cara dan waktu pengamatan] |
| Nilai CF dan rentang yang berlaku | [isi; jangan turunkan langsung dari keparahan/luas gejala] |
| Jenis nilai | [simulasi / sementara / hasil metode terdokumentasi / lainnya] |
| Sumber dan provenance | [judul/ID, penerbit/pemilik jika diketahui, versi/tanggal, halaman/rekaman, URL/arsip, tanggal akses] |
| Metode penetapan CF | [metode, skala/instruksi, agregasi jika ada, alasan pemilihan] |
| Justifikasi | [bagaimana sumber/bukti mendukung relasi dan nilai; ketidakpastian/batasan] |
| Elicitation pakar (jika digunakan) | [identitas/role sesuai izin, kompetensi relevan, tanggal, pertanyaan, respons, alasan, perbedaan pendapat] |
| Pengusul dan tanggal | [isi] |
| Validator dan tanggal | [isi atau belum tersedia] |
| Ruang lingkup dan batas penggunaan | [isi] |
| Status dan alasan status | [draft / published / validated / status aplikasi; alasan] |
| Bukti/rekaman pendukung | [ID atau lokasi arsip yang berwenang] |
| Tindakan lanjutan / tanggal tinjau ulang | [isi] |

### Contoh struktur isian (placeholder, bukan validasi nyata)

- ID aturan/versi: `[ID aturan] / [versi]`
- Relasi: `[komoditas] — [gejala terdefinisi] → [penyakit/kesimpulan]`
- CF: `[nilai yang sedang dinilai]`; metode: `[nama/deskripsi metode dan versinya]`
- Provenance: `[sumber yang dapat ditelusuri, bagian/rekaman, versi/tanggal, akses]`
- Justifikasi dan keterbatasan: `[ringkasan bukti, alasan nilai, ketidakpastian]`
- Validator: `[identitas/role sesuai izin]`; tanggal: `[tanggal]`; hasil: `[diterima / perlu revisi / ditolak]`
- Status: `[draft / published / validated]`; dasar perubahan status: `[rekaman keputusan]`

Selama elemen penting belum lengkap atau validasi belum dilakukan, gunakan status dan disclaimer yang tidak menyiratkan bahwa nilai telah divalidasi.
