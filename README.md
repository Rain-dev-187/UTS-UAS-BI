# Dasbor Bencana Indonesia (PHP + Tailwind)

Dasbor web untuk dataset bencana BNPB/DIBI yang sudah dibersihkan
(17.878 baris, tahun 1815–2014). Menampilkan kartu KPI, grafik tren per tahun,
sebaran jenis bencana, 10 provinsi terbanyak, dan tabel kejadian dengan
korban jiwa terbesar. Ada filter jenis bencana, provinsi, dan rentang tahun.

## Isi folder
- `index.php` — halaman dasbor (query SQL ke database SQLite)
- `bencana.db` — database SQLite (17.878 baris, tabel `bencana`)
- `import_csv.php` — membangun ulang `bencana.db` dari `data.csv` (jalankan: `php import_csv.php`)
- `data.csv` — dataset bersih (26 kolom)

## Cara menjalankan (Windows, tanpa Docker)
Pilihan 1 — Laragon:
1. Salin folder ini ke `C:\laragon\www\dasbor-bencana`
2. Jalankan Laragon, klik Start All
3. Buka browser: http://localhost/dasbor-bencana

Pilihan 2 — server bawaan PHP (paling ringan):
1. Buka terminal di folder ini (dari Laragon: tombol Terminal), jalankan: `php -S localhost:8000`
2. Buka browser: http://localhost:8000

Catatan: Tailwind dan Chart.js sudah disertakan lokal di folder `assets/`, jadi dasbor ini **100% bisa dibuka tanpa internet**.

## Fitur
- Filter jenis bencana, provinsi, dan rentang tahun
- Tombol ganti tema terang/gelap (pilihan tersimpan otomatis)

## Teknologi
PHP + PDO SQLite (agregasi dengan SQL: SUM, GROUP BY, ORDER BY), Tailwind CSS dan Chart.js dari folder `assets/` (offline).
