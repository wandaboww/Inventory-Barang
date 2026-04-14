# Inventory Barang (Laravel 13)

Proyek ini adalah hasil konversi dari aplikasi inventaris berbasis single-file PHP ke arsitektur Laravel terbaru.

## Stack

- Backend: Laravel 13 (PHP 8.3)
- Database default: SQLite
- Frontend: Blade + Bootstrap 5

## Modul Utama

- Dashboard Public
	- Stok barang tersedia
	- Daftar pinjaman aktif siswa
- Dashboard Admin
	- Ringkasan aset dan pinjaman
	- Tabel pinjaman aktif (kelas, no. HP, tanggal pinjam)
- Data Barang
	- Tambah/hapus barang
	- Filter (search, kategori, status)
	- Ringkasan total, per kategori, laptop per merk
- Data Pengguna
	- Tambah/hapus pengguna
	- Filter (search, role, kelas)
- Transaksi Peminjaman
	- Proses pinjam
	- Proses pengembalian

## Jalankan Proyek

```bash
composer install
php artisan migrate:fresh --seed
php artisan serve
```

Atau di Windows gunakan:

```bat
start-server.bat
```

Mode testing (port terpisah):

```bat
start-test-server.bat
```

Atau langsung dari skrip utama:

```bat
start-server.bat test
```

Override port manual:

```bat
start-server.bat 8080
start-server.bat test 8081
```

## Route Penting

- `/` : public dashboard
- `/admin/dashboard` : admin dashboard
- `/admin/assets` : data barang
- `/admin/users` : data pengguna
- `/admin/loans` : transaksi peminjaman

## Seed Default

Seeder membuat data awal berikut:

- 1 admin
- 1 guru
- 1 siswa
- sample assets + sample loans

Password admin default seed:

- `admin12345`
