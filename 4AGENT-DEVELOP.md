# Panduan Membuat Aplikasi Baru di phpVB

> Dokumen ini ditujukan untuk **AI coding agent** (Claude, Copilot, dll) yang akan membuat PoC atau aplikasi baru di atas framework phpVB.
> Untuk panduan instalasi framework, lihat `4AGENT-INSTALL.md`.
> Untuk arsitektur & MVC pattern, lihat wiki `03-Architecture.md` dan `04-MVC-Guide.md`.

---

## 1. Naming Convention

### Folder vs File

```
apps/gov2example/           ← Folder = identitas modul (pageID)
├── example.php             ← Controller = nama resource
├── model/example.php       ← Model = nama resource
├── view/example.html       ← View = nama resource
├── vue/example.vue         ← Vue SFC = nama resource
├── json/example.json       ← Form fields = nama resource
└── sql/example.sql         ← DDL = nama tabel
```

**Aturan:**
- Folder app = nama modul (pageID), **prefix `gov2` opsional** — bisa `gov2example` atau langsung `example`
- File MVC **tanpa prefix**, konsisten di semua layer: `example.*`
- Namespace PHP: `App\{pageID}\model\{resource}` (contoh: `App\gov2example\model\example` atau `App\inventory\model\barang`)

### Jika app punya banyak resource

```
apps/gov2inventory/
├── index.php               ← Landing/dashboard
├── barang.php              ← Controller CRUD barang
├── kategori.php            ← Controller CRUD kategori
├── model/
│   ├── index.php
│   ├── barang.php
│   └── kategori.php
├── view/
│   ├── body.html           ← Layout utama
│   ├── barang.html
│   └── kategori.html
├── json/
│   ├── barang.json
│   └── kategori.json
└── sql/
    ├── barang.sql
    └── kategori.sql
```

### Konten Markdown Per-Tenant (Multi-Tenant)

Untuk app yang dipakai banyak tenant (multi-domain), konten markdown bisa di-override per-tenant via naming convention `{tenant}.{name}.md`.

**Resolusi tenant slug (urutan prioritas):**

1. **Atribut XML eksplisit** `tenant="..."` di `config.{stage}.xml`
2. **Auto-derive dari subdomain** — label pertama dari `SERVER_NAME`
3. Kosong → tidak ada lookup tenant-specific, langsung pakai generic

```xml
<domain>
    <!-- Eksplisit (override otomatis): -->
    <portal.bkpm.go.id tenant="bkpm">home</portal.bkpm.go.id>

    <!-- Auto-derive: tenant = "bkpm" (label pertama) -->
    <bkpm.gov2.web.id>home</bkpm.gov2.web.id>

    <!-- localhost / IP / single-label hostname → tenant kosong -->
    <localhost>home</localhost>
</domain>
```

> **Catatan:** value entry `<domain>` (`home` di contoh atas) tetap menjadi `$pageID` default. Atribut `tenant` adalah **field terpisah** khusus untuk override konten per-tenant.
>
> Slug di-sanitasi ke `^[a-z0-9_-]+$` — kalau hasil derive tidak match (misal IP address `192`), tenant lookup di-skip.

**Struktur folder `md/`:**

```
apps/home/md/
├── index.md                ← Generic (fallback semua tenant)
├── tentang.md
├── bkpm/                   ← Namespace tenant bkpm
│   ├── index.md
│   ├── tentang.md
│   └── kontak.md
└── kemenko/
    ├── index.md
    └── tentang.md
```

**Pemanggilan dari controller — tidak berubah:**

```php
$doc->body('readMD');             // resolve md/{className}.md (caller's class name)
$doc->body('readMD', 'tentang');  // resolve md/tentang.md
```

**Resolution chain (first match wins):**
1. `md/{tenant}/{name}.md` — tenant subfolder (kalau tenant slug tersedia)
2. `md/{name}.md` — generic fallback
3. `core/lib/md_missing.md` — template error

