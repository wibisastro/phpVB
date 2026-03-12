# CLAUDE_PHPVB.md — Install & Update Framework phpVB

File ini dibaca oleh **Claude Code** untuk setup dan update framework phpVB.
Semua instruksi berbasis kode aktual.

**Template files** di `core/scripts/` (masuk git, aman di-copy):
- `_dsnSource.dev.xml` — template koneksi database
- `_htaccess` — template rewrite rules
- `_config.dev.xml` — template config stage
- `_menu.dev.xml` — template menu sidebar

---

# BAGIAN A: FRESH INSTALL

## Urutan Instalasi

```
0. Tanya info ke user (domain, database)
1. composer install
2. Buat/edit config.{STAGE}.xml — daftarkan domain
3. Buat public/.htaccess (copy dari core/scripts/_htaccess)
4. Buat dsnSource.{STAGE}.xml untuk setiap app yang butuh DB
5. Verifikasi
```

---

## Langkah 0: Tanya Info ke User

Sebelum mulai, Claude Code **WAJIB tanya** ke user:

```
1. Apa domain/hostname server ini?
   (contoh: ayam.gov2.web.id, dev.local, 192.168.1.100)

2. Stage mana yang dipakai? (default: dev)
   - local  → bypass semua auth, display_errors ON
   - dev    → auth penuh, display_errors ON, error level bisa diatur via ?error=all|warning
   - prod   → production mode

3. Apakah sudah ada database MySQL/MariaDB?
   - Jika ya: hostname, port, username, password, nama database
   - Jika belum: mau setup lokal atau cloud?

4. (Opsional) Apakah perlu HTTPS? (config <secure>true</secure>)
```

**Simpan jawaban ke memory** untuk session berikutnya.

---

## Langkah 1: composer install

```bash
cd /path/to/phpVB
composer install
```

**Mengapa ini duluan?** `core/init/index.php` line 15 langsung `require vendor/autoload.php`. Tanpa ini, fatal error.

**Require PHP ^8.4** — cek dulu:
```bash
php -v
```

---

## Langkah 2: Setup Domain di Config XML

### Bagaimana STAGE Ditentukan

STAGE **bukan** dari `.htaccess` atau environment variable. Logikanya ada di `core/config/index.php`:

```php
$stages = array('dev');  // line 13 — daftar stage yang dicek
foreach ($stages as $stage) {
    $config = simplexml_load_file("config.{$stage}.xml");
    if ($config->domain->{$_SERVER["SERVER_NAME"]}) {  // line 18
        define('STAGE', $stage);  // line 19
        break;
    }
}
```

**Artinya**: hostname request (`$_SERVER["SERVER_NAME"]`) harus ada sebagai tag di `<domain>` dalam salah satu file `config.{STAGE}.xml`.

### Apa yang harus dilakukan

Edit file `core/config/config.dev.xml` (atau buat baru dari template `core/scripts/_config.dev.xml`), tambahkan domain baru di blok `<domain>`:

```xml
<domain>
    <ayam.gov2.web.id>home</ayam.gov2.web.id>
    <!-- Tambah domain baru di sini: -->
    <DOMAIN_BARU>home</DOMAIN_BARU>
</domain>
```

Value `home` = **default app** yang diload saat akses root domain.

**Catatan**: template `_config.dev.xml` belum berisi blok `<domain>`, harus ditambahkan manual.

### Perhatikan: $stages array

Di `core/config/index.php` line 13, array `$stages` hanya berisi stage yang dicek. Jika pakai stage `local`, tambahkan:

```php
$stages = array('local', 'dev');
```

Dan pastikan file `config.local.xml` ada di `core/config/`.

### Jika domain tidak match

Error: `UnConfiguredDomain:{hostname}` — hostname request tidak ada di config XML manapun.

---

## Langkah 3: Buat public/.htaccess

File `public/.htaccess` **di-gitignore** (`/public/.htaccess`). Harus dibuat manual.

**Copy dari template**:
```bash
cp core/scripts/_htaccess public/.htaccess
```

Isi file:
```apache
RewriteEngine on

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php/$1 [NC,L]
```

**Pastikan** `mod_rewrite` aktif di Apache.

---

## Langkah 4: Buat dsnSource Files

### Mengapa Error NoDSNConfigFile Terjadi

