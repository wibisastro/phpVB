# phpVB

Framework PHP untuk pengembangan aplikasi pemerintahan (government e-services) dengan arsitektur **Component-Based MVC**, konfigurasi XML-driven, dan frontend Vue.js interaktif.

> **Versi saat ini:** v5.0.2 (Perbaikan Arsitektur)
> Lihat [Release Notes](../../wiki/13-Release-Notes) untuk detail perubahan per versi.

---

## Tech Stack

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| **Runtime** | PHP | ^8.4 |
| **Routing** | nikic/fast-route | ^1.3 |
| **HTTP Client** | Guzzle | ^7.8 |
| **Database** | MySQL/MariaDB via MeekroDB | 2.5.1 |
| **Template** | Twig | ^3.20 |
| **Auth** | JWT (firebase/php-jwt) + OAuth2/Keycloak SSO | ^6.11 / ^2.7 |
| **Email** | Mailgun SDK | ^4.0 |
| **Frontend** | Vue 2 + Bootstrap 4 + httpVueLoader | 2.6.11 |
| **Testing** | PHPUnit + PHPStan + PHP CS Fixer | ^11.0 / ^2.0 / ^3.0 |

---

## Arsitektur

phpVB menggunakan arsitektur Component-Based MVC dengan XML Configuration. Alur request:

```
Browser Request
  -> public/index.php (CORS, session init)
    -> core/init/index.php (bootstrap config, router, template)
      -> core/init/route.php (FastRoute dispatcher)
        -> Handler class (apps/{app}/{handler}.php)
          -> Model operations (apps/{app}/model/{model}.php)
            -> Response: JSON (AJAX) atau Twig HTML (page render)
```

### Class Hierarchy (Gov2lib)

```
Gov2lib\customException
  └── Gov2lib\document          # Twig rendering, body data, error pages
       └── Gov2lib\dsnSource    # DB connection via XML DSN
            └── Gov2lib\checkExist    # Directory/file validation
                 └── Gov2lib\crudModel     # doAdd, doUpdate, doDel, doRead, doBrowse
                      └── Gov2lib\crudHandler   # HTTP request mapping + response
                           └── App\{module}\model\{class}  # App-specific logic
```

### Namespace & Autoloading (PSR-4)

```
Gov2lib\               -> core/lib/
Gov2lib\Enums\         -> core/lib/Enums/
Gov2lib\Exceptions\    -> core/lib/Exceptions/
App\                   -> apps/
Tests\                 -> tests/
```

---

## Struktur Direktori

```
phpVB/
├── public/                 # Web root (index.php, .htaccess, static assets)
│   ├── index.php           # Entry point: CORS headers, session, bootstrap
│   ├── js/                 # Shared JavaScript (gov2form, gov2helper, Vue libs)
│   └── css/                # Shared stylesheets
├── core/
│   ├── config/             # XML config per environment (local, dev, prod)
│   ├── init/               # Bootstrap: index.php, route.php, template.php
│   ├── lib/                # 32+ library classes (Gov2lib namespace)
│   │   ├── Enums/          # PHP 8.4 enums (UserRole, UserStatus, HttpMethod, NotificationType)
│   │   ├── Exceptions/     # Typed exceptions (Http, Auth, Validation, NotFound, DB, Config)
│   │   └── *.php           # Core classes (document, crudModel, crudHandler, dll)
│   ├── template/           # Twig templates (bootstrap, bulma, krisna, cube)
│   └── scripts/            # Vue build pipeline
├── apps/
│   ├── home/               # Homepage module
│   ├── gov2login/          # Auth & SSO (Keycloak OAuth2, JWT session)
│   ├── gov2option/         # System configuration/options
│   ├── gov2survey/         # Survey & kuesioner
│   ├── gov2pipe/           # Data pipeline & workflow
│   └── components/         # Shared Vue components (26 files)
├── tests/
│   ├── Unit/               # Unit tests (Enums, Exceptions)
│   └── Integration/        # Integration tests
├── release_notes/          # Catatan rilis per versi
├── composer.json
├── phpunit.xml
└── .env                    # Environment variables
```

### Pola Modular per App

Setiap app mengikuti struktur konsisten:

```
apps/{module}/
├── {module}.php             # Controller/handler utama
├── model/                   # Model classes (extends crudModel/crudHandler)
├── view/                    # Twig HTML templates
├── vue/                     # Vue SFC components (.vue)
├── json/                    # Konfigurasi form/fields
├── xml/                     # Route, DSN, table config, menu
└── sql/                     # Schema SQL
```

