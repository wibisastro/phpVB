# CLAUDE_APPS.md — Panduan Install / Update Aplikasi di phpVB untuk Claude Code

File ini dibaca oleh **Claude Code** saat menambahkan aplikasi pihak ketiga ke instalasi phpVB.
Aplikasi adalah git repo terpisah yang di-clone ke folder `apps/`.

**Prasyarat**: phpVB sudah ter-install dan berjalan (lihat `CLAUDE_PHPVB.md`).

---

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

Sebelum mulai, Claude Code **WAJIB tanya**:

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

## Langkah 2: Buat dsnSource

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

## Langkah 3: Setup Database

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

## Langkah 5: Verifikasi

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

## Troubleshooting

| Error | Penyebab | Fix |
|-------|---------|-----|
| `NoDSNConfigFile: apps/{appID}/xml/dsnSource.dev.xml` | File dsnSource belum dibuat | Langkah 2: buat file dsnSource |
| `DSNEntryNotFound` | Tag `<name>` di dsnSource tidak match domain | Pastikan `<name>` = `$_SERVER["SERVER_NAME"]` |
| `CannotConnectDSN` | Credentials salah atau DB tidak ada | Cek host/user/pass/db di dsnSource |
| 404 pada route app | route.xml tidak terbaca atau path salah | Cek `xml/route.xml` ada dan URI prefix = `/{appID}/` |
| Menu sidebar kosong | menu.xml tidak ada atau format salah | Cek `xml/menu.xml` dan pastikan tag `<mainmenu>` |
| `Table doesn't exist` | Tabel belum di-import | Langkah 3: import `sql/table.sql` |
| `UnConfiguredDomain` (portal terpisah) | Domain portal belum di-config | Langkah 4: tambah domain di `config.{STAGE}.xml` |

---

## Contoh: Install subsidibbm

```bash
# 1. Clone
cd /path/to/phpVB/apps
git clone git@github.com:user/subsidibbm.git subsidibbm

# 2. dsnSource (DB sendiri)
cp core/scripts/_dsnSource.dev.xml apps/subsidibbm/xml/dsnSource.dev.xml
# Edit: ganti DB_USER, DB_PASSWORD, DB_NAME, DOMAIN_SERVER

# 3. Database
mysql -u root -p -e "CREATE DATABASE subsidibbm_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p subsidibbm_dev < apps/subsidibbm/sql/table.sql

# 4. Portal (jika pakai domain sendiri)
# Edit core/config/config.dev.xml → tambah <sdi.gov2.web.id>subsidibbm</sdi.gov2.web.id>

# 5. Verifikasi
# Akses http://DOMAIN/subsidibbm/ di browser
```

---

## Catatan untuk Claude Code

1. **Selalu tanya user** untuk: repo URL, database credentials, domain portal. Jangan assume.
2. **Clone ke `apps/`** — jangan copy manual, jangan git submodule.
3. **dsnSource harus dibuat manual** — file ini di-gitignore, tidak akan datang dari clone.
4. **Cek `sql/table.sql`** di repo app — jika ada, tanya user apakah perlu import.
5. **Tanya user apakah app jalan di domain terpisah** — jika ya, perlu tambah domain di `config.{STAGE}.xml`.
6. **App folder = repo sendiri** — `git push/pull` di folder app terpisah dari phpVB.
7. **Jangan add folder app ke repo phpVB** — biarkan sebagai untracked.
8. **Simpan info app ke memory** — nama app, repo URL, DB name, domain portal.
9. **Saat update**: `git pull` di folder app, cek `sql/` untuk migrasi, pastikan dsnSource masih valid.
10. **Jika app butuh tabel shared** (member, wilayah, dll): cek `dbTables.xml`, tanya user apakah DB sendiri atau share.

---

**Last Updated**: 12 Mar 2026
