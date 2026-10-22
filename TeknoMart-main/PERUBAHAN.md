# Perbaikan TeknoMart

## Menjalankan aplikasi

- PHP 8.1+ dengan ekstensi `mysqli`, `mbstring`, dan `fileinfo`.
- Database yang dipakai tetap `tecp5312_elektronik`; data lama tidak dihapus atau diimpor ulang.
- Konfigurasi koneksi berada di `includes/bootstrap.php`. Server juga dapat mengatur `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, dan `DB_NAME`.
- Buka `index.php` untuk toko dan `bangjanganbang/login_admin.php` untuk admin. URL halaman lama dipertahankan.
- Folder `upload` harus dapat ditulis PHP. Perlindungan `.htaccess` berlaku pada Apache dengan AllowOverride aktif. Laragon lokal menggunakan Nginx, sehingga aturan `.htaccess` tidak dijalankan; konfigurasi pembatasan file statis seperti dump SQL harus diatur pada Nginx sebelum publikasi.

## Perilaku yang diperbaiki

- Login admin dan user memverifikasi password sebelum membuat sesi. Password teks biasa dimigrasikan menjadi bcrypt saat login berhasil. Identitas admin dan user dipisahkan.
- Semua halaman pengelolaan dan ekspor memerlukan login admin. Profil, checkout, dan pesanan memerlukan login user; pesanan diperiksa kepemilikannya.
- Query memakai parameter dan tampilan meng-escape data. Formulir perubahan memakai token CSRF dan logout memerlukan konfirmasi melalui POST.
- Registrasi mengisi seluruh kolom wajib, memvalidasi email dan konfirmasi password, serta menolak username/email yang sudah terdaftar.
- Checkout mengambil harga dari database dan pemilik dari sesi, memvalidasi stok, mengunci baris produk, serta menyimpan pesanan/detail/stok dalam satu transaksi.
- Checkout keranjang hanya menghapus produk yang dipilih oleh pemilik keranjang tersebut.
- Pembatalan mengembalikan stok satu kali. Konfirmasi pembayaran dan pengiriman mengikuti transisi status; pengulangan tindakan ditolak.
- Unggahan produk, pembayaran, retur, dan ulasan dibatasi JPG/PNG/WebP maksimal 5 MB dengan pemeriksaan isi file.
- Penambahan stok dan pencatatan pengeluaran dilakukan dalam satu transaksi. Admin mengisi harga beli aktual; harga jual adalah harga final, bukan markup otomatis.
- Produk dengan riwayat pesanan tidak dapat dihapus agar riwayat tetap utuh. Produk tanpa pesanan dapat dihapus; catatan pengeluaran dipertahankan.
- Laporan kompatibel dengan `ONLY_FULL_GROUP_BY`, tanggal akhir mencakup seluruh hari, dan angka ekspor konsisten dengan laporan.
- Perubahan password memerlukan login dan password saat ini. Pemulihan akun melalui email belum memiliki layanan pengiriman/token; halaman bantuan meminta verifikasi oleh pengelola, bukan mengizinkan reset hanya dengan mengetahui email.

## Tampilan

Tema navy–teal yang sama digunakan pada toko, login, profil, checkout, pesanan, katalog, serta workspace admin. Layout responsif, tabel dapat digulir di layar kecil, dan sidebar admin dapat dibuka dengan tombol menu. Halaman tidak bergantung pada CDN Bootstrap, Chart.js, font, atau ikon eksternal.

Struktur utama:

- `includes/bootstrap.php`: koneksi, sesi, query, validasi, autentikasi.
- `includes/commerce.php`: aturan stok, checkout, pembatalan, dan status pesanan.
- `includes/catalog.php`, `customer.php`, `auth.php`, `admin.php`: alur halaman.
- `includes/layout.php`: navigasi, kartu produk, formulir, dan layout bersama.
- `css/app.css`, `js/app.js`: tampilan dan interaksi.

## Verifikasi

Pengujian berada di folder `tests` pada direktori induk aplikasi. Pengujian transaksi dan formulir menggunakan tabel MySQL sementara; akun, produk, dan pesanan asli tidak diubah.

Dari direktori induk:

```powershell
New-Item -ItemType Directory -Force tests/sessions
php -d session.save_path=tests/sessions tests/commerce_test.php
```

Untuk pemeriksaan HTTP, jalankan server lokal khusus tes (ganti path sesi sesuai lokasi proyek):

```powershell
php -d session.save_path=D:/laragon/www/TeknoMart-main/tests/sessions -d display_errors=1 -d error_reporting=32767 -S 127.0.0.1:8765 -t TeknoMart-main tests/router.php
python tests/http_smoke.py
python tests/http_actions.py
```

`tests/router.php` hanya untuk server pengujian CLI, bukan server produksi. Pemeriksaan browser memakai Playwright dan Microsoft Edge melalui `node tests/browser/smoke.cjs`; screenshot tersimpan di `tests/screenshots`.

Dependensi browser dapat dipasang dengan `npm.cmd install --prefix tests/browser`. Pemeriksaan interaksi tambahan dijalankan lewat `node tests/browser/interactions.cjs`.

Hasil verifikasi: 43 pemeriksaan logika, 54 pemeriksaan HTTP/akses, dan 33 pengujian formulir lulus. Sembilan tampilan diuji pada lebar 1440 dan 390 piksel tanpa overflow halaman, gambar rusak, atau error JavaScript; interaksi keranjang, total checkout, dan navigasi ponsel juga diperiksa.


## Header, profil, dan rincian pembayaran

Header menampilkan monogram SVG, TeknoMart, menu, dan pencarian dalam satu baris. Menu dapat digeser horizontal pada ponsel. Semua tab memakai judul TeknoMart dan favicon `assets/icon/brand.svg`.

Foto profil disimpan melalui tabel `user_profiles`. Pada instalasi lain jalankan `php db/profile_migration.php` dari folder aplikasi sebelum menggunakan fitur foto profil. Migrasi ini sudah dijalankan pada database lokal.

Checkout menampilkan subtotal, biaya pengiriman, biaya pembayaran, diskon, dan total. Konfigurasi promo berada di `includes/promotions.php`, berupa daftar dengan kunci `label`, `percent`, `starts`, `ends`, dan `product_ids`. Tanggal memakai format YYYY-MM-DD. Daftar awal kosong; tidak ada diskon otomatis tanpa konfigurasi pemilik toko. Diskon yang memenuhi syarat dihitung ulang oleh server dan harga bersih disimpan dalam detail pesanan, sehingga total pembayaran serta laporan memakai harga setelah diskon. Jika beberapa promo berlaku, sistem memilih potongan terbesar per produk.

Pesanan belum dibayar menampilkan foto, nama, jumlah, harga satuan, subtotal per produk, pengiriman, biaya pembayaran, dan total. Harga pada pesanan yang sudah dibuat tetap mengikuti detail yang tersimpan.


## Foto, banner promo, dan pesanan

- Foto profil dapat dihapus melalui tombol khusus di halaman Akun; perubahan membutuhkan token formulir dan hanya menghapus foto milik pengguna yang sedang masuk.
- Banner utama menampilkan produk yang terhubung dengan promo aktif dan harga sebelum/sesudah potongan. Lebih dari satu produk promo ditampilkan sebagai carousel dengan pergantian otomatis setiap 6,5 detik, tombol panah, indikator, dan geser jari pada ponsel. Timer berhenti saat kursor/fokus berada di banner atau tab tidak aktif. Tanpa promo aktif, banner produk pilihan tetap tampil.
- Ringkasan pesanan menghitung status Belum Bayar, Diproses, Dikirim, dan Selesai milik pengguna. Banner pengingat mengarahkan pengguna ke penilaian untuk paket yang belum diberi rating.
- Pada ponsel menu Dashboard, Produk, Notifikasi, Pesanan, Keranjang, dan Akun berada di tombol garis tiga di sisi kanan. Pencarian tampil di tengah baris tersendiri di bawah identitas toko.

Pengujian tambahan untuk foto profil, status pesanan, menu ponsel, dan carousel dilakukan pada lebar 1440 dan 390 piksel. Tombol banner serta pergantian otomatis juga diperiksa.
