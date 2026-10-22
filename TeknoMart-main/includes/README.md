# Peta kode TeknoMart

Folder `includes` berisi logika aplikasi. Berkas pada `pages/` dan `bangjanganbang/` adalah alamat halaman yang dipanggil browser; keduanya meneruskan pekerjaan ke modul di sini.

## Bagian bersama

- `bootstrap.php` — sesi, koneksi basis data, validasi input, dan helper umum.
- `auth.php` dan `logout.php` — alur masuk, daftar, dan keluar.
- `layout.php` — header, footer, ikon, dan komponen tampilan yang dipakai bersama.
- `commerce.php` — keranjang, checkout, pesanan, pengiriman, dan retur.
- `pricing.php` — perhitungan harga promo dan foto profil.

## Bagian pelanggan (`user/`)

- `home.php` — banner utama, carousel penawaran, kategori, dan produk unggulan.
- `products.php` — pencarian, filter, pengurutan, dan halaman katalog.
- `promotions.php` — daftar konfigurasi promo toko.
- `customer.php` — pemeriksaan akses dan pemilihan handler/tampilan pelanggan.
- `actions/` — proses form pelanggan dipisah per fitur: produk, keranjang, checkout, profil, pesanan, pembayaran, rating, dan retur.
- `views/product.php`, `views/cart.php`, `views/checkout.php` — detail produk, keranjang, dan checkout.
- `views/profile.php`, `views/orders.php`, `views/payment.php` — profil, pesanan, dan pembayaran.
- `views/review_refund.php`, `views/cancel.php` — penilaian, retur, dan pembatalan pesanan.

## Bagian admin (`admin/`)

- `controller.php` — autentikasi admin, pemilihan handler/tampilan, dan data laporan.
- `actions/products.php` dan `actions/orders.php` — proses perubahan produk/stok serta status pesanan/retur.
- `views/dashboard.php` — ringkasan toko.
- `views/products.php` — daftar produk, tambah/edit produk, dan stok.
- `views/orders.php` — konfirmasi, pengiriman, pembatalan, dan retur.
- `views/reports.php` — laporan pemasukan dan pengeluaran.
- `views/customers.php` — daftar pelanggan.

Berkas `catalog.php`, `customer.php`, `admin.php`, dan `promotions.php` di tingkat atas `includes/` dipertahankan sebagai penghubung agar alamat dan halaman lama tidak berubah. Untuk menyesuaikan banner, buka `user/home.php`; untuk mengatur promo, buka `user/promotions.php`; untuk katalog pelanggan, buka `user/products.php`.
