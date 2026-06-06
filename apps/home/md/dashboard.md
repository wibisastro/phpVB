# Dashboard Portal

Dashboard ringkas portal landing (app **home**). Menampilkan dua section yang
dirender live dari DB portal sendiri:

- **Info Diri** — jumlah Wilayah, Instansi, dan Member milik portal ini
  (beserta breakdown per level / per role).
- **Agregat** — rollup wilayah/instansi/member anak di bawahnya. Hanya tampil
  untuk portal scope **pusat** atau **provinsi** (dibaca dari
  `gov3_central.dashboard_rollup`).

Sumber scope diambil dari satu baris tabel `dashboard_ingest`; angka self_*
di-overlay live (bukan cache cron) tiap kali halaman dibuka.

## Rencana lanjutan

- Tautan per-kartu ke daftar detail (drill-down wilayah/instansi/member).
- Tren historis (sparkline) jumlah member/instansi.
- Section khusus per-app lain saat tersedia (mis. ringkasan evaluasi aisakip,
  pipeline ingest) — masing-masing dibangun di repo app-nya sendiri.