Model class yang `extends crudHandler` memanggil `connectDB()`, yang mencari:

```
apps/{pageID}/xml/dsnSource.{STAGE}.xml
```

Jika tidak ada → `NoDSNConfigFile: apps/{pageID}/xml/dsnSource.{STAGE}.xml tidak ditemukan`

### Template: core/scripts/_dsnSource.dev.xml

File template masuk git (prefix `_`, bukan `dsn`):

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!--
	Contoh file dsnSource.dev.xml
	Salin ke: apps/{appID}/xml/dsnSource.dev.xml
	Ganti nilai host, user, pass, db sesuai environment.
	File ini di-gitignore (**/dsn*.xml) — JANGAN git add -f.
-->
<list>
	<dsn>
		<name>master</name>
		<host>localhost</host>
		<user>db_user</user>
		<pass>GANTI_PASSWORD_DISINI</pass>
		<db>nama_database</db>
	</dsn>
	<dsn>
		<name>ayam.gov2.web.id</name>
		<host>localhost</host>
		<user>db_user</user>
		<pass>GANTI_PASSWORD_DISINI</pass>
		<db>nama_database</db>
	</dsn>
</list>
```

### Format dsnSource — Penting

**Tag XML**: `<name>`, `<host>`, `<user>`, `<pass>`, `<db>`
**BUKAN**: `<hostname>`, `<username>`, `<password>`, `<database>`

**Root element**: `<list>`

**Harus ada 2 DSN entry**:
- `<name>master</name>` — fallback saat `connectDB('master')`
- `<name>DOMAIN_SERVER</name>` — dipakai default, karena `$config->domain->attr['dsn'] = $_SERVER["SERVER_NAME"]` (line 71 `core/config/index.php`)

Kedua entry boleh credentials sama — yang penting `<name>` berbeda.

### Cara Buat

1. Copy template ke app master dan edit:
```bash
cp core/scripts/_dsnSource.dev.xml apps/gov2login/xml/dsnSource.dev.xml
# Edit: ganti db_user, GANTI_PASSWORD_DISINI, nama_database, ayam.gov2.web.id
```

2. Untuk app lain, buat file share:
```bash
SHARE='<?xml version="1.0" encoding="UTF-8"?>
<list>
  <share>gov2login</share>
</list>'

for app in home gov2wilayah gov2instansi gov2option gov2survey gov2pipe components; do
    echo "$SHARE" > "apps/$app/xml/dsnSource.dev.xml"
done
```

Tag `<share>gov2login</share>` artinya: pakai dsnSource dari `apps/gov2login/xml/dsnSource.dev.xml`.

### Apps yang Butuh dsnSource

| App | Butuh dsnSource |
|-----|------|
| `gov2login` | **YA — MASTER** (admin, member, guest, owner, privilege, ref_unitkerja, ref_user, webmaster) |
| `gov2wilayah` | YA (wilayah, sidepanel) |
| `gov2instansi` | YA (instansi) |
| `gov2option` | YA (index, option, controlpanel, instansi, provider, receiver) |
| `gov2survey` | YA (index, survey, survey_view, kuesioner, api) |
| `gov2pipe` | YA (index, pipedin) |
| `home` | YA (crud) |
| `components` | YA (gov2table) |

### JANGAN Commit

`.gitignore`: `**/dsn*.xml` — semua file dsnSource diabaikan git.
Template `_dsnSource.dev.xml` aman karena prefix `_`.

---

## Langkah 5: Verifikasi

### Cek dsnSource lengkap

```bash
for app in gov2login gov2wilayah gov2instansi gov2option gov2survey gov2pipe home components; do
    FILE="apps/$app/xml/dsnSource.dev.xml"
    [ -f "$FILE" ] && echo "OK  $FILE" || echo "MISSING  $FILE"
