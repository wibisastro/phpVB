# 4AGENT.md — Panduan Install & Update phpVB untuk AI Agent

File ini dibaca oleh **AI coding agent** (Claude Code, Gemini CLI, OpenClaw, dll) untuk setup framework phpVB dan install aplikasi pihak ketiga.
Semua instruksi berbasis kode aktual.

**Template files** di `core/scripts/` (masuk git, aman di-copy):
- `_dsnSource.dev.xml` — template koneksi database
- `_htaccess` — template rewrite rules
- `_config.dev.xml` — template config stage
- `_menu.dev.xml` — template menu sidebar

---

# BAGIAN A: FRESH INSTALL FRAMEWORK

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

Sebelum mulai, Agent **WAJIB tanya** ke user:

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

**Simpan jawaban ke memory** (jika agent mendukung) untuk session berikutnya.

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

## Upgrade ke Versi Baru phpVB

### Kapan Upgrade (Bukan Update Biasa)

Upgrade dilakukan ketika versi phpVB baru di-release dan perlu mengganti seluruh framework. Berbeda dengan **update** (git pull), upgrade berarti mengambil source code baru secara keseluruhan — misalnya dari git clone atau download release.

### ⚠ PERINGATAN: File yang TIDAK BOLEH Ditimpa

Jika langsung `git clone` ke folder yang sama, file-file berikut akan **hilang** karena di-gitignore (tidak ada di repo):

| File | Lokasi | Isi |
|------|--------|-----|
| `config.{STAGE}.xml` | `core/config/` | Domain mapping, konfigurasi stage |
| `dsnSource.{STAGE}.xml` | `apps/*/xml/` | Credentials database (per-app) |
| `.htaccess` | `public/` | Rewrite rules Apache |
| `apps/{appPihakKetiga}/` | `apps/` | Aplikasi pihak ketiga (repo sendiri) |

### Prosedur Upgrade

```
1. Backup file credential & config
2. Clone/download versi baru ke folder terpisah
3. Salin kembali file credential & config
4. composer install/update
5. Verifikasi
```

### Langkah 1: Backup File Credential & Config

Sebelum upgrade, **backup semua file yang di-gitignore**:

```bash
cd /path/to/phpVB

# Buat folder backup
BACKUP_DIR="/tmp/phpVB_backup_$(date +%Y%m%d%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup config stage
cp core/config/config.*.xml "$BACKUP_DIR/" 2>/dev/null

# Backup semua dsnSource
for dsn in $(find apps -name "dsnSource.*.xml"); do
    mkdir -p "$BACKUP_DIR/$(dirname $dsn)"
    cp "$dsn" "$BACKUP_DIR/$dsn"
done

# Backup .htaccess
cp public/.htaccess "$BACKUP_DIR/" 2>/dev/null

# Catat daftar app pihak ketiga (yang punya .git sendiri)
ls -d apps/*/.git 2>/dev/null | sed 's|apps/||;s|/.git||' > "$BACKUP_DIR/app_list.txt"

echo "Backup selesai di: $BACKUP_DIR"
```

### Langkah 2: Clone/Download Versi Baru

```bash
# Opsi A: Clone ke folder baru, lalu ganti
git clone <REPO_URL> /path/to/phpVB_new

# Opsi B: Jika upgrade di tempat (folder sama)
# Hapus file framework saja, JANGAN hapus apps/ dan file config
cd /path/to/phpVB
# Hapus hanya folder framework (bukan apps, bukan config)
rm -rf core/ vendor/ composer.json composer.lock
git clone <REPO_URL> /tmp/phpVB_new
# Copy file framework dari clone baru
cp -r /tmp/phpVB_new/core /tmp/phpVB_new/composer.* /tmp/phpVB_new/public/index.php .
```

### Langkah 3: Kembalikan File Credential & Config

```bash
# Restore config stage
cp "$BACKUP_DIR"/config.*.xml core/config/

# Restore semua dsnSource
for dsn in $(find "$BACKUP_DIR/apps" -name "dsnSource.*.xml" 2>/dev/null); do
    TARGET="${dsn#$BACKUP_DIR/}"
    cp "$dsn" "$TARGET"
done

# Restore .htaccess
cp "$BACKUP_DIR/.htaccess" public/.htaccess 2>/dev/null
```

### Langkah 4: composer install

```bash
cd /path/to/phpVB
composer install
```

Jika `composer.json` berubah dari versi sebelumnya, kemungkinan ada dependency baru.

### Langkah 5: Verifikasi

