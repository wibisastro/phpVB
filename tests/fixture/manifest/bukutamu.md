# Manifest API — bukutamu v1.2.0

<!-- manifest:app start -->
```json manifest-app
{
  "skema": 1,
  "app": "bukutamu",
  "judul": "Buku Tamu",
  "versi": "1.2.0",
  "dibangkitkan": "2026-10-08T10:00:00+07:00",
  "generator": "gov3phpvb 0.4",
  "framework_min": "5.1.5",
  "transport": {
    "json": "Hanya bila header Accept diawali application/json; selain itu balasan HTML (J01).",
    "get": "/{app}/{mvc}/{cmd}[/{id}] dan /{app}/{mvc}/{cmd}/{scroll}/{id} → controller::{cmd}($vars); segmen ke-2 = id (J02).",
    "post": "/{app}/{mvc} body JSON {\"cmd\": \"...\", ...} → controller::{cmd}($_POST); multipart → $_FILES.",
    "sesi": "cookie Gov2Session (JWT HS256) — diterbitkan ulang oleh portal, klien wajib menyerap Set-Cookie; agen: POST /{app}/agen cmd=sesi (v5.1.6).",
    "galat": {"401": "NotLogin — belum login", "403": "Unauthorized — role kurang", "422": "validasi / kueri gagal; class is-warning|is-danger"},
    "catatan": ["200 ≠ sukses: baca class/notification pada balasan cmd tulis (J18)."]
  }
}
```
<!-- manifest:app end -->

## Konvensi transport (berlaku semua MVC)

- JSON hanya bila `Accept: application/json`; GET `/{app}/{mvc}/{cmd}/{id}`; POST `/{app}/{mvc}` body `{cmd, …}`.
- 401 `NotLogin`, 403 `Unauthorized`, 422 validasi. 200 ≠ sukses — baca `class`/`notification`.

## MVC

| MVC | Judul | Pola | Tabel | Auth | Cmd |
|---|---|---|---|---|---|
| jenis_keperluan | Jenis Keperluan | P1 | jenis_keperluan | admin (konstruktor) | fields count table edit add update del bulk |
| kunjungan | Kunjungan Tamu | P1+M4+M5 | kunjungan | admin (konstruktor) | fields count table edit add update del detail search resetSearch |

<!-- manifest:mvc:jenis_keperluan start -->
### jenis_keperluan — Jenis Keperluan

Konteks: referensi keperluan kunjungan (lihat README §Jenis Keperluan). Tabel `jenis_keperluan`, auth `admin` (konstruktor → semua cmd).

| cmd | method | URL / body | argumen | balasan | catatan |
|---|---|---|---|---|---|
| fields | GET | /bukutamu/jenis_keperluan/fields | — | array field form | field pertama `cmd` |
| count | GET | /bukutamu/jenis_keperluan/count/{id} | id=0 | `{totalRecord}` | J03 |
| table | GET | /bukutamu/jenis_keperluan/table/{scroll}/{id} | scroll≥1, id=0 | array baris \| `{data:'empty'}` | J02 |
| edit | GET | /bukutamu/jenis_keperluan/edit/{id} | id | satu baris | konfirmasi sebelum update/del |
| add | POST | `{cmd:'add', nama, keterangan}` | wajib: nama | `{class:'is-primary', id}` | |
| update | POST | `{cmd:'update', id, …}` | id + kolom | `{class:'is-info'}` | |
| del | POST | `{cmd:'del', id}` | id | `{class:'is-danger'}` | `edit/{id}` dulu, minta konfirmasi |
| bulk | GET | /bukutamu/jenis_keperluan/bulk | — | `[{id, nama}]` | sumber dropdown (M6) |

Alur: **tambah** fields → add · **ubah** table → edit/{id} → update · **hapus** table → edit/{id} → (konfirmasi) → del.

```json manifest
{
  "mvc": "jenis_keperluan",
  "judul": "Jenis Keperluan",
  "pola": ["P1"],
  "tabel": "jenis_keperluan",
  "auth": {"level": "admin", "scope": "ctor"},
  "konteks": "Referensi keperluan kunjungan.",
  "cmd": {
    "fields": {"method": "GET", "uri": "/bukutamu/jenis_keperluan/fields", "ringkas": "definisi field form"},
    "count": {"method": "GET", "uri": "/bukutamu/jenis_keperluan/count/{id}", "wajib": ["id"], "ringkas": "jumlah baris; id=0"},
    "table": {"method": "GET", "uri": "/bukutamu/jenis_keperluan/table/{scroll}/{id}", "wajib": ["scroll", "id"], "ringkas": "halaman tabel"},
    "edit": {"method": "GET", "uri": "/bukutamu/jenis_keperluan/edit/{id}", "wajib": ["id"], "peran": "konfirmasi", "ringkas": "satu baris sebelum update/del"},
    "add": {"method": "POST", "uri": "/bukutamu/jenis_keperluan", "body": {"cmd": "add"}, "wajib": ["nama"], "opsional": ["keterangan"], "balasan": "class is-primary + id"},
    "update": {"method": "POST", "uri": "/bukutamu/jenis_keperluan", "body": {"cmd": "update"}, "wajib": ["id", "nama"], "opsional": ["keterangan"], "balasan": "class is-info"},
    "del": {"method": "POST", "uri": "/bukutamu/jenis_keperluan", "body": {"cmd": "del"}, "wajib": ["id"], "konfirmasi": "edit", "balasan": "class is-danger"},
    "bulk": {"method": "GET", "uri": "/bukutamu/jenis_keperluan/bulk", "ringkas": "sumber dropdown {id, nama}"}
  },
  "field": [
    {"name": "nama", "type": "text", "label": "Nama", "required": true},
    {"name": "keterangan", "type": "textarea", "label": "Keterangan", "required": false}
  ],
  "alur": {"tambah": ["fields", "add"], "ubah": ["table", "edit", "update"], "hapus": ["table", "edit", "del"]}
}
```
<!-- manifest:mvc:jenis_keperluan end -->

