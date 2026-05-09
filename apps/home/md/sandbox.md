# Gov3.id

**Sandbox Tata Kelola Pemerintahan Digital Indonesia**

*Riset eGov Lab UI bersama Cyber Gov Labs*

---

## Apa itu Gov3.id?

Gov3.id adalah **sandbox tata kelola pemerintahan digital** yang terbuka bagi seluruh instansi pemerintah di Indonesia — dari kementerian, pemerintah provinsi, kabupaten/kota, hingga unit kerja teknis. Berbeda dari sandbox konvensional yang hanya menguji aplikasi dalam lingkungan terisolasi, Gov3.id menguji **keseluruhan ekosistem tata kelola**: regulasi, struktur organisasi, model interoperabilitas, dan pola penganggaran.

Platform ini berjalan **paralel** dengan infrastruktur `*.go.id` existing — bukan menggantikannya. Setiap instansi tetap menjalankan sistem resminya, sementara Gov3.id menyediakan ruang eksperimen untuk membuktikan ada cara yang lebih baik.

---

## Pergeseran Paradigma: Dari Domain Aplikasi ke Domain Instansi

Selama dua dekade pembangunan e-government, Indonesia menjalankan model "satu kebutuhan — satu aplikasi — satu domain". Hasilnya: ratusan silo digital, biaya integrasi yang berlipat, dan pemeliharaan yang mahal.

Gov3.id mengusulkan pergeseran fundamental:

| Aspek | Gov 2.0 (Sekarang) | Gov 3.0 (Sandbox) |
|---|---|---|
| Unit arsitektur | Aplikasi | **Instansi** |
| Integrasi | API point-to-point | **Federated by default** (MCP) |
| Pembangunan | Proyek per kebutuhan | **Konfigurasi on-demand** |
| Peran ASN | Operator + developer | **Domain expert + AI orchestrator** |

Dengan AI sebagai orchestrator, layanan publik tidak lagi *dibangun*, melainkan *dideskripsikan*. Yang menjadi unit utama bukan lagi aplikasi — melainkan instansi pemerintah itu sendiri.

---

## Open Onboarding — Terbuka untuk Seluruh Indonesia

Setiap instansi pemerintah dapat bergabung **tanpa proses seleksi** — semudah mendaftar layanan cloud. Tidak ada *barrier to entry* selain kesediaan untuk bereksperimen.

Setiap instansi yang onboard mendapatkan subdomain di bawah `gov3.id` dengan kapabilitas standar yang otomatis tersedia:

| Subdomain | Fungsi |
|---|---|
| `[instansi].gov3.id` | Portal utama instansi |
| `mcp.[instansi].gov3.id` | Endpoint AI interoperability (MCP) |
| `dav.[instansi].gov3.id` | Federated document storage |
| `auth.[instansi].gov3.id` | Identity & authentication |
| `sql.[instansi].gov3.id` | Structured data endpoint |
| `msg.[instansi].gov3.id` | Messaging antar-instansi |

Tidak diperlukan pengembangan terpisah untuk masing-masing fungsi.

---

## Komponen Platform

Gov3.id dibangun di atas komponen open-source teruji, dinamai dengan fauna Indonesia:

| Komponen | Nama | Fungsi |
|---|---|---|
| Discovery | **Gurita** | Service discovery antar-instansi (MCP Registry) |
| Storage | **Kambing** | Federated document storage (WebDAV) |
| Auth | **Walet** | Single identity federation (OAuth2/SAML) |
| Audit | **Gajah** | Immutable audit trail |
| Messaging | **Merpati** | Komunikasi antar-instansi |
| Knowledge | **Lebah** | Basis pengetahuan institusional |
| Geospatial | **Penyu** | Layanan data geospasial (PostGIS) |
| Billing | **Semut** | Usage metering & PNBP |
| AI Node | **Per-instansi LLM** | Orkestrasi AI per instansi |

---

## Lifecycle: Open Entry, Gradual Graduation

Gov3.id menggunakan model adopsi bertahap — bukan *all-or-nothing*:

1. **Onboarding** — instansi bergabung tanpa seleksi
2. **Eksperimen** — bebas menguji proses bisnis dan model organisasi baru
3. **Shadow Integration** — output sandbox menjadi *input referensi* bagi proses resmi
4. **Adopsi Parsial** — komponen yang terbukti efektif diadopsi secara resmi
5. **Adopsi Penyelenggara** — model atau standar di-graduate ke tata kelola formal

Sandbox tetap hidup sebagai ruang inovasi permanen — yang di-graduate adalah **hasilnya**, bukan sandbox-nya.

---

## Momentum: SAKIP AI

Titik masuk pertama Gov3.id adalah kolaborasi dengan **KemenPAN-RB** dalam implementasi **SAKIP AI** — sistem analisis dokumen kinerja berbasis AI. Instansi terpilih KemenPAN-RB mengakses SAKIP AI melalui platform Gov3.id, sekaligus membuka pintu bagi instansi lain untuk onboarding secara mandiri.

---

## Riset

Gov3.id adalah **riset eGov Lab Universitas Indonesia bersama Cyber Gov Labs** untuk menguji apakah Indonesia siap bertransisi dari Government 2.0 (layanan online terfragmentasi) ke Government 3.0 (*orchestrated intelligence*).

Platform ini menjalankan teknologi **phpVB** sebagai kerangka aplikasi — menyediakan multi-domain, multi-staging, dan pemisahan kerangka-isi yang dibutuhkan untuk melayani ratusan instansi dari satu basis kode.

> Dokumen konsep lengkap: *Gov3.id — Sandbox Tata Kelola Pemerintahan Digital v1.2 (April 2026)*

---

## Kontak

eGov Lab Universitas Indonesia · Cyber Gov Labs

Wibisono Sastrodiwiryo — wibi@alumni.ui.ac.id
