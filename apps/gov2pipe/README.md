# Gov2 Pipe

Aplikasi pipeline dan data processing untuk phpVB.

## Fitur

- Tabel data dengan navigasi breadcrumb dan wilayah
- Session dan token management
- Integrasi webservice ke backend processing
- Multi-domain support (BKN, KPU, dll)

## Route

| Method | URL | Fungsi |
|--------|-----|--------|
| GET | `/gov2pipe` | Landing page |
| GET | `/gov2pipe/index/breadcrumb` | Breadcrumb wilayah |
| GET | `/gov2pipe/index/getHeaders` | Header navigasi |
| GET | `/gov2pipe/index/getMenus` | Menu sidebar |
| POST | `/gov2pipe/index/session` | Create session |

## Auth

- Level: `public` (autentikasi minimal)
- Session berbasis JWT token