```bash
# Cek config ada
ls core/config/config.*.xml

# Cek dsnSource masih ada
for app in gov2login gov2wilayah gov2instansi gov2option; do
    FILE="apps/$app/xml/dsnSource.dev.xml"
    [ -f "$FILE" ] && echo "OK  $FILE" || echo "MISSING  $FILE"
done

# Cek .htaccess
[ -f "public/.htaccess" ] && echo "OK  .htaccess" || echo "MISSING  .htaccess"

# Cek app pihak ketiga masih ada
cat "$BACKUP_DIR/app_list.txt" 2>/dev/null
```

Akses `http://DOMAIN/home/` — pastikan tidak ada error.

### Checklist Upgrade

- [ ] Backup `config.{STAGE}.xml` sebelum upgrade
- [ ] Backup semua `dsnSource.{STAGE}.xml` sebelum upgrade
- [ ] Backup `public/.htaccess` sebelum upgrade
- [ ] App pihak ketiga di `apps/` tidak terhapus
- [ ] File credential & config sudah dikembalikan setelah upgrade
- [ ] `composer install` berhasil
- [ ] Halaman bisa diakses tanpa error

### CATATAN: Jangan Clone Langsung ke Folder Production

**JANGAN** lakukan ini:
```bash
# SALAH — akan menghapus semua file gitignored!
rm -rf /path/to/phpVB
git clone <REPO_URL> /path/to/phpVB
```

Selalu backup dulu, atau clone ke folder terpisah lalu salin file framework saja.

---

# BAGIAN C: INSTALL APLIKASI PIHAK KETIGA

**Prasyarat**: phpVB sudah ter-install dan berjalan (Bagian A selesai).

## Konsep

Setiap aplikasi di phpVB adalah folder di `apps/{appID}/` dengan struktur:

```
apps/{appID}/
├── index.php          # Controller utama (extends \Gov2lib\api)
├── model/             # Model classes (extends \Gov2lib\crudHandler atau \Gov2lib\document)
├── view/              # Twig templates (body.html, index.html, dll)
├── vue/               # Vue SFC components
├── css/               # Custom CSS (opsional)
├── js/                # Custom JS (opsional)
├── json/              # JSON data files (opsional)
├── lib/               # Helper classes (opsional)
├── sql/               # SQL schema files (referensi, tidak auto-execute)
│   └── table.sql      # CREATE TABLE statements
├── xml/
│   ├── route.xml      # Routing definitions
│   ├── menu.xml       # Sidebar menu
│   ├── pageroles.xml  # Access control per-page
│   ├── dbTables.xml   # Daftar tabel yang dipakai app
│   ├── header.xml     # Header icons (opsional)
│   ├── superuser.xml  # Superuser list (opsional)
│   └── userroles.xml  # User role definitions (opsional)
└── .git/              # Repo sendiri (submodule-like, tapi bukan git submodule)
```

Framework **auto-discover** app berdasarkan folder di `apps/` — tidak perlu registrasi.

---

## Urutan Install Aplikasi Baru

```
1. Tanya info ke user (repo URL, database)
2. Clone repo ke apps/
3. Buat dsnSource.{STAGE}.xml
4. Setup database (buat DB + import tabel)
5. Konfigurasi domain portal (jika app jalan di domain terpisah)
6. Verifikasi
```

---

## Langkah 0: Tanya Info ke User

Sebelum mulai, agent **WAJIB tanya**:

```
1. Apa URL git repo aplikasi?
   (contoh: git@github.com:user/subsidibbm.git, https://...)

2. Nama folder di apps/ = nama repo (otomatis dari git clone, JANGAN beri nama lain)

3. Apakah app ini pakai database sendiri atau share dengan gov2login?
   - Jika sendiri: nama database, host, user, password
   - Jika share: akan pakai <share>gov2login</share>

4. Apakah perlu import tabel? (cek apakah ada folder sql/ di repo)

5. Apakah app ini jalan di domain/portal terpisah?
   - Jika ya: domain portal apa? (perlu daftarkan di config.{STAGE}.xml)
   - Jika tidak: app terintegrasi di portal utama, diakses via /{appID}/
```

**Simpan jawaban ke memory** untuk session berikutnya.

---

## Langkah 1: Clone Repo ke apps/

```bash
cd /path/to/phpVB/apps
git clone <REPO_URL> {appID}
```

**Contoh**:
```bash
cd /path/to/phpVB/apps
git clone git@github.com:user/subsidibbm.git subsidibbm
```

### Verifikasi struktur

```bash
ls apps/{appID}/xml/
# Harus ada minimal: route.xml, menu.xml
```

### PENTING: App adalah repo independen

- App punya `.git/` sendiri — **bukan** git submodule phpVB
- `git pull` / `git push` di `apps/{appID}/` beroperasi pada repo app
- `git status` di root phpVB akan melihat `apps/{appID}/` sebagai untracked directory — **ini normal, jangan add ke repo phpVB**