**Aturan:**
- Tenant slug di-sanitasi ke `^[a-z0-9_-]+$`; selain itu di-skip
- Auto-derive subdomain cocok untuk skenario `{tenant}.domain-utama.tld` — kalau struktur domain berbeda, pakai atribut `tenant="..."` eksplisit

---

## 2. Struktur Minimal (PoC)

Minimum file untuk app yang **bisa jalan**:

```
apps/gov2example/
├── index.php                   ← Controller utama
├── model/
│   └── index.php               ← Model (extends document)
├── view/
│   └── body.html               ← Template Twig (extends cubeLayout)
└── xml/
    ├── route.xml               ← Routing URL
    └── menu.xml                ← Sidebar menu
```

Tambahan jika butuh **database**:

```
├── xml/
│   ├── dbTables.xml            ← Mapping nama tabel
│   └── pageroles.xml           ← Access control (opsional)
├── json/
│   └── example.json            ← Form field definitions
└── sql/
    └── example.sql             ← CREATE TABLE DDL
```

> **dsnSource.dev.xml** TIDAK masuk git (gitignored `**/dsn*.xml`). Buat manual di server.

---

## 3. File XML — Template

### route.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<list>
    <!-- Landing page -->
    <route>
        <method>GET</method>
        <uri>/gov2example</uri>
        <handler>gov2example\model\index</handler>
    </route>

    <!-- Resource page (tampilkan tabel) -->
    <route>
        <method>GET</method>
        <uri>/gov2example/example</uri>
        <handler>gov2example\model\example</handler>
    </route>

    <!-- CRUD commands: /gov2example/example/{cmd}[/{id}] -->
    <route>
        <method>GET</method>
        <uri>/gov2example/example/{cmd}[/{id}]</uri>
        <handler>gov2example\model\example</handler>
    </route>

    <!-- Pagination: /gov2example/example/{cmd}/{scroll}[/{id}] -->
    <route>
        <method>GET</method>
        <uri>/gov2example/example/{cmd}/{scroll}/[{id}]</uri>
        <handler>gov2example\model\example</handler>
    </route>

    <!-- POST form submit -->
    <route>
        <method>POST</method>
        <uri>/gov2example/example</uri>
        <handler>gov2example\model\example</handler>
    </route>
</list>
```

**Aturan routing:**
- `handler` = namespace model class: `{pageID}\model\{className}`
- `{cmd}` = nama method di controller (table, edit, add, update, del, fields, breadcrumb, dll)
- `[/{id}]` = parameter opsional (square brackets)
- Method di controller dipanggil otomatis berdasarkan `{cmd}`

#### App sebagai default landing page (di-route dari `/`)

Kalau app ini di-set sebagai default di `<domain>` config:

```xml
<domain>
    <portal.example.id>gov2example</portal.example.id>   <!-- gov2example = default -->
</domain>
```

**Mekanisme default framework** (di [`core/init/route.php`](core/init/route.php)): saat user akses root `/`, dispatcher coba match `/` dulu. Kalau tidak ada route yang match, framework **fallback retry** ke `/{pageID}` (route standar app). Jadi app **tidak wajib register `<uri>/</uri>`** — selama route `<uri>/{pageID}</uri>` ada (yang memang konvensi standar), root URL akan jalan.

**Tapi sebaiknya tetap atur sendiri** route untuk `/` dengan handler eksplisit:

```xml
<route>
    <method>GET</method>
    <uri>/</uri>
    <handler>gov2example\model\index</handler>
