<?php
function icon($name) {
    $paths = [
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/>',
        'bag' => '<path d="M5 7h14l1 14H4L5 7Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'box' => '<path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v10l9 5 9-5V8M12 13v10M7 5l10 6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 22v-3a8 8 0 0 1 16 0v3"/>',
        'chart' => '<path d="M4 3v18h18M8 16v-4m5 4V8m5 8V5"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'logout' => '<path d="M9 4H4v16h5m6-13 5 5-5 5m-6-5h11"/>',
        'truck' => '<path d="M2 5h12v12H2V5Zm12 5h4l4 5v2h-8"/><circle cx="6" cy="19" r="2"/><circle cx="18" cy="19" r="2"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/>',
    ];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['box']) . '</svg>';
}
function brand() { return '<img class="store-logo" src="' . e(url('assets/icon/brand.svg')) . '" alt=""><span>TeknoMart</span>'; }
function head_html($title, $class = '') {
    $cssPath = dirname(__DIR__) . '/css/app.css';
    $cssVersion = is_file($cssPath) ? filemtime($cssPath) . '-' . filesize($cssPath) : '1';
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#122c34"><title>TeknoMart</title><link rel="icon" type="image/svg+xml" href="' . e(url('assets/icon/brand.svg')) . '"><link rel="stylesheet" href="' . e(url('css/app.css') . '?v=' . $cssVersion) . '"><script defer src="' . e(url('js/app.js')) . '"></script></head><body class="' . e($class) . '">';
}
function notices($error = '') {
    if (!empty($_SESSION['flash'])) { [$message, $type] = $_SESSION['flash']; unset($_SESSION['flash']); echo '<div class="notice ' . e($type) . '" role="status">' . e($message) . '</div>'; }
    if ($error !== '') echo '<div class="notice error" role="alert">' . e($error) . '</div>';
}
function store_header($title, $error = '') {
    $user = current_user();
    $count = $user ? (int)(one('SELECT SUM(qty) AS n FROM keranjang WHERE id_user = ?', [(int)$user['id_user']])['n'] ?? 0) : 0;
    $ordersCount = $user ? (int)(one("SELECT COUNT(*) AS n FROM orders WHERE id_user = ? AND status IN ('Belum Bayar','Menunggu Konfirmasi','Diproses','Dikirim','Selesai Belum Dinilai')", [(int)$user['id_user']])['n'] ?? 0) : 0;
    $page = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $catalogPages = ['product.php', 'search_product.php', 'filterbrand.php', 'filterprice.php', 'filterrecomendation.php', 'deskripsi.php'];
    $active = in_array($page, $catalogPages, true) ? 'product.php' : $page;
    $links = [
        ['index.php', 'grid', 'Dashboard'],
        ['pages/product.php', 'box', 'Produk'],
        ['pages/notifikasi.php', 'bell', 'Notifikasi'],
        ['pages/pesanan.php', 'truck', 'Pesanan'],
        ['pages/keranjang.php', 'bag', 'Keranjang'],
        ['pages/profile.php', 'user', 'Akun'],
    ];
    head_html($title, 'store');
    echo '<header class="store-header"><div class="shell store-header-row"><div class="store-brand-row"><a class="brand store-brand" href="' . e(url()) . '" aria-label="TeknoMart, halaman utama">' . brand() . '</a></div><form class="search nav-search" role="search" action="' . e(url('pages/search_product.php')) . '">' . icon('search') . '<input aria-label="Cari produk" name="search_query" placeholder="Cari produk..." value="' . e(input('search_query')) . '"><button aria-label="Cari">Cari</button></form><button class="store-menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false" aria-controls="store-nav"><span></span><span></span><span></span></button><div class="store-navigation" id="store-nav"><nav class="store-nav" aria-label="Navigasi toko">';
    foreach ($links as [$path, $glyph, $label]) {
        $selected = $active === basename($path);
        echo '<a href="' . e(url($path)) . '"' . ($selected ? ' class="active" aria-current="page"' : '') . '>' . icon($glyph) . '<span>' . e($label) . '</span>';
        if ($glyph === 'bag' && $count > 0) echo '<span class="nav-count" aria-label="' . $count . ' produk">' . $count . '</span>';
        if ($glyph === 'truck' && $ordersCount > 0) echo '<span class="nav-count" aria-label="' . $ordersCount . ' pesanan aktif">' . $ordersCount . '</span>';
        echo '</a>';
    }
    echo '</nav></div></div></header><main class="shell store-main">';
    notices($error);
}
function store_footer() {
    echo '</main><footer class="store-footer"><div class="shell footer-grid"><a class="brand" href="' . e(url()) . '">' . brand() . '</a><p>Teknologi pilihan.<br>Untuk setiap bagian kehidupan.</p><div><a href="' . e(url()) . '">Dashboard</a><a href="' . e(url('pages/product.php')) . '">Produk</a><a href="' . e(url('pages/notifikasi.php')) . '">Notifikasi</a></div><div><a href="' . e(url('pages/pesanan.php')) . '">Pesanan</a><a href="' . e(url('pages/profile.php')) . '">Akun saya</a><a href="' . e(url('bangjanganbang/login_admin.php')) . '">Portal admin</a></div></div><div class="shell footer-bottom">&copy; ' . date('Y') . ' TeknoMart. Semua hak dilindungi.<span>Dirancang untuk keseharian Anda.</span></div></footer></body></html>';
}
function page_title($eyebrow, $title, $description = '') { echo '<div class="page-heading"><span class="eyebrow">' . e($eyebrow) . '</span><h1>' . e($title) . '</h1>' . ($description ? '<p>' . e($description) . '</p>' : '') . '</div>'; }
function empty_state($title, $message, $path = 'pages/product.php', $button = 'Jelajahi produk') { echo '<div class="empty-state">' . icon('bag') . '<h2>' . e($title) . '</h2><p>' . e($message) . '</p><a class="button" href="' . e(url($path)) . '">' . e($button) . ' ' . icon('arrow') . '</a></div>'; }
function product_card($product) {
    $link = url('pages/deskripsi.php?id=' . (int)$product['id_barang']);
    $rating = (float)($product['average_rating'] ?? 0);
    echo '<article class="product-card"><a class="product-image" href="' . e($link) . '"><span class="product-label">' . e($product['merk']) . '</span><img loading="lazy" src="' . e(url($product['img'])) . '" alt="' . e($product['nama_barang']) . '"></a><div class="product-info"><span class="eyebrow">' . e($product['kategori']) . '</span><a class="product-name" href="' . e($link) . '">' . e($product['nama_barang']) . '</a><div class="rating"><span>★</span> ' . ($rating ? number_format($rating, 1) : 'Baru') . ' <span class="muted">· ' . (int)$product['terjual'] . ' terjual</span></div><div class="product-bottom"><strong>' . rupiah($product['harga']) . '</strong><a class="circle-link" aria-label="Lihat ' . e($product['nama_barang']) . '" href="' . e($link) . '">' . icon('arrow') . '</a></div><small class="stock ' . ($product['stok'] > 0 ? '' : 'sold-out') . '">' . ($product['stok'] > 0 ? 'Stok tersedia' : 'Stok habis') . '</small></div></article>';
}
function field($label, $name, $value = '', $type = 'text', $attrs = '') { echo '<label class="field"><span>' . e($label) . '</span><input type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '" ' . $attrs . '></label>'; }
function status_badge($status) {
    $type = in_array($status, ['Selesai', 'Diterima']) ? 'success' : (in_array($status, ['Dibatalkan', 'Ditolak', 'Retur']) ? 'danger' : 'pending');
    return '<span class="badge ' . $type . '">' . e($status) . '</span>';
}
function action_form($action, $id, $label, $class = 'button small', $confirm = '') {
    echo '<form method="post" class="inline-form"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf() . '<input type="hidden" name="id" value="' . (int)$id . '"><button class="' . e($class) . '" name="action" value="' . e($action) . '">' . e($label) . '</button></form>';
}
function admin_header($title, $active, $error = '') {
    $admin = require_user(true);
    head_html($title, 'admin');
    echo '<aside class="sidebar" id="sidebar"><a class="brand" href="' . e(url('bangjanganbang/index.php')) . '">' . brand() . '</a><span class="sidebar-label">WORKSPACE</span><nav aria-label="Navigasi admin">';
    $links = [['index', 'grid', 'Ringkasan'], ['all_product', 'box', 'Semua produk'], ['add_product', 'box', 'Tambah produk & stok'], ['edit_product', 'box', 'Kelola produk'], ['konfirmasi', 'bag', 'Konfirmasi pesanan'], ['diproses', 'truck', 'Pengiriman'], ['selesai', 'check', 'Pesanan selesai'], ['batal', 'bag', 'Pembatalan'], ['refund', 'shield', 'Pengajuan retur'], ['pendapatan', 'chart', 'Pemasukan'], ['pengeluaran', 'chart', 'Pengeluaran'], ['user', 'user', 'Pelanggan']];
    foreach ($links as [$route, $glyph, $label]) echo '<a class="' . ($active === $route ? 'active' : '') . '" href="' . e(url('bangjanganbang/' . $route . '.php')) . '">' . icon($glyph) . '<span>' . e($label) . '</span></a>';
    echo '</nav><div class="sidebar-bottom"><span class="avatar">' . e(strtoupper(substr($admin['username'], 0, 1))) . '</span><div><strong>' . e($admin['username']) . '</strong><small>Administrator</small></div><a aria-label="Keluar" href="' . e(url('bangjanganbang/logout.php')) . '">' . icon('logout') . '</a></div></aside><div class="admin-content"><header class="admin-topbar"><button class="icon-button mobile-menu" aria-label="Buka navigasi" aria-expanded="false" aria-controls="sidebar">' . icon('menu') . '</button><span>Workspace <span class="muted">/ ' . e($title) . '</span></span><a href="' . e(url()) . '" class="text-link">Lihat toko ' . icon('arrow') . '</a></header><main class="admin-main">';
    notices($error);
    page_title('TEKNOMART ADMIN', $title, 'Kelola toko Anda dengan lebih mudah.');
}
function admin_footer() { echo '</main><footer class="admin-footer">© ' . date('Y') . ' TeknoMart <span>Admin workspace</span></footer></div></body></html>'; }
