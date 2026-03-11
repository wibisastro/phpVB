# Gov2 Option

Aplikasi manajemen Options dan Services untuk setiap app di phpVB.

## Fitur

- CRUD options dan services per app
- Offcanvas panel pengaturan (accessible dari semua halaman)
- Year selector per app
- Pageroles management

## Konsep

Semua konfigurasi app disimpan di tabel `options`:

| Kolom | Keterangan |
|-------|-----------|
| `app` | ID aplikasi (pageID) |
| `nama` | Nama option |
| `value` | Nilai option |
| `type` | `option` atau `service` |
| `status` | `ON` / `OFF` |
| `level` | Hierarki (1 = parent, 2 = child) |

## API Endpoints

| Method | URL | Fungsi |
|--------|-----|--------|
| GET | `/gov2option/index/getList` | Semua options + services (status ON) |
| GET | `/gov2option/index/getYearOptions/{appID}` | Year options per app |
| GET | `/gov2option/index/getPageroles/{appID}` | Pageroles per app |
| GET | `/gov2option/{pageID}/setup` | Form setup options |
| GET | `/gov2option/{pageID}/view` | View options |
| GET | `/gov2option/{pageID}/view_services` | View services |

## Side Panel API (Instansi)

| Method | URL | Fungsi |
|--------|-----|--------|
| GET | `/gov2option/index/getUnitKerjaConfig` | Baca session instansi |
| GET | `/gov2option/index/getUnitKerjaList/{id}` | Tree data instansi |
| GET | `/gov2option/index/searchUnitKerja?q=...` | Search instansi |
| GET | `/gov2option/index/changePortal/{id}` | Simpan pilihan ke session |
| GET | `/gov2option/index/resetPortal` | Clear session instansi |
