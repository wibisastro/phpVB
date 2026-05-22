# Gov3.id

**Government 3.0 — Architectural Reference Model untuk Era AI**

*Inisiatif riset eGov Lab Universitas Indonesia bersama Cyber Gov Labs*

---

## Ini Portal Apa

Gov3.id adalah ruang riset terbuka untuk menguji **Government 3.0** — paradigma tata kelola pemerintahan digital generasi berikutnya yang berfokus bukan pada konektivitas sistem, tetapi pada **orkestrasi penalaran institusional**.

Halaman depan portal ini sengaja dibuka untuk publik: siapa pun bisa membaca manifesto, mencoba endpoint sandbox, dan melihat operasional nyata Governance Layer berjalan. Lihat [`/sandbox`](/sandbox) untuk arsitektur teknis platform.

---

## Pertanyaan yang Sudah Berubah

Selama satu generasi, pertanyaan tata kelola digital adalah: *"Bagaimana kita menghubungkan sistem?"*

Pertanyaan itu sudah terjawab — sebagian. Platform tersedia. Digital Public Infrastructure dibangun. Data mengalir lintas-lembaga. Namun keberhasilannya masih jauh dari harapan: ribuan aplikasi pemerintah tetap berdiri dalam silo, dan berbagai inisiatif percepatan nasional diperkenalkan tanpa dampak yang signifikan.

Hipotesis sementara: belum ada ekosistem *data exchange* yang benar-benar mudah dijalankan. Salah satu faktornya adalah **jurang bahasa** — bahasa teknis *data exchange* tidak mudah diterjemahkan ke dalam bahasa *governance*, padahal *data exchange layer* membutuhkan *data governance* sebagai pondasi.

Tetapi jurang bahasa hanyalah separuh cerita. Faktor kedua yang sama beratnya adalah **resistensi birokrasi bermuatan politis** — kepentingan unit kerja, ikatan kontrak vendor, kewenangan anggaran — yang justru *memanfaatkan* jurang bahasa ini sebagai amunisi untuk mempertahankan status quo. Selama *data exchange* terdengar sebagai jargon teknis yang abstrak, urusan itu mudah diparkir; selama *data governance* terdengar sebagai beban regulasi tambahan, urusan itu mudah ditunda.

Kedua faktor ini saling memperkuat: jurang bahasa menyediakan alasan teknis, resistensi menyediakan motif politis. Adopsi melemah persis di persimpangan keduanya.

Era AI membuka peluang baru: nalar *governance* kini bisa diterjemahkan ke bahasa teknis *data exchange* dengan jauh lebih mudah. Namun faktanya, proses bernalar yang dibantu AI ini pun terjadi dalam silo. Perangkat AI dipakai — tapi terpisah-pisah. Asumsi analitik tidak terlihat lintas batas organisasi. Output kebijakan saling bertentangan di tempat yang seharusnya selaras.

Pertanyaannya kini: ***Bagaimana kita mengorkestrasi penalaran institusional?***

Inilah pertanyaan yang dijawab Gov3.id.

---

## Diagnosis: Fragmentasi Intelligence

Pemerintah Indonesia sedang mengakselerasi adopsi AI di seluruh K/L. Tanpa arsitektur koordinasi, akselerasi ini justru **memperlebar fragmentasi**:

- Setiap K/L melatih model pada domain yang tumpang tindih
- Instansi menerapkan kriteria berbeda pada indikator yang sama
- Reasoning tertanam implisit dalam model AI — tidak terdokumentasi, tidak bisa dibagikan
- Sinyal kebijakan saling bertentangan di tempat yang seharusnya selaras

**Hasilnya:** institusi yang makin kuat secara analitik, namun secara kolektif makin sulit berkoordinasi.

Ini bukan kegagalan teknologi. Ini **kegagalan arsitektur**.

---

## Tiga Lapisan Tata Kelola Digital

Government 3.0 mengusulkan satu elemen arsitektural yang hilang: **Governance Layer** — lapisan di atas Digital Public Infrastructure yang mengorkestrasi reasoning lintas institusi.

| Lapisan | Tahap | Fungsi | Status di Indonesia |
|---|---|---|---|
| Infrastructure Layer | Gov 1.0 | Digitalisasi administratif | Sudah ada |
| Platform Layer | Gov 2.0 | Konektivitas & interoperabilitas (DPI) | Sedang dibangun |
| **Governance Layer** | **Gov 3.0** | **Orkestrasi reasoning lintas institusi** | **Lapisan yang hilang** |

Governance Layer **tidak menggantikan** apa yang sudah ada. Ia mengorganisasi reasoning di atasnya. Otoritas manusia tetap di puncak — AI augmentatif, bukan otoritatif.

---

## Empat Pilar Orchestrated Intelligence

Yang diuji di sandbox Gov3.id adalah empat pilar kecerdasan terorkestrasi:

1. **Traceable Reasoning** — setiap analytical pathway terekam dan bisa diaudit, dari input data hingga rekomendasi AI hingga keputusan manusia
2. **Shared Criteria** — instansi menggunakan definisi dan indikator konsisten melalui *criteria catalog*; analisis lintas instansi menjadi *comparable*
3. **Structured Human-AI Mediation** — approval gate, override registry, confidence threshold memastikan otoritas manusia substantif, bukan formalitas
4. **Persistent Institutional Memory** — Policy Decision Records bertahan lintas siklus kebijakan dan pergantian kepemimpinan

---

## Bukan Mandat, Melainkan Undangan

