# phpVB — PHP Vue Bootstrap Framework

Situs development **phpVB** — framework PHP 8.4 dengan Vue 2 dan Bootstrap 5 (Cube theme).

## Fitur Utama

- **Cube Theme** — Layout responsive dengan sidebar, header, content, footer
- **Vue 2 SFC** — Single File Component via httpVueLoader
- **Twig Template** — Template engine dengan block inheritance
- **JWT Auth** — Session berbasis JSON Web Token via SSO
- **RESTful API** — Routing via FastRoute

## Struktur Layout

Layout Cube theme terdiri dari beberapa area:

| Area | File Template | Twig Block |
|------|--------------|------------|
| Head | `cubeHead.html` | `block head` |
| Sidebar | `cubeSideNav.html` | `block sidebar` |
| Header | `cubeHeader.html` | `block header` |
| Content | *(per-app)* | `block content` |
| Footer | `cubeFooter.html` | `block footer` |

## Apps

| App | Fungsi |
|-----|--------|
| `home` | Landing page |
| `gov2login` | Login, signup, profile, logout |
| `gov2option` | Options & services management |
| `gov2wilayah` | CRUD wilayah + sidepanel navigator |
| `gov2instansi` | CRUD data instansi |
| `components` | Shared components (nav, breadcrumb, dll) |

## Quick Start

```bash
composer install
# Konfigurasi config.dev.xml
# Akses via browser
```

## Dokumentasi

Dokumentasi lengkap tersedia di [phpVB Wiki](https://github.com/wibisastro/phpVB/wiki).
