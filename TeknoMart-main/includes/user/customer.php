<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
$error = '';
$user = $route === 'deskripsi' ? current_user() : require_user();
$userId = (int)($user['id_user'] ?? 0);
$id = (int)input('id', '0');
$product = $route === 'deskripsi' ? one('SELECT * FROM tb_barang WHERE id_barang = ?', [$id]) : null;
$order = null;
if ($route === 'deskripsi' && !$product) { http_response_code(404); store_header('Produk tidak ditemukan'); empty_state('Produk tidak ditemukan', 'Produk mungkin telah dihapus atau tautannya tidak valid.'); store_footer(); return; }
if (in_array($route, ['pembayaran','rating','refund','batal'])) {
    try { $order = owned_order($id, $userId); }
    catch (DomainException $ex) { http_response_code(404); store_header('Pesanan tidak ditemukan'); empty_state('Pesanan tidak ditemukan', 'Periksa kembali daftar pesanan Anda.', 'pages/pesanan.php', 'Lihat pesanan'); store_footer(); return; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = input('action');
        $actionByRoute = [
            'deskripsi' => 'product.php',
            'keranjang' => 'cart.php',
            'rincian' => 'checkout.php', 'rinciannow' => 'checkout.php',
            'profile' => 'profile.php',
            'pesanan' => 'orders.php', 'batal' => 'orders.php',
            'pembayaran' => 'payment.php',
            'rating' => 'rating.php', 'refund' => 'refund.php',
        ];
        if (isset($actionByRoute[$route])) require __DIR__ . '/actions/' . $actionByRoute[$route];
    } catch (DomainException $ex) { $error = $ex->getMessage(); }
    catch (mysqli_sql_exception $ex) { error_log($ex->getMessage()); $error = 'Perubahan belum dapat disimpan. Silakan coba lagi.'; }
}

$titles = ['deskripsi'=>'Detail produk','keranjang'=>'Keranjang belanja','rincian'=>'Checkout','rinciannow'=>'Checkout','profile'=>'Profil saya','pesanan'=>'Pesanan saya','pesananstatus'=>'Pesanan saya','pembayaran'=>'Pembayaran','rating'=>'Konfirmasi & ulasan','refund'=>'Pengajuan retur','batal'=>'Batalkan pesanan'];
store_header($titles[$route], $error);
$viewByRoute = [
    'deskripsi' => 'product.php',
    'keranjang' => 'cart.php',
    'rincian' => 'checkout.php', 'rinciannow' => 'checkout.php',
    'profile' => 'profile.php',
    'pesanan' => 'orders.php', 'pesananstatus' => 'orders.php',
    'pembayaran' => 'payment.php',
    'rating' => 'review_refund.php', 'refund' => 'review_refund.php',
    'batal' => 'cancel.php',
];
require __DIR__ . '/views/' . ($viewByRoute[$route] ?? 'orders.php');
store_footer();