Gov3 bukan regulasi top-down. Bukan SPBE versi baru. Bukan arahan kepatuhan.

Gov3 adalah **undangan untuk bereksperimen bersama** — dijalankan melalui pendekatan sandbox yang:

- **Tidak mengganggu** — paralel dengan `*.go.id`, tidak ada yang diminta meninggalkan sistem lama
- **Tidak memaksa** — bergabung sukarela, tanpa seleksi, semudah mendaftar layanan cloud
- **Tidak mengancam** — output sandbox bersifat referensi, bukan pengganti
- **Membuktikan, bukan menjanjikan** — operasional nyata yang hasilnya bisa dilihat dan diukur

Yang di-graduate adalah **hasilnya**, bukan sandbox-nya. Ruang eksperimen tetap hidup sebagai wadah inovasi berkelanjutan.

---

## Cara Instansi Pemerintah Bergabung

Onboarding dirancang serupa pendaftaran layanan cloud — tanpa proses seleksi, tanpa MoU formal di awal:

1. **Ajukan permohonan onboarding** — kirim email ke kontak di bawah dengan: nama instansi, kode wilayah/K/L, dan satu liaison teknis (boleh ASN, boleh tenaga ahli)
2. **Verifikasi instansi** — Tim Gov3 memvalidasi bahwa permohonan berasal dari unit kerja resmi (cek SOTK / surat penunjukan liaison)
3. **Subdomain aktif** — dalam 1×24 jam, instansi mendapat `[instansi].gov3.id` lengkap dengan endpoint `mcp.`, `dav.`, `auth.`, `sql.`, `kms.`, `pnbp.` (lihat [`/sandbox`](/sandbox) untuk detail prefix)
4. **Onboarding kit** — akses ke dokumentasi, criteria catalog, workflow template, dan jalur dukungan teknis (chat Merpati)
5. **Eksperimen layanan pertama** — biasanya SAKIP AI sebagai entry point, atau layanan lain yang relevan dengan tugas pokok instansi
6. **Maturity tracking dimulai** — posisi instansi di **Gov 3 Maturity Index** (Gov 3.1 — eksperimen awal hingga Gov 3.9 — institusionalisasi normatif) dipantau berbasis evidence, bukan klaim

Tidak ada biaya selama fase sandbox. Tidak ada kewajiban graduasi pada timeline tertentu. Instansi bergerak sesuai kesiapannya sendiri.

---

## Siapa yang Menggunakan

| Pemangku | Peran |
|---|---|
| **Kementerian / Lembaga** | Menguji proses bisnis lintas-K/L (mis. SAKIP AI bersama KemenPAN-RB) |
| **Pemerintah daerah** | Onboarding mandiri tanpa dependensi nasional; eksperimen layanan daerah |
| **Unit kerja teknis** | Domain expert + AI orchestrator — bukan lagi operator + developer |
| **Akademisi** | Riset empiris tentang efektivitas Governance Layer pada konteks Indonesia |
| **CSO & vendor** | Berpartisipasi di MCP federation dengan TLD role berbeda (`.or.id`, `.co.id`) |

---

## Kelayakan Sebagai Riset eGov Lab UI

Gov3.id memenuhi kriteria riset ilmiah kelas universitas:

- **Pertanyaan riset yang belum terjawab** — apakah orchestrated intelligence bisa dioperasionalkan pada konteks tata kelola Indonesia? Tidak ada bukti empiris global yang menjawab ini.
- **Metode yang bisa direplikasi** — sandbox sebagai eksperimen terkendali, dengan evidence-based assessment (log platform, dokumen formal, observasi reasoning quality).
- **Output ilmiah yang konkret** — whitepaper, arsitektur referensi, maturity index, policy brief, dan paper turunan dari setiap layer eksperimen.
- **Relevansi kebijakan langsung** — temuan dapat masuk ke turunan Perpres 83/2025 tentang Pemerintahan Digital.
- **Kemitraan institusional** — KemenPAN-RB (SAKIP AI), CSGAR (cyber governance), instansi peserta sebagai co-researcher.

Riset ini bersifat **architectural**, bukan teknologikal. Yang diuji bukan apakah AI bisa membantu pemerintah — itu sudah jelas. Yang diuji adalah **arsitektur mediasi** yang mengizinkan kecerdasan tertib lintas batas institusi.

---

## Dokumen Sumber

Halaman ini meringkas konsep yang dirumuskan dalam lima dokumen referensi:

| Dokumen | Fungsi |
|---|---|
| **Manifesto Gov3** | Pernyataan publik tentang pergeseran paradigma — *kenapa* Gov3 ada |
| **Whitepaper Gov3** | Argumentasi teknis-konseptual lengkap, ~70 halaman |
| **Arsitektur Gov3 v2** | Spesifikasi teknis: protocol prefix, MCP federation, TLD role convention |
| **Gov 3 Maturity Index** | Sembilan level kematangan (Gov 3.1 – Gov 3.9) + mekanisme assessment |
| **Policy Brief** | Ringkasan untuk pembuat kebijakan — masalah, diagnosis, yang diminta |

Dokumen-dokumen ini tersedia untuk diskusi teknis lebih dalam — silakan hubungi tim riset.

---

## Kontak

**Lab e-Gov & e-Bus Universitas Indonesia · Cyber Gov Labs**

Wibisono Sastrodiwiryo — wibi@alumni.ui.ac.id

---

> *Infrastructure provides connection. Platform architecture enables integration. The Governance Layer ensures coherence.*
