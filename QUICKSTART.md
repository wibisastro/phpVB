# phpVB Quick Start Installation Guide

Panduan instalasi dari awal (fresh server) untuk menjalankan **phpVB** di Ubuntu/Debian.

---

## Prasyarat

- Ubuntu 22.04 / 24.04 (atau Debian equivalen)
- Apache2
- PHP 8.4
- Composer
- Git

---

## 1. Install Dependensi Sistem

```bash
apt-get update
apt-get install -y git unzip

# Install PHP 8.4 (via Ondřej PPA untuk Ubuntu)
add-apt-repository ppa:ondrej/php -y
apt-get update
apt-get install -y php8.4 libapache2-mod-php8.4 \
    php8.4-xml php8.4-zip php8.4-mbstring php8.4-curl php8.4-mysql
```

---

## 2. Aktifkan Modul Apache

```bash
a2enmod rewrite
systemctl restart apache2
```

---

## 3. Clone Repository

```bash
mkdir -p /var/dev
git clone https://github.com/wibisastro/phpVB /var/dev/phpVB
```

---

## 4. Install Composer & Dependencies

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies
cd /var/dev/phpVB
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction
```

---

## 5. Konfigurasi Apache VirtualHost

Buat file `/etc/apache2/sites-available/dev.cybergl.co.id.conf`:

```apache
<VirtualHost *:80>
    ServerName dev.cybergl.co.id

    DocumentRoot /var/dev/phpVB/public

    <Directory /var/dev/phpVB/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dev.cybergl.co.id-error.log
    CustomLog ${APACHE_LOG_DIR}/dev.cybergl.co.id-access.log combined
</VirtualHost>
```

Aktifkan site:

```bash
a2ensite dev.cybergl.co.id
systemctl reload apache2
```

---

## 6. Konfigurasi Domain di phpVB

Edit file `core/config/config.local.xml`, tambahkan domain Anda di dalam tag `<domain>`:

```xml
<domain>
    <your.domain.com>home</your.domain.com>
</domain>
```

> **Penting:** Nama tag harus sama persis dengan `SERVER_NAME` / domain yang diakses browser.

---

## 7. Struktur Konfigurasi

File konfigurasi ada di `core/config/`:

| File | Keterangan |
|------|-----------|
| `config.local.xml` | Untuk environment lokal/dev |
| `config.dev.xml` | Untuk environment development |
| `config.prod.xml` | Untuk environment production |

Pastikan nama domain sudah terdaftar di file XML yang sesuai dengan stage yang digunakan.

---

## 8. Ekstensi PHP yang Dibutuhkan

| Ekstensi | Package |
|----------|---------|
| SimpleXML, DOM, XML | `php8.4-xml` |
| Zip | `php8.4-zip` |
| Mbstring | `php8.4-mbstring` |
| cURL | `php8.4-curl` |
| MySQLi / PDO MySQL | `php8.4-mysql` |

Cek ekstensi yang sudah aktif:

```bash
php -m
```

---

## 9. Cek Log Error

Jika terjadi error di browser, cek log Apache:

```bash
tail -30 /var/log/apache2/your.domain.com-error.log
```

---

## Troubleshooting Umum

| Error | Solusi |
|-------|--------|
| `Call to undefined function simplexml_load_file()` | `apt install php8.4-xml` |
| `Failed opening vendor/autoload.php` | Jalankan `composer install` |
| `Undefined constant "STAGE"` | Tambahkan domain ke `config.local.xml` |
| `The zip extension and unzip/7z commands are both missing` | `apt install unzip php8.4-zip` |
