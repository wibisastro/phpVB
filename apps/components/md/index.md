# Components

Shared components yang digunakan oleh semua app dalam phpVB.

## Komponen Utama

| Komponen | File | Fungsi |
|----------|------|--------|
| `gov2nav` | `gov2nav.php` | Navigasi sidebar dan breadcrumb |
| `gov2notification` | `gov2notification.php` | Notifikasi (Vue component) |
| `gov2navbreadcrumb` | `vue/gov2navbreadcrumb.vue` | Breadcrumb Vue component |

## Penggunaan

Dari controller app lain:

```php
// Load semua components
$self->takeAll("components");

// Load spesifik component
$self->take("components", "gov2nav", "setDefaultNav");
$self->take("components", "gov2notification");
```

## Vue Components

Vue SFC components tersedia di `vue/` dan di-register otomatis via `$doc->component('components')`.
