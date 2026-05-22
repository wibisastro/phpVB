# Sandbox Gov3.id

**Arsitektur Teknis Platform**

> Halaman ini berisi spesifikasi teknis sandbox. Untuk konsep besar Gov3 — kenapa portal ini ada, siapa yang pakai, cara bergabung — lihat [`/`](/).

---

## Apa yang Diuji di Sini

Berbeda dari regulatory sandbox konvensional yang hanya menguji aplikasi dalam environment terisolasi, Gov3.id menguji **keseluruhan ekosistem tata kelola**:

- Regulasi (apa yang perlu Permen/Perpres, apa yang bisa SK)
- Struktur organisasi (siapa pegang criteria catalog, siapa approval gate)
- Model interoperabilitas (MCP federation, Walet identity, Kambing storage)
- Pola penganggaran (PNBP, hibah, cost-sharing antar-instansi)
- Mekanisme mediasi human-AI (approval gate, override registry, confidence threshold)

Yang diuji adalah **arsitektur**, bukan teknologi tunggal.

---

## Prinsip Desain

1. **Paralel, bukan pengganti** — berjalan bersamaan dengan `*.go.id` existing
2. **Open onboarding** — tanpa seleksi, semudah mendaftar layanan cloud
3. **Domain instansi sebagai unit utama** — bukan domain aplikasi
4. **Convention over configuration** — kapabilitas otomatis via protocol prefix terstandar
5. **AI-native** — setiap endpoint dapat diorkestrasi AI via MCP
6. **Human authority at the apex** — semua output AI rekomendatif; otoritas final di pejabat berwenang
7. **Sandbox mencakup governance** — bukan hanya teknologi
8. **Graduasi bertahap** — tidak all-or-nothing

---

## Struktur Domain

Setiap instansi yang onboard mendapatkan subdomain dengan protocol prefix terstandar:

| Prefix | Contoh | Fungsi | Komponen Fauna |
|---|---|---|---|
| `[instansi].gov3.id` | `bekasikab.gov3.id` | Portal utama instansi | — |
| `mcp.[instansi].gov3.id` | `mcp.bekasikab.gov3.id` | AI interoperability endpoint | MCP Node |
| `dav.[instansi].gov3.id` | `dav.bekasikab.gov3.id` | Federated document storage (WebDAV) | Kambing |
| `auth.[instansi].gov3.id` | `auth.bekasikab.gov3.id` | Identity & authentication | Walet |
| `sql.[instansi].gov3.id` | `sql.bekasikab.gov3.id` | Database platform (Supabase) | Gajah |
| `kms.[instansi].gov3.id` | `kms.bekasikab.gov3.id` | Knowledge base & institutional memory | Lebah |
| `pnbp.[instansi].gov3.id` | `pnbp.bekasikab.gov3.id` | Billing & cost allocation | Semut |

Komponen pusat tanpa prefix per-instansi: `gurita.gov3.id` (registry), `merpati.gov3.id` (messaging), `penyu.gov3.id` (geospatial).

---

## Gov3.id sebagai Bridging TLD

Arsitektur Gov3 v2 menetapkan **TLD sebagai role convention** untuk production:

| Sandbox (Gov3.id) | Production | TLD Role |
|---|---|---|
| `mcp.kukarkab.gov3.id` | `mcp.kukarkab.go.id` | Pemerintah |
| `mcp.cybergl.gov3.id` | `mcp.cybergl.co.id` | Vendor/Swasta |
| `mcp.csgar.gov3.id` | `mcp.csgar.ui.ac.id` | Akademik |
| `mcp.icw.gov3.id` | `mcp.icw.or.id` | CSO/Organisasi |

Selama di sandbox, role ditandai via metadata onboarding. Saat graduasi, TLD asli instansi mengambil alih fungsi role convention — tanpa perubahan arsitektur, hanya perpindahan domain.

---

## Komponen Platform

Dibangun di atas komponen open-source teruji, dinamai dengan fauna Indonesia:

| Komponen | Nama | Basis Teknologi | Fungsi |
|---|---|---|---|
| Discovery & Registry | **Gurita** | MCP Registry + Criteria Catalog | Service discovery, LLM registry, shared criteria |
| Storage | **Kambing** | WebDAV / OCM | Federated document storage |
| Auth | **Beo** | OAuth2 / JWT | Single identity federation, drilldown level |
| Database | **Gajah** | Supabase (PostgreSQL + Realtime) | Database, audit trail, override registry |
| Messaging | **Merpati** | Chat + Conversational flow | Notifikasi, mediation delivery, eskalasi |
| Knowledge | **Lebah** | Project mgmt + Git | Knowledge base, Policy Decision Records |
| Billing | **Semut** | Usage metering | PNBP & cost allocation |
| Geospatial | **Penyu** | PostGIS + KML | Geospatial data services |
| AI Node | per-instansi | MCP + RAG + LLM | AI orchestration per instansi |

Seluruh kapabilitas ini tersedia **otomatis** saat instansi onboard — tidak diperlukan pengembangan terpisah.

---

## Lifecycle: Open Entry, Gradual Graduation

| Tahap | Status | Output |
|---|---|---|
| **1. Onboarding** | Subdomain aktif | Akses penuh ke semua komponen platform |
| **2. Eksperimen** | Pure sandbox | Output eksperimental, no implikasi hukum |
| **3. Shadow Integration** | Hybrid informal | Output sandbox = referensi informal di proses resmi |
| **4. Adopsi Parsial** | Hybrid formal | SK/nota dinas mengakui output sandbox sebagai input resmi |
| **5. Graduasi Platform** | Production penuh | Migrasi ke TLD asli instansi (`.go.id` dsb) |

Sandbox **tidak mati** setelah graduasi — yang di-graduate adalah hasilnya, bukan sandbox-nya. Ruang eksperimen tetap hidup untuk inovasi berkelanjutan.

Perjalanan diukur via **Gov 3 Maturity Index** (Gov 3.1 – Gov 3.9). Detail di dokumen Maturity Index.

---

## Orchestrated Intelligence di Sandbox

Empat pilar yang dioperasionalkan di lapisan teknis:

- **Mediation Protocol** — approval gate via Merpati, override registry di Gajah, confidence threshold per layanan
- **Criteria Catalog** — definisi indikator bersama di Gurita; instansi pull/push criteria
- **Institutional Memory** — Policy Decision Records di Lebah, di-versioning seperti aset
- **Traceable Reasoning** — analytical pathway log end-to-end di Gajah audit trail

---

## Guardrails

Sandbox bukan tanpa aturan. Batas yang tidak boleh dilanggar:

- Tidak boleh mengurangi hak akses layanan publik
- Standar keamanan informasi tidak boleh di bawah baseline SPBE
- Harus ada rollback plan — eksperimen yang gagal dapat dikembalikan
- Data tetap comply dengan UU Perlindungan Data Pribadi
- Transparansi penuh — semua keputusan governance terdokumentasi
- Pada tahap shadow integration ke atas, output sandbox **wajib diberi label** untuk membedakan dari output resmi
- **Human authority** — semua output AI rekomendatif; keputusan final di pejabat berwenang

### AI Safeguards Framework

- **Non-discrimination check** — workflow template memvalidasi output AI terhadap indikator fairness
- **Purpose limitation** — AI hanya mengakses data sesuai scope yang di-otorisasi Walet
- **AI block classification** — output yang menyentuh domain sensitif (keamanan nasional, data personal) otomatis di-block atau di-eskalasi dengan threshold lebih ketat

---

## Teknologi Kerangka

Sandbox dijalankan di atas **phpVB** — framework PHP + Vue + Bootstrap yang menyediakan multi-domain, multi-staging, dan pemisahan kerangka-isi yang dibutuhkan untuk melayani ratusan instansi dari satu basis kode. Setiap subdomain instansi otomatis ter-routing ke konfigurasi dan database terpisah.

---

## Kontak Teknis

**Lab e-Gov & e-Bus Universitas Indonesia · Cyber Gov Labs**

Wibisono Sastrodiwiryo — wibi@alumni.ui.ac.id

Untuk pertanyaan teknis platform: liaison teknis instansi dapat membuka issue di Lebah setelah onboarding.