</route>
```

Alasan:
- **Kontrol eksplisit** atas root behavior (misal landing page beda dari `/{pageID}`, redirect khusus, custom class)
- **Tidak bergantung** pada fallback framework yang bisa berubah implementasinya
- **Self-documenting** — siapa baca route.xml langsung tahu `/` di-handle, tidak perlu trace ke framework
- **Predictable** — eksplisit selalu menang; fallback hanya kick in saat dispatcher NOT_FOUND

Aturan precedence:
1. Route eksplisit di route.xml app dicoba duluan
2. Kalau tidak match → framework retry dengan `/{pageID}`
3. Kalau masih tidak match → 404

### menu.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<mainmenu>
    <menu>
        <caption>Example</caption>
        <url>/gov2example</url>
        <menu>
            <caption>Data Example</caption>
            <url>/gov2example/example</url>
        </menu>
    </menu>
</mainmenu>
```

- Hanya 2 level: menu utama + submenu
- URL = path dari route.xml

### dbTables.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<list>
    <table name="example">example</table>
</list>
```

- `name` = nama yang dipakai di PHP (`$this->tbl->example`)
- Value = nama tabel fisik di MySQL

### pageroles.xml (opsional)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<pageroles>
    <guest>1</guest>
    <member>3</member>
    <admin>4</admin>
    <webmaster>5</webmaster>
    <default>0</default>
</pageroles>
```

- Jika tidak ada, pakai default dari `config.{STAGE}.xml`
- Level di pageroles ≠ UserRole enum (legacy design)

---

## 4. Controller — Template

### Controller sederhana (tanpa DB)

```php
<?php

namespace App\gov2example;

class index extends \Gov2lib\api
{
    public function __construct()
    {
        global $self, $doc;
        $self->takeAll('components');
        parent::__construct();
    }

    public function index(): void
    {
        global $self, $doc;
        $self->ses->authenticate('public');     // 'public' = tanpa auth
        $doc->body('pageTitle', 'Example');
        $self->content();                       // Render view/body.html
    }
}
```

### Controller CRUD

```php
<?php

namespace App\gov2example;

class example extends \Gov2lib\api
{
    public function __construct()
    {
        global $self, $doc;
        $self->takeAll('components');
        $doc->component('gov2option');
        parent::__construct();
        $self->scrollInterval = 100;
        $self->fields = $self->gov2formfield->getFields(__DIR__ . '/json/example.json');
    }

    public function index(): void
    {
        global $self, $doc;
        $self->ses->authenticate('guest');      // Minimal login
        $self->take('components', 'gov2nav', 'setDefaultNav');
        $doc->body('pageTitle', 'Example');
        $doc->body('subTitle', 'Data Example');
        $self->loadTable();
        $self->content();
    }

    public function fields(): array
    {
        global $self;
        return $self->fields;
    }

    public function table(array $vars): mixed
    {
        global $self;
        return $self->getRecords($vars);
    }

    public function edit(array $vars): array
    {
        global $self;
        return $self->getRecord((int) ($vars['id'] ?? 0));
    }

    public function add(): array
    {
        global $self;
        unset($_POST['id'], $_POST['cmd']);
        return $self->postAdd($_POST);
    }

    public function update(): array
    {
        global $self;
        return $self->postUpdate($_POST);
    }

    public function del(): array
    {
        global $self;
        return $self->postDel($_POST);
    }

    public function count(array $vars): mixed
    {
        global $self;
        return $self->getCount((int) ($vars['id'] ?? 0));
    }
}
```

**Pola penting:**
- Constructor: `parent::__construct()` dipanggil di akhir
- `$self->ses->authenticate($role)` — level akses: `public`, `guest`, `member`, `admin`
- `$self->content()` — render view template
- Method return `array` atau `mixed` — framework serialize ke JSON otomatis

---

## 5. Model — Template

### Model tanpa DB (extends document)

```php
<?php

namespace App\gov2example\model;

class index extends \Gov2lib\document
{
    public function __construct()
    {
        $this->templateDir = __DIR__ . '/../view';
        $path = explode('\\', __CLASS__);
        $this->className = $path[count($path) - 1];
        $this->controller = __DIR__ . '/../' . $this->className . '.php';
    }

    public function dependencies(): void
    {
    }
}
```

### Model dengan DB (extends crudHandler)

