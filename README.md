# Dasbor Bencana Indonesia (PHP + Tailwind)

Dasbor web untuk dataset bencana BNPB/DIBI yang sudah dibersihkan
(17.878 baris, tahun 1815–2014). Menampilkan kartu KPI, grafik tren per tahun,
sebaran jenis bencana, 10 provinsi terbanyak, dan tabel kejadian dengan
korban jiwa terbesar. Ada filter jenis bencana, provinsi, dan rentang tahun.

## Isi folder
- `index.php` — halaman dasbor (membaca data.csv, agregasi di sisi server)
- `data.csv` — dataset bersih (26 kolom)

## Cara menjalankan (Windows, tanpa Docker)
Pilihan 1 — server bawaan PHP (paling ringan):
1. Pastikan PHP terpasang (cek: `php -v`). Belum ada? Unduh dari windows.php.net
   atau pakai PHP dari XAMPP (`C:\xampp\php`).
2. Buka terminal di folder ini, jalankan: `php -S localhost:8000`
3. Buka browser: http://localhost:8000

Pilihan 2 — XAMPP:
1. Salin folder ini ke `C:\xampp\htdocs\dasbor-bencana`
2. Jalankan Apache dari XAMPP Control Panel
3. Buka browser: http://localhost/dasbor-bencana

Catatan: Tailwind dan Chart.js sudah disertakan lokal di folder `assets/`, jadi dasbor ini **100% bisa dibuka tanpa internet**.

## Fitur
- Filter jenis bencana, provinsi, dan rentang tahun
- Tombol ganti tema terang/gelap (pilihan tersimpan otomatis)

## Teknologi
PHP (fgetcsv + agregasi), Tailwind CSS (CDN), Chart.js (CDN). Tanpa database.
