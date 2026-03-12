# Arsitektur Gov2AI — Layanan Analisis Dokumen AI untuk ASN

## Context

PNS/ASN butuh tools untuk analisis dokumen (ringkasan, ekstraksi data, analisis kebijakan). Dibangun di atas phpVB (PHP 8.4) yang sudah ada — memanfaatkan SSO, auth, deploy workflow yang sudah jalan. Backend AI menggunakan Claude API via Guzzle (sudah ada di composer).

---

## Arsitektur Overview

```
PNS/ASN → Browser → phpVB (gov2ai app) → Claude API
                         ↓
                    MySQL (riwayat + audit)
                    File Storage (uploads)
```

## Struktur App

```
apps/gov2ai/
├── index.php                    # Controller (extends Gov2lib\api)
├── model/
│   ├── index.php                # Dashboard + getList
│   ├── document.php             # Upload handler
│   ├── analysis.php             # Analisis engine + riwayat
│   └── quota.php                # Rate limiting
├── view/
│   ├── dashboard.html           # Twig template utama
│   └── analysis.html            # Detail hasil
├── vue/
│   ├── gov2ai-upload.vue        # Upload + pilih tipe analisis
│   ├── gov2ai-result.vue        # Tampilkan hasil (streaming)
│   └── gov2ai-history.vue       # Tabel riwayat
├── xml/
│   ├── route.xml                # Routes
│   ├── pageroles.xml            # Auth (guest=1, member=3)
│   ├── dsnSource.xml            # DSN
│   ├── dsnSource.dev.xml        # DSN server ayam
│   ├── dbTables.xml             # Tabel mapping
│   └── menu.xml                 # Sidebar entry
├── json/
│   └── analysis_field.json      # Form field definitions
└── sql/
    ├── ai_documents.sql         # Tabel metadata dokumen
    ├── ai_analysis.sql          # Tabel hasil analisis
    ├── ai_audit_log.sql         # Audit trail (compliance)
    └── ai_quota.sql             # Kuota per user/bulan

core/lib/
└── ClaudeClient.php             # Guzzle-based Claude API client
```

## Database (4 tabel)

| Tabel | Fungsi |
|-------|--------|
| `ai_documents` | Metadata file upload (account_id, filename, mime, path, status) |
| `ai_analysis` | Request + hasil analisis (document_id, type, prompt, result, tokens, cost, status) |
| `ai_audit_log` | Immutable log (account_id, action, IP, user_agent, detail JSON) |
| `ai_quota` | Kuota per user per bulan (requests_used/limit, tokens_used/limit) |

## ClaudeClient.php (core/lib/)

Mengikuti pola `eSignClient.php` — Guzzle client, config dari XML, error handling.

- **Config XML**: tambah `<claude>` di config.dev.xml / config.prod.xml
  ```xml
  <claude>
      <api_key>sk-ant-api03-xxxxx</api_key>
      <model>claude-sonnet-4-20250514</model>
      <max_tokens>4096</max_tokens>
  </claude>
  ```
- **Methods**: `analyze()`, `analyzeStream()` (Phase 2), `getSystemPrompt()`, `estimateTokens()`
- API key HANYA di server config, tidak pernah ke client

## Tipe Analisis

| Tipe | System Prompt (Bahasa Indonesia) |
|------|----------------------------------|
| ringkasan | Ringkas poin utama, kesimpulan, rekomendasi |
| ekstraksi | Ekstrak data terstruktur (nama, tanggal, nomor surat, instansi) |
| kebijakan | Analisis tujuan, stakeholder, dampak, risiko |
| custom | User tulis sendiri promptnya |

## Security

- Auth: semua endpoint `authenticate('guest')` — wajib login SSO
- Data isolation: query selalu filter `account_id = current user`
- Upload: validasi mime type server-side, max 10MB, simpan di luar webroot (`data/gov2ai/uploads/`)
- Audit: setiap aksi di-log ke `ai_audit_log`
- API key di `.gitignore`-d config file

## Routes

```
GET  /gov2ai                          → dashboard
POST /gov2ai/upload                   → upload dokumen
POST /gov2ai/analyze                  → trigger analisis
GET  /gov2ai/stream/{id}              → SSE streaming hasil (Phase 2)
GET  /gov2ai/history[/{cmd}[/{id}]]   → riwayat
GET  /gov2ai/download/{id}            → download hasil
GET  /gov2ai/quota                    → cek sisa kuota
```

## File References (pola yang diikuti)

- `core/lib/eSignClient.php` → pola ClaudeClient (Guzzle, error handling)
- `core/lib/api.php` → pola Bearer token, JSON response
- `apps/gov2option/model/option.php` → pola crudHandler, DB query
- `apps/gov2login/index.php` → pola controller + authenticate
- `apps/gov2option/vue/cube-menu-settings.vue` → pola Vue SFC

## Dependencies

- **Sudah ada**: Guzzle 7.8, firebase/php-jwt, MeekroDB
- **Server**: install `poppler-utils` untuk `pdftotext` (PDF text extraction)
- **Tidak perlu tambah composer package untuk Phase 1**

---

## Phasing

### Phase 1 — MVP (target: bisa deploy cepat)
- Upload **PDF only** (max 5MB)
- Text extraction via `pdftotext`
- 1 tipe analisis: **Ringkasan** (synchronous, non-streaming)
- Simpan riwayat + audit log
- Kuota sederhana: max 20 req/hari/user (hardcoded)
- **~12 file baru** (lihat struktur di atas)

### Phase 2 — Multi-format + Streaming
- Tambah DOCX, XLSX, gambar (Claude Vision API)
- SSE streaming response
- Semua 4 tipe analisis + custom prompt
- Kuota dari tabel `ai_quota`
- Download hasil sebagai .doc

### Phase 3 — Advanced
- Perbandingan 2 dokumen
- Export PDF
- Admin dashboard (usage semua user, manage kuota)
- Multi-tenant via gov2option
- Queue system untuk dokumen besar

### Phase 4 — Production Hardening
- Rate limiting nginx
- Monitoring + alerting
- File retention policy (auto-delete 90 hari)
- Token usage reporting per instansi

## Verifikasi

1. Deploy Phase 1 ke server ayam
2. Login via SSO → buka `/gov2ai`
3. Upload PDF → cek file tersimpan di `data/gov2ai/uploads/`
4. Klik "Analisis" → cek hasil ringkasan muncul
5. Cek riwayat di `/gov2ai/history`
6. Cek `ai_audit_log` ada record
7. Upload > 20x → cek kuota block