```php
<?php

namespace App\gov2example\model;

class example extends \Gov2lib\crudHandler
{
    public function __construct()
    {
        global $config, $doc;

        $this->templateDir = __DIR__ . '/../view';
        $path = explode('\\', __CLASS__);
        $this->className = $path[count($path) - 1];
        $doc->body('className', $this->className);

        $this->controller = __DIR__ . '/../' . $this->className . '.php';

        // DSN = nama koneksi dari dsnSource.xml
        parent::__construct($config->domain->attr['dsn'] ?? '');

        // Map tabel dari dbTables.xml
        $this->tbl->table = $this->tbl->example;
    }

    /**
     * Setup vueData untuk tablepack component
     */
    public function loadTable(): void
    {
        global $doc;
        $prefix = '/' . $doc->pageID . '/' . $this->className;
        $GLOBALS['vueData']['action'] = $prefix;
        $GLOBALS['vueData']['fieldurl'] = $prefix . '/fields';
        $GLOBALS['vueData']['breadcrumburl'] = $prefix . '/breadcrumb';
        $GLOBALS['vueData']['itemPerPage'] = 10;
        $GLOBALS['vueData']['interval'] = [10, 25, 50, 100];
        $GLOBALS['vueData']['scrollInterval'] = $this->scrollInterval;
    }

    /**
     * Override doBrowse untuk custom query
     */
    public function doBrowse(int|string $scroll = 0, int|string $parentId = 0, string $parentIdName = ''): ?array
    {
        try {
            $scrolled = $this->scroll((int) $scroll);
            $query = "SELECT * FROM {$this->tbl->table} ORDER BY nama ASC LIMIT {$scrolled}";
            return \DB::query($query);
        } catch (\MeekroDBException $e) {
            $this->exceptionHandler($e->getMessage());
            return null;
        }
    }

    public function dependencies(): void
    {
    }
}
```

**Wajib:**
- `dependencies()` method harus ada (dipanggil oleh Gov2lib\login)
- `$this->templateDir`, `$this->className`, `$this->controller` harus di-set di constructor
- `parent::__construct($dsn)` untuk koneksi DB

---

## 6. View — Template (Twig)

### body.html (layout utama)

```html
{% extends "cubeLayout.html" %}

{% block head %}
    {{ parent() }}
    {% include('cubeHead.html') %}
{% endblock %}

{% block header %}
    {% include('cubeHeader.html') %}
{% endblock %}

{% block pagetitle %}
    {% include('cubePageTitle.html') %}
{% endblock %}

{% block notification %}
    {% include('cubeNotification.html') %}
{% endblock %}

{% block sidebar %}
    {% for item in sidebars %}
       {% include[item,'cubeMissingFile.html'] %}
    {% endfor %}
{% endblock %}

{% block content %}
    {% for item in contents %}
       {% include[item,'cubeMissingFile.html'] %}
    {% endfor %}
{% endblock %}

{% block footer %}
    {% include('cubeFooter.html') %}
{% endblock %}

{% block js %}
    {{ parent() }}
    {% include('vueJS.html') %}
{% endblock %}

{% block externalJS %}
    {% include('externalJS.html') %}
{% endblock %}
```

### Content template dengan tablepack (example.html)

```html
<!-- Form fields -->
<gov2formfield-bs4
    :action="action"
    :field-url="fieldurl">
</gov2formfield-bs4>

<!-- Tabel data -->
<div class="row">
  <div class="col-lg-12">
    <tablepack
        :is-active="true"
        :get-url="action"
        :post-url="action"
        :columns="['id','nama','keterangan']"
        :filter-key="searchQuery"
        :item-per-page="itemPerPage"
        :interval="interval"
        :scroll-interval="scrollInterval"
        :readonly="false">
    </tablepack>
  </div>
</div>
```

### Content template sederhana (tanpa CRUD)