---

## Langkah 2: Buat dsnSource untuk App

File `dsnSource.{STAGE}.xml` di-gitignore (`**/dsn*.xml`) — **harus dibuat manual di server**.

### Opsi A: Database sendiri (app punya DB terpisah)

Copy template dan edit:
```bash
cp core/scripts/_dsnSource.dev.xml apps/{appID}/xml/dsnSource.dev.xml
```

Edit file — ganti placeholder:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<list>
	<dsn>
		<name>master</name>
		<host>localhost</host>
		<user>DB_USER</user>
		<pass>DB_PASSWORD</pass>
		<db>DB_NAME</db>
	</dsn>
	<dsn>
		<name>DOMAIN_SERVER</name>
		<host>localhost</host>
		<user>DB_USER</user>
		<pass>DB_PASSWORD</pass>
		<db>DB_NAME</db>
	</dsn>
</list>
```

**Tag `<name>` pada DSN kedua** harus **persis sama** dengan hostname domain di `config.{STAGE}.xml`.

### Opsi B: Share dengan gov2login

Jika app pakai database yang sama dengan framework:
```bash
cat > apps/{appID}/xml/dsnSource.dev.xml << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<list>
  <share>gov2login</share>
</list>
EOF
```

### Kapan Pakai Opsi A vs B?

- **Opsi A** — app punya tabel di database terpisah (contoh: `subsidibbm` punya DB `sdi_kukarkab`)
- **Opsi B** — app pakai tabel di database yang sama dengan framework (contoh: `gov2wilayah`, `gov2instansi`)
- Cek `sql/table.sql` atau `xml/dbTables.xml` di repo app untuk mengetahui tabel apa yang dipakai

### JANGAN commit dsnSource

File ini berisi password — sudah di-gitignore (`**/dsn*.xml`). **JANGAN pernah `git add -f`**.

---

## Langkah 3: Setup Database untuk App

### Cek apakah app butuh tabel baru

```bash
ls apps/{appID}/sql/
# Jika ada table.sql → perlu import
```

### Buat database (jika pakai DB sendiri)

```sql
CREATE DATABASE {db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### Import tabel

```bash
mysql -u {user} -p {db_name} < apps/{appID}/sql/table.sql
```

Atau jika ada file terpisah:
```bash
mysql -u {user} -p {db_name} < apps/{appID}/sql/table.sql
mysql -u {user} -p {db_name} < apps/{appID}/sql/view.sql    # SQL views (opsional)
mysql -u {user} -p {db_name} < apps/{appID}/sql/copy.sql    # Data awal (opsional)
```

### Cek tabel shared dengan framework

Beberapa tabel mungkin sudah ada di database phpVB:
- `member` — tabel user, biasanya sudah ada dari `gov2login`
- `wilayah` — data wilayah, dari `gov2wilayah`
- `instansi` — data instansi, dari `gov2instansi`
- `options` — konfigurasi app, dari `gov2option`

Jika app pakai DB terpisah tapi butuh tabel ini, ada 2 opsi:
1. **Import ulang** tabel tersebut ke DB app (data terpisah)
2. **Pakai view/federated** yang merujuk ke DB framework (advanced)

Cek `xml/dbTables.xml` untuk melihat tabel apa yang direferensikan app.

---

## Langkah 4: Konfigurasi Domain Portal (Opsional)

Jika app jalan di domain/portal terpisah (bukan di domain utama phpVB), perlu daftarkan domain di config.

### Jika app jalan di portal terpisah

1. Pastikan domain portal terdaftar di `core/config/config.{STAGE}.xml`:
```xml
<domain>
    <ayam.gov2.web.id>home</ayam.gov2.web.id>
    <!-- Tambah domain portal app: -->
    <sdi.gov2.web.id>subsidibbm</sdi.gov2.web.id>
</domain>
```

**Value** = nama folder app (bukan `home`). Ini menentukan app mana yang jadi default saat domain diakses.

2. DSN entry `<name>` juga harus cocok dengan domain portal:
   - Satu entry `<name>master</name>`
   - Satu entry `<name>sdi.gov2.web.id</name>` (domain portal)

### Jika app terintegrasi di portal utama

Tidak perlu ubah config domain — app diakses via path `/{appID}/...` di domain utama.

---

## Langkah 5: Verifikasi App

### Cek file-file wajib

```bash
APP="subsidibbm"  # ganti sesuai appID

echo "=== Cek struktur ==="
for f in "xml/route.xml" "xml/menu.xml" "xml/pageroles.xml"; do
    [ -f "apps/$APP/$f" ] && echo "OK  $f" || echo "MISSING  $f"
done

echo ""
echo "=== Cek dsnSource ==="
STAGE="dev"  # ganti sesuai stage
DSN="apps/$APP/xml/dsnSource.$STAGE.xml"
[ -f "$DSN" ] && echo "OK  $DSN" || echo "MISSING  $DSN"

echo ""
echo "=== Cek SQL ==="
[ -d "apps/$APP/sql" ] && ls apps/$APP/sql/ || echo "Tidak ada folder sql/"
```

### Cek akses browser

```
http://DOMAIN/{appID}/          → Halaman utama app
http://DOMAIN/{appID}/index     → Controller index
```

### Checklist

- [ ] Repo berhasil di-clone ke `apps/{appID}/`
- [ ] `xml/route.xml` dan `xml/menu.xml` ada
- [ ] `dsnSource.{STAGE}.xml` sudah dibuat (manual, bukan dari git)
- [ ] Database dan tabel sudah ada (import dari `sql/table.sql` jika perlu)
- [ ] (Jika portal terpisah) Domain terdaftar di `config.{STAGE}.xml`
- [ ] Halaman app bisa diakses tanpa error

---

## Update Aplikasi

### Pull versi terbaru

```bash
cd /path/to/phpVB/apps/{appID}
git pull origin main     # atau master, sesuai branch repo app
```

### Cek migrasi database

Setelah pull, cek apakah ada perubahan SQL:
```bash
git log --oneline --name-only -- sql/
# Jika ada file baru/berubah di sql/, jalankan migrasi manual
```

### JANGAN lakukan

- Jangan `git add` folder app ke repo phpVB
- Jangan commit `dsnSource*.xml` dari app
- Jangan hapus `.git/` di folder app (itu repo app sendiri)

---

# ERROR REFERENCE

| Error | Penyebab | Fix |
|-------|---------|-----|
| `Undefined constant "STAGE"` | Domain tidak ada di config XML | Bagian A Langkah 2: tambahkan domain di `<domain>` block |
| `UnConfiguredDomain:{host}` | STAGE belum ada di switch-case | Tambah case di `core/config/index.php` line 31 |
| `ConfigFileNotExist:config.dev.xml` | File config tidak ada | Copy `core/scripts/_config.dev.xml` ke `core/config/`, edit |
| `NoDSNConfigFile: apps/{app}/xml/...` | dsnSource belum dibuat | Copy `core/scripts/_dsnSource.dev.xml`, edit |
| `DSNEntryNotFound: Entry '{name}'...` | `<name>` di dsnSource tidak match hostname | Pastikan `<name>` = `$_SERVER["SERVER_NAME"]` |
| `CannotConnectDSN:{mysql error}` | Credentials salah atau MySQL mati | Cek host/port/user/pass di dsnSource |
| `InvalidDSNConfigFile` | XML rusak | Cek format: root `<list>`, tag tertutup |
| `DSNShareFileNotExist` | `<share>` target tidak ada | Buat dsnSource di app yang di-share |
| Fatal error: vendor/autoload.php | composer belum dijalankan | `composer install` |
| 404 pada route app | route.xml tidak terbaca atau path salah | Cek `xml/route.xml` ada dan URI prefix = `/{appID}/` |
| Menu sidebar kosong | menu.xml tidak ada atau format salah | Cek `xml/menu.xml` dan pastikan tag `<mainmenu>` |
| `Table doesn't exist` | Tabel belum di-import | Import `sql/table.sql` |
| `UnConfiguredDomain` (portal terpisah) | Domain portal belum di-config | Tambah domain di `config.{STAGE}.xml` |

---

# CATATAN UNTUK AGENT

1. **Selalu tanya user** untuk: domain, stage, database credentials, repo URL. Jangan assume.
2. **Pakai template dari `core/scripts/`** — jangan tulis XML dari nol.
3. **dsnSource butuh 2 entry**: `master` + hostname domain.
4. **Tag `<name>` hostname = persis sama** dengan tag di config XML `<domain>`.
5. **Root element dsnSource**: `<list>`.
6. **Pakai share pattern** — 1 master dsnSource di `gov2login`, sisanya `<share>`.
7. **Jangan commit** `dsnSource*.xml` dan `public/.htaccess` — keduanya di-gitignore.
8. **Simpan credentials ke memory** (hint saja, bukan password langsung).
9. **Error NoDSNConfigFile saat runtime**: parse nama app, buat file share ke `gov2login`.
10. **Clone app ke `apps/`** — jangan copy manual, jangan git submodule.
11. **Cek `sql/table.sql`** di repo app — jika ada, tanya user apakah perlu import.
12. **App folder = repo sendiri** — `git push/pull` di folder app terpisah dari phpVB.
13. **Jangan add folder app ke repo phpVB** — biarkan sebagai untracked.

---

**Last Updated**: 13 Mar 2026