---

## Kekuatan Arsitektur

**Modular & Konsisten** -- Setiap app adalah module mandiri dengan struktur yang sama (controller, model, view, vue, xml, sql), memudahkan onboarding developer baru dan penambahan fitur.

**Namespace PSR-4** -- Autoloading terstandar untuk `Gov2lib\` dan `App\`, mempermudah penemuan class dan menghindari konflik nama.

**Parameterized Queries** -- MeekroDB menggunakan parameterized queries (`%i`, `%s`, `%b`) secara konsisten di seluruh codebase, memberikan perlindungan dari SQL injection.

**JWT Session Management** -- Autentikasi menggunakan firebase/php-jwt dengan cookie-based session (`Gov2Session`), mendukung SSO via OAuth2/Keycloak.

**Multi-Environment Config** -- Konfigurasi XML per environment (`dsnSource.local.xml`, `dsnSource.dev.xml`, `dsnSource.siap.xml`) memungkinkan deployment fleksibel tanpa mengubah kode.

**Hierarchical Data Support** -- Built-in support untuk data hierarkis (parent-child, breadcrumb, wilayah geographic hierarchy) di crudModel dan crudHandler.

**Multi-Theme Templates** -- Empat tema tersedia (Bootstrap, Bulma, Krisna, Cube) dengan Twig template engine, mendukung switching tema tanpa perubahan logic.

**PHP 8.4 Modern Syntax** -- Core library sudah direfactor ke PHP 8.4 dengan typed properties, return types, match expressions, null-safe operator, property hooks, asymmetric visibility, dan PHPDoc lengkap.

**Typed Enums & Exceptions** -- PHP 8.4 backed enums (UserRole, UserStatus, HttpMethod, NotificationType) dan typed exception hierarchy menggantikan string-based patterns lama, dengan backward compatibility methods.

**Testing Infrastructure** -- PHPUnit 11, PHPStan level 5, dan PHP CS Fixer tersedia via composer scripts untuk code quality assurance.

---

## Quick Start

Untuk panduan instalasi lengkap (Windows/Laragon dan Linux Ubuntu/Apache), lihat:

**[Setup Guide](../../wiki/10-Setup-Guide)**

Quick commands setelah setup:

```bash
# Install dependencies
composer install

# Jalankan tests
composer test

# Static analysis
composer lint

# Code style fix
composer cs-fix

# Lint + test sekaligus
composer check
```

---

## Roadmap

phpVB sedang dalam proses refactoring besar menuju arsitektur modern. Rencana dibagi dalam 3 rilis utama dan 7 fase:

| Rilis | Versi | Fase | Fokus | Status |
|-------|-------|------|-------|--------|
| **1 — Deploy Klasik** | **v5.0.1** | **1 — Foundation** | PHP 8.4, tooling, testing, modern syntax | **Selesai** |
| | **v5.0.2** | **2 — Arsitektur** | DI container, interfaces, routing bersih | **Selesai** |
| 2 — Deploy Baru | v5.1.1 | 3 — Frontend | Vue 3, Vite, Bootstrap 5, TypeScript | Planned |
| | v5.1.2 | 4 — Supabase | API abstraction layer, PostgreSQL migration | Planned |
| 3 — Incremental | v5.2.1 | 5 — Template | Design system, dark mode, SCSS | Planned |
| | v5.2.2 | 6 — PWA | Service worker, offline support, installable | Planned |
| | v5.2.3 | 7 — Finalisasi | Migration system, CI/CD, cleanup | Planned |

Estimasi total: 18 minggu untuk 1 developer full-time.

Detail lengkap tersedia di [Refactoring Plan](../../wiki/12-Refactoring-Plan).

---

## Dokumentasi

Dokumentasi lengkap tersedia di [Wiki](../../wiki):

| Dokumen | Isi |
|---------|-----|
| [Setup Guide](../../wiki/10-Setup-Guide) | Panduan instalasi Windows & Linux |
| [Codebase Review](../../wiki/11-Codebase-Review) | Review arsitektur dan analisis teknis |
| [Refactoring Plan](../../wiki/12-Refactoring-Plan) | Rencana refactoring 7 fase lengkap |
| [Release Notes](../../wiki/13-Release-Notes) | Catatan rilis per versi |

---

## Lisensi

Hak cipta dilindungi. Framework ini dikembangkan untuk kebutuhan proyek pemerintahan.

## Kontak

Wibisono Sastrodiwiryo -- wibi@alumni.ui.ac.id