```html
<div class="p-4">
    <h4>{{ pageTitle }}</h4>
    <p>Selamat datang di halaman example.</p>
</div>
```

**Pola penting:**
- Selalu extends `cubeLayout.html` (Cube theme)
- Gunakan referensi HTML dari `/Documents/git/cube/design/` — jangan tulis CSS sendiri
- Vue components pakai kebab-case: `<tablepack>`, `<gov2formfield-bs4>`
- Data Vue di-bind dengan `:prop="value"` (reactive)

---

## 7. Form Fields — JSON Schema

```json
[
    {
        "name": "cmd",
        "value": "",
        "label": "Submit",
        "type": "hidden"
    },
    {
        "name": "id",
        "value": "",
        "label": "ID",
        "placeholder": "Nomor ID",
        "disabled": "true"
    },
    {
        "name": "nama",
        "value": "",
        "label": "Nama",
        "placeholder": "Masukan nama",
        "required": "true",
        "error_message": "Nama wajib diisi"
    },
    {
        "name": "keterangan",
        "value": "",
        "label": "Keterangan",
        "placeholder": "Keterangan (opsional)"
    },
    {
        "name": "status",
        "value": "active",
        "label": "Status",
        "type": "select",
        "options": {
            "active": "Aktif",
            "inactive": "Nonaktif"
        }
    }
]
```

**Field `cmd` wajib ada** — dipakai framework untuk routing (add/update/del).

**Properties:**

| Property | Keterangan |
|----------|-----------|
| `name` | Nama kolom (sesuai tabel DB) |
| `label` | Label tampilan |
| `type` | `text` (default), `hidden`, `select`, `textarea`, `number` |
| `placeholder` | Hint di input |
| `required` | `"true"` untuk validasi wajib |
| `disabled` | `"true"` untuk read-only |
| `error_message` | Pesan error validasi |
| `options` | Object `{"value": "label"}` untuk select/radio |
| `value` | Default value |

---

## 8. Konvensi Tabel MySQL

### Template CREATE TABLE

```sql
CREATE TABLE IF NOT EXISTS `example` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` varchar(255) NOT NULL DEFAULT '',
    `keterangan` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int UNSIGNED DEFAULT NULL,
    `modify_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `modify_by` int UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Deskripsi singkat tabel';
```

### Aturan Umum

| Aspek | Konvensi |
|-------|----------|
| **Nama tabel** | snake_case, singular (`example`, `daftar_aset`) |
| **Primary key** | `id` — `int UNSIGNED NOT NULL AUTO_INCREMENT` |
| **Engine** | `InnoDB` (default, mendukung foreign key & transaksi) |
| **Charset** | `utf8mb4` (mendukung emoji & karakter unicode penuh) |
| **Comment** | Selalu sertakan `COMMENT='...'` di akhir CREATE TABLE |

### Pola Kolom Standar

#### Identitas & Nama

```sql
`id` int UNSIGNED NOT NULL AUTO_INCREMENT,        -- PK, selalu unsigned
`nama` varchar(255) NOT NULL DEFAULT '',           -- Nama utama
`kode` char(50) NOT NULL,                          -- Kode identitas (fixed-length)
`keterangan` varchar(255) DEFAULT NULL,            -- Deskripsi opsional
`account_id` char(18) DEFAULT NULL,                -- ID akun (government 18 digit)
```

#### Hierarki (Parent-Child)

Untuk data bertingkat (wilayah, instansi, kategori):

```sql
`parent_id` int UNSIGNED NOT NULL DEFAULT 0,       -- 0 = root
`children` smallint UNSIGNED NOT NULL DEFAULT 0,   -- Counter anak (untuk pagination)
`level` tinyint UNSIGNED NOT NULL DEFAULT 0,       -- Kedalaman level (0,1,2,...)
`level_label` enum('level1','level2','level3') NOT NULL DEFAULT 'level1',
```

- `parent_id = 0` artinya root (bukan NULL)
- `children` di-maintain manual (increment saat add, decrement saat delete)
- `level` numerik, `level_label` human-readable

