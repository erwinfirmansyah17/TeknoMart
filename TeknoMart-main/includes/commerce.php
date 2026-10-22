<?php
function save_cart($userId, $productId, $quantity, $add = false) {
    global $conn;
    $quantity = positive($quantity);
    $conn->begin_transaction();
    try {
        $product = one('SELECT * FROM tb_barang WHERE id_barang = ? FOR UPDATE', [(int)$productId]);
        if (!$product) throw new DomainException('Produk tidak ditemukan.');
        $old = one('SELECT SUM(qty) AS qty FROM keranjang WHERE id_user = ? AND id_barang = ?', [(int)$userId, (int)$productId]);
        if ($add) $quantity += (int)($old['qty'] ?? 0);
        if ($quantity > $product['stok']) throw new DomainException('Jumlah melebihi stok tersedia (' . $product['stok'] . ').');
        db('DELETE FROM keranjang WHERE id_user = ? AND id_barang = ?', [(int)$userId, (int)$productId]);
        db('INSERT INTO keranjang (id_user, id_barang, qty) VALUES (?, ?, ?)', [(int)$userId, (int)$productId, $quantity]);
        $conn->commit();
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}
function checkout($userId, $items, $data, $fromCart = false) {
    global $conn;
    if (!$items) throw new DomainException('Pilih produk terlebih dahulu.');
    if (!one('SELECT id_jasa FROM jasa_kirim WHERE id_jasa = ?', [(int)($data['jasa_kirim'] ?? 0)]) || !one('SELECT id_metode FROM metode WHERE id_metode = ?', [(int)($data['metode'] ?? 0)])) throw new DomainException('Pilih pengiriman dan metode pembayaran.');
    foreach (['nama' => 99, 'telp' => 20, 'alamat' => 99] as $field => $max) {
        if (!is_string($data[$field] ?? null) || trim($data[$field]) === '' || mb_strlen($data[$field]) > $max) throw new DomainException('Lengkapi data penerima; ' . $field . ' maksimal ' . $max . ' karakter.');
    }
    $note = is_string($data['pesan'] ?? null) ? trim($data['pesan']) : '';
    if (mb_strlen($note) > 255) throw new DomainException('Catatan maksimal 255 karakter.');
    ksort($items); // Consistent lock order for concurrent checkouts.
    $conn->begin_transaction();
    try {
        $products = [];
        foreach ($items as $id => $quantity) {
            $quantity = positive($quantity);
            $product = one('SELECT * FROM tb_barang WHERE id_barang = ? FOR UPDATE', [(int)$id]);
            if (!$product || $quantity > $product['stok']) throw new DomainException('Stok produk tidak cukup. Periksa kembali keranjang Anda.');
            if ($fromCart) {
                $cart = one('SELECT SUM(qty) AS qty FROM keranjang WHERE id_barang = ? AND id_user = ?', [(int)$id, (int)$userId]);
                if ((int)($cart['qty'] ?? 0) !== $quantity) throw new DomainException('Keranjang telah berubah. Silakan periksa kembali.');
            }
            $products[] = [$product, $quantity];
        }
        db('UPDATE tb_user SET nama = ?, telp = ?, alamat = ? WHERE id_user = ?', [trim($data['nama']), trim($data['telp']), trim($data['alamat']), (int)$userId]);
        db("INSERT INTO orders (id_user, id_jasa, id_metode, tanggal, pesan, konfirmasi, status, bukti_pembayaran) VALUES (?, ?, ?, NOW(), ?, 'Belum Terkonfirmasi', 'Belum Bayar', '')", [(int)$userId, (int)$data['jasa_kirim'], (int)$data['metode'], $note]);
        $orderId = $conn->insert_id;
        foreach ($products as [$product, $quantity]) {
            $offer = product_offer($product);
            db("INSERT INTO details (id_orders, id_barang, harga_satuan, qty, rating, ulasan, foto) VALUES (?, ?, ?, ?, 0, '', '')", [$orderId, (int)$product['id_barang'], $offer['price'], $quantity]);
            db('UPDATE tb_barang SET stok = stok - ? WHERE id_barang = ?', [$quantity, (int)$product['id_barang']]);
            if ($fromCart) db('DELETE FROM keranjang WHERE id_barang = ? AND id_user = ?', [(int)$product['id_barang'], (int)$userId]);
        }
        $conn->commit();
        return $orderId;
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}
function cancel_order($id, $userId = null) {
    global $conn;
    $conn->begin_transaction();
    try {
        $order = $userId === null ? one('SELECT * FROM orders WHERE id_orders = ? FOR UPDATE', [(int)$id]) : owned_order($id, $userId, true);
        $allowed = $userId === null ? ['Belum Bayar', 'Menunggu Konfirmasi'] : ['Belum Bayar'];
        if (!$order || !in_array($order['status'], $allowed, true)) throw new DomainException('Pesanan ini tidak dapat dibatalkan.');
        foreach (order_details($id) as $item) db('UPDATE tb_barang SET stok = stok + ? WHERE id_barang = ?', [(int)$item['qty'], (int)$item['id_barang']]);
        db("UPDATE orders SET status = 'Dibatalkan' WHERE id_orders = ?", [(int)$id]);
        $conn->commit();
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}
function advance_order($id, $action) {
    global $conn;
    $conn->begin_transaction();
    try {
        $order = one('SELECT * FROM orders WHERE id_orders = ? FOR UPDATE', [(int)$id]);
        $transitions = ['confirm' => ['Menunggu Konfirmasi', 'Diproses'], 'ship' => ['Diproses', 'Dikirim']];
        if (!isset($transitions[$action]) || !$order || $order['status'] !== $transitions[$action][0]) throw new DomainException('Status pesanan telah berubah atau belum memenuhi syarat.');
        db("UPDATE orders SET status = ?, konfirmasi = 'Terkonfirmasi' WHERE id_orders = ?", [$transitions[$action][1], (int)$id]);
        if ($action === 'confirm') foreach (order_details($id) as $item) db('UPDATE tb_barang SET terjual = terjual + ? WHERE id_barang = ?', [(int)$item['qty'], (int)$item['id_barang']]);
        $conn->commit();
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}
function decide_refund($id, $accept) {
    global $conn;
    $conn->begin_transaction();
    try {
        $order = one('SELECT * FROM orders WHERE id_orders = ? FOR UPDATE', [(int)$id]);
        if (!$order || $order['status'] !== 'Diajukan Retur') throw new DomainException('Pengajuan retur sudah diproses.');
        $refund = one("SELECT * FROM tb_refund WHERE id_orders = ? AND status_refund = 'Menunggu' ORDER BY id_refund DESC LIMIT 1", [(int)$id]);
        if (!$refund) throw new DomainException('Pengajuan retur tidak ditemukan.');
        db('UPDATE tb_refund SET status_refund = ? WHERE id_orders = ? AND status_refund = ?', [$accept ? 'Diterima' : 'Ditolak', (int)$id, 'Menunggu']);
        db('UPDATE orders SET status = ? WHERE id_orders = ?', [$accept ? 'Retur' : 'Selesai', (int)$id]);
        // Returned units may be damaged; stock is not automatically resold.
        $conn->commit();
    } catch (Throwable $e) { $conn->rollback(); throw $e; }
}
