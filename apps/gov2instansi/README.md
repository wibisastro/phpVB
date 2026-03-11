# Gov2 Instansi

Aplikasi manajemen data instansi (kementerian/lembaga).

## Fitur

- CRUD data instansi dengan hierarki 2 level (Eselon 1 dan Eselon 2)
- Tabel recursive dengan breadcrumb navigasi
- Auth level: `admin`

## Struktur Data

| Level | Keterangan | Contoh |
|-------|-----------|--------|
| Eselon 1 | Kementerian / Lembaga induk | Kementerian Keuangan |
| Eselon 2 | Unit kerja di bawah kementerian | Ditjen Anggaran |

## Route

| Method | URL | Fungsi |
|--------|-----|--------|
| GET | `/gov2instansi` | Landing page (README) |
| GET | `/gov2instansi/instansi` | Tabel data instansi |
| GET | `/gov2instansi/instansi/table/{scroll}/{parentId}` | API data tabel |
| POST | `/gov2instansi/instansi/add` | Tambah instansi |
| POST | `/gov2instansi/instansi/edit/{id}` | Edit instansi |

## Side Panel

Data instansi juga ditampilkan di side panel (offcanvas) pada semua halaman via component `cube-instansi.vue`.