#### Audit Trail (Timestamp)

```sql
`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
`created_by` int UNSIGNED DEFAULT NULL,
`modify_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
`modify_by` int UNSIGNED DEFAULT NULL,
```

- `created_at` + `created_by` = siapa dan kapan buat
- `modify_at` + `modify_by` = siapa dan kapan edit terakhir
- `ON UPDATE CURRENT_TIMESTAMP` otomatis update saat row berubah

#### Status & Enum

```sql
`status` enum('active','inactive') NOT NULL DEFAULT 'active',
`role` enum('guest','member','admin','webmaster') NOT NULL DEFAULT 'guest',
`kategori` enum('tetap','lancar','keuangan','sdm') DEFAULT 'tetap',
```

- Gunakan `enum` untuk pilihan yang **tetap dan terbatas**
- Default value wajib di-set

#### Kolom Regional (Wilayah)

```sql
`provinsi_id` int UNSIGNED DEFAULT NULL,
`kabupaten_id` int UNSIGNED DEFAULT NULL,
`kecamatan_id` int UNSIGNED DEFAULT NULL,
`kelurahan_id` int UNSIGNED DEFAULT NULL,
```

#### Data Fleksibel

```sql
`attr` text DEFAULT NULL,                -- JSON/serialized metadata
`value` text DEFAULT NULL,               -- Nilai generik
`spesifikasi` text DEFAULT NULL,         -- Deskripsi panjang
```

### Index

```sql
-- Single column
KEY `idx_parent_id` (`parent_id`),
KEY `idx_level` (`level`),
KEY `idx_status` (`status`),

-- Composite (untuk query filter kombinasi)
KEY `idx_app_level_status` (`app`, `level`, `status`),

-- Regional
KEY `idx_kabupaten` (`provinsi_id`, `kabupaten_id`),
```

**Aturan index:**
- Prefix `idx_` + nama kolom
- Index kolom yang sering di-WHERE atau di-JOIN
- Composite index untuk query filter kombinasi yang sering dipakai

### Tipe Data — Panduan Pemilihan

| Kebutuhan | Tipe | Catatan |
|-----------|------|---------|
| ID / Primary Key | `int UNSIGNED` | AUTO_INCREMENT |
| ID kecil (<65K rows) | `smallint UNSIGNED` | Hemat storage |
| Nama pendek (≤32 char) | `char(32)` | Fixed-length, lebih cepat |
| Nama/text variabel | `varchar(255)` | Variable-length |
| Text panjang | `text` | Untuk JSON, deskripsi panjang |
| Kode tetap | `char(N)` | N sesuai panjang kode |
| Boolean/level kecil | `tinyint UNSIGNED` | 0-255 |
| Counter sedang | `smallint UNSIGNED` | 0-65535 |
| Pilihan terbatas | `enum('a','b','c')` | Max ~5-10 opsi |
| Tanggal & waktu | `datetime` | DEFAULT CURRENT_TIMESTAMP |
| Angka desimal | `decimal(10,2)` | Untuk uang/presisi |

### Contoh Lengkap — Tabel CRUD Standar

