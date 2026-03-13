# phpVB

**Platform aplikasi digital pemerintahan yang dirancang untuk kondisi nyata di lapangan.**

---

## Mengapa phpVB?

Digitalisasi pemerintahan menghadapi tantangan yang berbeda dari sektor swasta. Infrastruktur tidak selalu tersedia merata, kapasitas teknis tim bervariasi, dan kebutuhan bisa berubah seiring pergantian kepemimpinan. phpVB dibangun untuk menjawab tantangan-tantangan ini.

---

## Prinsip Desain

### 1. Konvensi di Atas Konfigurasi

Setiap aplikasi baru mengikuti pola yang sama — struktur folder, penamaan file, alur data — semuanya sudah ditentukan oleh framework. Developer tidak perlu membuat keputusan arsitektur dari nol setiap kali membangun fitur baru.

**Dampaknya:** onboarding developer baru lebih cepat, kualitas antar modul konsisten, dan risiko kesalahan arsitektur berkurang.

### 2. Siap di Berbagai Kondisi Infrastruktur

phpVB dirancang agar bisa berjalan dalam tiga tingkat kesiapan infrastruktur:

| Tingkat | Kondisi | Penyimpanan Data | Cocok Untuk |
|---------|---------|-------------------|-------------|
| **Mandiri** | Tanpa database server | File teks (XML & JSON) | Prototipe, demo, daerah dengan keterbatasan server |
| **Database Lokal** | Database di server sendiri | MySQL/MariaDB atau PostgreSQL | Instansi yang sudah punya data center atau server lokal |
| **Database Cloud** | Database cloud via API | MySQL atau PostgreSQL | Instansi yang ingin skalabilitas dan kolaborasi antar-wilayah |

Satu basis kode yang sama bisa berjalan di ketiga kondisi. Ketika infrastruktur ditingkatkan, aplikasi tidak perlu ditulis ulang — cukup ubah sumber datanya.

### 3. Pemisahan Tanggung Jawab yang Jelas

phpVB memisahkan **kerangka** dan **isi** secara tegas:

- **Kerangka** (layout, navigasi, keamanan, hak akses) diproses di server — konsisten dan terkontrol
- **Isi** (data, formulir, interaksi pengguna) diproses di browser — responsif dan interaktif

Analoginya seperti gedung kantor pemerintahan: struktur bangunan (dinding, koridor, pintu keamanan) sudah fixed dan dikelola oleh pengelola gedung, sementara isi ruangan (meja, kursi, peralatan kerja) diatur fleksibel oleh masing-masing unit yang menempati.

**Dampaknya:**
- Tim desain tampilan dan tim pengembang data bisa bekerja paralel tanpa saling menunggu
- Perubahan tampilan tidak mempengaruhi logika data, dan sebaliknya
- Keamanan terjaga di level server — tidak bergantung pada browser pengguna

### 4. Multi-Staging Otomatis

phpVB mengenali lingkungan kerja secara otomatis dari alamat domain — tanpa konfigurasi manual:

| Domain | Lingkungan | Fungsi |
|--------|-----------|--------|
| `localhost` | **Lokal** | Pengembangan di komputer developer |
| `dev.nama-aplikasi.go.id` | **Pengembangan** | Pengujian oleh tim sebelum rilis |
| `nama-aplikasi.go.id` | **Produksi** | Diakses oleh pengguna akhir |

Setiap lingkungan bisa memiliki sumber data dan tingkat keamanan yang berbeda, namun kode aplikasinya tetap sama. Ini menghilangkan risiko "di komputer saya jalan, di server tidak."

---

## Modul Bawaan

phpVB menyediakan modul-modul dasar yang dibutuhkan hampir semua aplikasi pemerintahan:

| Modul | Fungsi |
|-------|--------|
| **Login & Akun** | Otentikasi pengguna, pendaftaran, profil, SSO |
| **Wilayah** | Hierarki wilayah administratif (provinsi, kabupaten, kecamatan, kelurahan) |
| **Instansi** | Struktur organisasi dan unit kerja |
| **Pengaturan** | Konfigurasi aplikasi per-modul |

Modul-modul ini sudah siap pakai dan bisa diperluas sesuai kebutuhan spesifik instansi.

---

## Keunggulan untuk Instansi Pemerintah

**Tidak terkunci vendor** — phpVB adalah framework open-source, bukan layanan berlangganan. Instansi memiliki kendali penuh atas kode dan data.

**Investasi bertahap** — Mulai dari mode mandiri (tanpa database), lalu tingkatkan ke database lokal atau cloud seiring kesiapan anggaran dan SDM.

**Standar terbuka** — Dibangun di atas teknologi standar industri (PHP, MySQL, PostgreSQL, Vue.js) yang diajarkan di universitas dan tersedia tenaga kerjanya secara luas di Indonesia.

**Keamanan terintegrasi** — Sistem hak akses, otentikasi, dan SSO sudah tertanam dalam framework — bukan tambahan di kemudian hari.

---

## Dokumentasi Teknis

Untuk tim pengembang, dokumentasi teknis lengkap tersedia di [phpVB Wiki](https://github.com/wibisastro/phpVB/wiki).

---

## Lisensi

Hak cipta dilindungi. Framework ini dikembangkan untuk kebutuhan proyek pemerintahan.

## Kontak

Wibisono Sastrodiwiryo — wibi@alumni.ui.ac.id