done
```

### Cek database connection

```bash
php -r "
\$link = mysqli_connect('HOSTNAME', 'USERNAME', 'PASSWORD', 'DATABASE', 3306);
echo \$link ? 'OK Database connected' : 'FAIL ' . mysqli_connect_error();
"
```

### Checklist Final

- [ ] `composer install` berhasil, folder `vendor/` ada
- [ ] Domain terdaftar di `core/config/config.{STAGE}.xml` → `<domain>`
- [ ] `public/.htaccess` ada (copy dari `core/scripts/_htaccess`)
- [ ] mod_rewrite aktif di Apache
- [ ] `dsnSource.{STAGE}.xml` ada untuk semua app yang butuh DB
- [ ] dsnSource punya 2 entry: `master` + hostname domain
- [ ] Tag `<name>` hostname di dsnSource **= persis sama** dengan tag domain di config XML
- [ ] Database bisa diakses (hostname, port, user, pass benar)
- [ ] Halaman `http://DOMAIN/home/` bisa diakses tanpa error

---

# BAGIAN B: UPDATE FRAMEWORK

## Kapan Update Diperlukan

- Setelah `git pull` dari remote (ada perubahan di `core/`, `composer.json`, dll)
- Setelah upgrade PHP version
- Setelah tambah dependency baru

## Langkah Update

### 1. git pull

```bash
cd /path/to/phpVB
git pull ayam master
```

### 2. composer update

```bash
composer update
```

Jika hanya install dependency baru tanpa upgrade existing:
```bash
composer install
```

### 3. Cek perubahan config

Setelah pull, cek apakah ada perubahan di:
- `core/config/index.php` — apakah `$stages` array berubah?
- `composer.json` — ada dependency baru?
- `core/lib/` — ada class baru atau method signature berubah?

### 4. Database migration

Cek apakah ada file SQL baru di:
```bash
ls -la core/scripts/sql/
ls -la apps/*/sql/
```

Jika ada file SQL baru, tanya user apakah perlu dijalankan.

### 5. Verify setelah update

- Akses `http://DOMAIN/home/` — tidak ada error
- Cek PHP error log untuk warning/error baru
- Jika ada error class signature mismatch → lihat section PHP 8.4 Compatibility

## PHP 8.4 Compatibility

Method override harus cocok signature parent **persis**: tambah type hints + return type.

Contoh error:
```
Declaration of App\xxx\model\yyy::method() must be compatible with Gov2lib\crudHandler::method(): returnType
```

Fix: samakan signature method child dengan parent persis.

---

# ERROR REFERENCE

| Error | Penyebab | Fix |
|-------|---------|-----|
| `Undefined constant "STAGE"` | Domain tidak ada di config XML | Langkah 2: tambahkan domain di `<domain>` block |
| `UnConfiguredDomain:{host}` | STAGE belum ada di switch-case | Tambah case di `core/config/index.php` line 31 |
| `ConfigFileNotExist:config.dev.xml` | File config tidak ada | Copy `core/scripts/_config.dev.xml` ke `core/config/`, edit |
| `NoDSNConfigFile: apps/{app}/xml/...` | dsnSource belum dibuat | Copy `core/scripts/_dsnSource.dev.xml`, edit |
| `DSNEntryNotFound: Entry '{name}'...` | `<name>` di dsnSource tidak match hostname | Pastikan `<name>` = `$_SERVER["SERVER_NAME"]` |
| `CannotConnectDSN:{mysql error}` | Credentials salah atau MySQL mati | Cek host/port/user/pass di dsnSource |
| `InvalidDSNConfigFile` | XML rusak | Cek format: root `<list>`, tag tertutup |
| `DSNShareFileNotExist` | `<share>` target tidak ada | Buat dsnSource di app yang di-share |
| Fatal error: vendor/autoload.php | composer belum dijalankan | `composer install` |

---

# CATATAN UNTUK CLAUDE CODE

1. **Selalu tanya user** untuk: domain, stage, database credentials. Jangan assume.
2. **Pakai template dari `core/scripts/`** — jangan tulis XML dari nol.
3. **dsnSource butuh 2 entry**: `master` + hostname domain.
4. **Tag `<name>` hostname = persis sama** dengan tag di config XML `<domain>`.
5. **Root element dsnSource**: `<list>`.
6. **Pakai share pattern** — 1 master dsnSource di `gov2login`, sisanya `<share>`.
7. **Jangan commit** `dsnSource*.xml` dan `public/.htaccess` — keduanya di-gitignore.
8. **Simpan credentials ke memory** (hint saja, bukan password langsung).
9. **Error NoDSNConfigFile saat runtime**: parse nama app, buat file share ke `gov2login`.

---

**Last Updated**: 12 Mar 2026