```sql
CREATE TABLE IF NOT EXISTS `barang` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama` varchar(255) NOT NULL DEFAULT '',
    `kode` char(20) NOT NULL DEFAULT '',
    `kategori` enum('elektronik','furniture','kendaraan','lainnya') NOT NULL DEFAULT 'lainnya',
    `jumlah` int UNSIGNED NOT NULL DEFAULT 0,
    `harga` decimal(15,2) DEFAULT NULL,
    `keterangan` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int UNSIGNED DEFAULT NULL,
    `modify_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `modify_by` int UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_kategori` (`kategori`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Data inventaris barang';
```

### Contoh Lengkap — Tabel Hierarkis

```sql
CREATE TABLE IF NOT EXISTS `kategori` (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` int UNSIGNED NOT NULL DEFAULT 0,
    `children` smallint UNSIGNED NOT NULL DEFAULT 0,
    `level` tinyint UNSIGNED NOT NULL DEFAULT 0,
    `level_label` enum('utama','sub') NOT NULL DEFAULT 'utama',
    `nama` varchar(255) NOT NULL DEFAULT '',
    `kode` char(20) DEFAULT NULL,
    `keterangan` varchar(255) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_by` int UNSIGNED DEFAULT NULL,
    `modify_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `modify_by` int UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_parent_id` (`parent_id`),
    KEY `idx_level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Kategori hierarkis';
```

### Yang TIDAK boleh dilakukan

- **Jangan** commit `dsnSource.*.xml` ke git
- **Jangan** pakai `MyISAM` untuk tabel baru (legacy only)
- **Jangan** pakai `latin1` charset untuk tabel baru
- **Jangan** pakai `int(11)` display width — deprecated di MySQL 8.0+
- **Jangan** buat migration framework — SQL dijalankan manual saat setup

---

## 9. Checklist: Membuat App Baru dari Nol

### A. Tanpa Database (view only)

1. [ ] Buat folder `apps/{nama}/`
2. [ ] Buat `xml/route.xml` — minimal 1 GET route
3. [ ] Buat `xml/menu.xml` — entry sidebar
4. [ ] Buat `model/index.php` — extends `\Gov2lib\document`, set templateDir + controller
5. [ ] Buat `index.php` — extends `\Gov2lib\api`, method `index()` dengan `authenticate` + `content()`
6. [ ] Buat `view/body.html` — extends `cubeLayout.html`
7. [ ] Test: buka `/{pageID}` di browser

### B. Dengan Database (CRUD)

1. [ ] Langkah A.1 — A.6 di atas
2. [ ] Buat `sql/{resource}.sql` — CREATE TABLE
3. [ ] Jalankan SQL di MySQL server
4. [ ] Buat `xml/dbTables.xml` — mapping tabel
5. [ ] Buat `dsnSource.dev.xml` di server (MANUAL, jangan commit)
6. [ ] Buat `model/{resource}.php` — extends `\Gov2lib\crudHandler`, set tbl->table
7. [ ] Buat `{resource}.php` — controller CRUD (fields, table, edit, add, update, del)
8. [ ] Buat `json/{resource}.json` — form field definitions
9. [ ] Buat `view/{resource}.html` — template dengan `<tablepack>` dan `<gov2formfield-bs4>`
10. [ ] Tambah route di `xml/route.xml` untuk resource baru
11. [ ] Test: buka `/{pageID}/{resource}` di browser

### C. Opsional

- [ ] `xml/pageroles.xml` — jika perlu access control custom
- [ ] `vue/{resource}.vue` — jika perlu sidepanel atau komponen Vue custom
- [ ] `css/` — custom CSS (sebisa mungkin pakai Cube class saja)
- [ ] `README.md` — dokumentasi app

---

## 10. Tips untuk AI Agent

1. **Selalu baca kode existing** sebelum generate — jangan asumsi pattern
2. **Cube theme**: pakai class dan pattern dari Cube (Bootstrap 5 theme layer), jangan tulis CSS custom kecuali perlu
3. **Jangan commit** file `dsnSource.*.xml` atau credential apapun
4. **Test di browser** setelah setiap langkah — jangan batch semua perubahan
5. **`dependencies()` wajib ada** di setiap model class (bahkan jika kosong)
6. **`parent::__construct()` di akhir** constructor controller
7. **`parent::__construct($dsn)` di akhir** constructor model (setelah set templateDir dll)
8. **URL di vueData harus full path** — `/gov2example/example`, bukan `example`
9. **authenticate level**: `public` (tanpa auth) → `guest` (login) → `admin` (admin) → `webmaster`
10. **Push default**: `git push ayam master` — ke GitHub hanya jika diminta eksplisit
