<!-- spec phpVB: app=bukutamu mvc=kunjungan diperbarui=2026-10-08 — salin seluruh isi berkas ini ke kancil untuk membangun ulang MVC ini -->
Di app bukutamu, buat halaman `kunjungan` — Kunjungan Tamu.

Kebutuhan awal (kata-kata pengguna):

> Di app bukutamu, catat tamu yang berkunjung: tanggal, nama tamu, asal instansi, keperluan (dipilih dari daftar jenis keperluan), dan catatan. Petugas bisa membentangkan baris untuk membaca catatan yang panjang dan mencari ke seluruh data.

<!-- spec:rincian start -->
Bentuk halaman: Daftar sederhana — Satu tabel isian: tambah, ubah, hapus, cari.
Data disimpan di tabel `kunjungan`.

Isian tiap baris:
- Nama Tamu (`nama`) — teks; wajib
- Tanggal — tanggal; wajib
- Keperluan (`keperluan_id`) — pilihan; diambil dari /bukutamu/jenis_keperluan/bulk
- Catatan — teks panjang

Fitur:
- Rincian per baris: Baris bisa dibentangkan untuk membaca isian yang panjang
- Pencarian: Kotak cari ke seluruh data, bukan hanya yang tampil di layar

Hak akses: halaman hanya untuk admin ke atas.
<!-- spec:rincian end -->

Catatan tambahan:
- Petugas resepsionis mencatat; halaman bukan untuk umum.

Tunjukkan rancangannya dulu dan tunggu persetujuan saya sebelum menulis berkas.