<!-- manifest:mvc:kunjungan start -->
### kunjungan — Kunjungan Tamu

Konteks: buku tamu harian (lihat README §Kunjungan). Tabel `kunjungan`, auth `admin` (konstruktor → semua cmd).

| cmd | method | URL / body | argumen | balasan | catatan |
|---|---|---|---|---|---|
| fields | GET | /bukutamu/kunjungan/fields | — | array field form | |
| count | GET | /bukutamu/kunjungan/count/{id} | id=0 | `{totalRecord}` | |
| table | GET | /bukutamu/kunjungan/table/{scroll}/{id} | scroll≥1, id=0 | array baris | terfilter kata kunci `search` |
| edit | GET | /bukutamu/kunjungan/edit/{id} | id | satu baris | konfirmasi sebelum update/del |
| add | POST | `{cmd:'add', nama, tanggal, keperluan_id, catatan}` | wajib: nama, tanggal | `{class:'is-primary', id}` | |
| update | POST | `{cmd:'update', id, …}` | id + kolom | `{class:'is-info'}` | |
| del | POST | `{cmd:'del', id}` | id | `{class:'is-danger'}` | `edit/{id}` dulu, minta konfirmasi |
| detail | GET | /bukutamu/kunjungan/detail/{id} | id | kartu detail (M4) | |
| search | POST | `{cmd:'search', data:{q, fields, instance}}` | q | `success` | kata kunci ke sesi (M5) |
| resetSearch | GET | /bukutamu/kunjungan/resetSearch | — | `success` | hapus kata kunci |

Alur: **tambah** fields → add · **ubah** table → edit/{id} → update · **hapus** table → edit/{id} → (konfirmasi) → del · **cari** search → table.

```json manifest
{
  "mvc": "kunjungan",
  "judul": "Kunjungan Tamu",
  "pola": ["P1", "M4", "M5"],
  "tabel": "kunjungan",
  "auth": {"level": "admin", "scope": "ctor"},
  "konteks": "Buku tamu harian; keperluan merujuk jenis_keperluan.",
  "cmd": {
    "fields": {"method": "GET", "uri": "/bukutamu/kunjungan/fields", "ringkas": "definisi field form"},
    "count": {"method": "GET", "uri": "/bukutamu/kunjungan/count/{id}", "wajib": ["id"]},
    "table": {"method": "GET", "uri": "/bukutamu/kunjungan/table/{scroll}/{id}", "wajib": ["scroll", "id"]},
    "edit": {"method": "GET", "uri": "/bukutamu/kunjungan/edit/{id}", "wajib": ["id"], "peran": "konfirmasi"},
    "add": {"method": "POST", "uri": "/bukutamu/kunjungan", "body": {"cmd": "add"}, "wajib": ["nama", "tanggal"], "opsional": ["keperluan_id", "catatan"], "balasan": "class is-primary + id"},
    "update": {"method": "POST", "uri": "/bukutamu/kunjungan", "body": {"cmd": "update"}, "wajib": ["id", "nama", "tanggal"], "opsional": ["keperluan_id", "catatan"], "balasan": "class is-info"},
    "del": {"method": "POST", "uri": "/bukutamu/kunjungan", "body": {"cmd": "del"}, "wajib": ["id"], "konfirmasi": "edit", "balasan": "class is-danger"},
    "detail": {"method": "GET", "uri": "/bukutamu/kunjungan/detail/{id}", "wajib": ["id"], "ringkas": "kartu detail (M4)"},
    "search": {"method": "POST", "uri": "/bukutamu/kunjungan", "body": {"cmd": "search", "data": {"q": "", "fields": [], "instance": ""}}, "wajib": ["data.q"], "ringkas": "kata kunci ke sesi (M5)"},
    "resetSearch": {"method": "GET", "uri": "/bukutamu/kunjungan/resetSearch", "ringkas": "hapus kata kunci"}
  },
  "field": [
    {"name": "nama", "type": "text", "label": "Nama Tamu", "required": true},
    {"name": "tanggal", "type": "date", "label": "Tanggal", "required": true},
    {"name": "keperluan_id", "type": "select", "label": "Keperluan", "required": false, "sumber": "/bukutamu/jenis_keperluan/bulk"},
    {"name": "catatan", "type": "textarea", "label": "Catatan", "required": false}
  ],
  "alur": {"tambah": ["fields", "add"], "ubah": ["table", "edit", "update"], "hapus": ["table", "edit", "del"], "cari": ["search", "table"]}
}
```
<!-- manifest:mvc:kunjungan end -->
