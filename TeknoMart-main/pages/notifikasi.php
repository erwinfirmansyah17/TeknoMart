<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/layout.php';
$user = require_user();
$updates = query('SELECT o.id_orders, o.status, o.tanggal,
    (SELECT r.status_refund FROM tb_refund r WHERE r.id_orders = o.id_orders ORDER BY r.id_refund DESC LIMIT 1) AS refund_status
    FROM orders o WHERE o.id_user = ? ORDER BY o.id_orders DESC LIMIT 30', [(int)$user['id_user']]);
$messages = [
    'Belum Bayar' => ['Pembayaran menunggu Anda', 'Selesaikan pembayaran dan unggah bukti untuk melanjutkan pesanan.', 'bag'],
    'Menunggu Konfirmasi' => ['Bukti pembayaran sedang diperiksa', 'Admin akan memverifikasi pembayaran sebelum memproses pesanan.', 'shield'],
    'Diproses' => ['Pesanan sedang disiapkan', 'Pembayaran telah dikonfirmasi. Produk Anda sedang dipersiapkan untuk pengiriman.', 'box'],
    'Dikirim' => ['Pesanan sudah dikirim', 'Konfirmasikan penerimaan dan bagikan ulasan setelah produk tiba.', 'truck'],
    'Selesai Belum Dinilai' => ['Bagikan pengalaman Anda', 'Pesanan sudah diterima. Berikan penilaian untuk produk yang Anda beli.', 'check'],
    'Selesai' => ['Pesanan telah selesai', 'Terima kasih sudah berbelanja di TeknoMart.', 'check'],
    'Dibatalkan' => ['Pesanan dibatalkan', 'Pesanan ini tidak akan diproses. Anda dapat menjelajahi produk lainnya.', 'bag'],
    'Diajukan Retur' => ['Pengajuan retur sedang ditinjau', 'Admin sedang memeriksa pengajuan dan bukti yang Anda kirim.', 'shield'],
    'Retur' => ['Status pengembalian produk', 'Lihat detail pesanan untuk memeriksa hasil pengajuan retur Anda.', 'box'],
];
store_header('Notifikasi');
page_title('PUSAT NOTIFIKASI', 'Kabar terbaru untuk Anda.', 'Ringkasan status terkini dari 30 pesanan terbaru Anda.');
if (!$updates) empty_state('Belum ada notifikasi', 'Informasi pembayaran dan pesanan Anda akan tampil di sini.');
else {
    echo '<section class="notification-list" aria-label="Notifikasi pesanan">';
    foreach ($updates as $update) {
        [$title, $message, $glyph] = $messages[$update['status']] ?? ['Status pesanan Anda', 'Periksa detail pesanan untuk informasi lebih lanjut.', 'bell'];
        if ($update['refund_status'] === 'Diterima' || $update['refund_status'] === 'Ditolak') {
            $title = 'Pengajuan retur ' . strtolower($update['refund_status']);
            $message = 'Admin telah meninjau pengajuan retur Anda. Lihat detail di daftar pesanan.';
        }
        $target = $update['refund_status'] ? 'pages/pesanan.php' : 'pages/pembayaran.php?id=' . (int)$update['id_orders'];
        echo '<article class="panel notification-card"><span class="notification-icon">' . icon($glyph) . '</span><div class="notification-copy"><div class="notification-meta"><span>Pesanan #TM-' . (int)$update['id_orders'] . '</span>' . status_badge($update['status']) . '</div><h2>' . e($title) . '</h2><p>' . e($message) . '</p><small class="muted">Pesanan dibuat ' . e(date('d M Y, H:i', strtotime($update['tanggal']))) . '</small><a class="text-link" href="' . e(url($target)) . '">Lihat pesanan ' . icon('arrow') . '</a></div></article>';
    }
    echo '</section>';
}
store_footer();
