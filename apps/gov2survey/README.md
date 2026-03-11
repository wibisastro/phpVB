# Gov2 Survey

Aplikasi survey dan formulir online untuk phpVB.

## Fitur

- Manajemen survey dan kuesioner
- Multi-domain support
- Navigasi breadcrumb dan wilayah
- Integrasi dengan rokuone components

## Route

| Method | URL | Fungsi |
|--------|-----|--------|
| GET | `/gov2survey` | Landing page |
| GET | `/gov2survey/index/version` | Info versi |
| GET | `/gov2survey/index/breadcrumb` | Breadcrumb navigasi |
| GET | `/gov2survey/index/getHeaders` | Header navigasi |
| GET | `/gov2survey/index/getMenus` | Menu sidebar |

## Auth

- Guest: login required (member level)
- Public: tanpa login
