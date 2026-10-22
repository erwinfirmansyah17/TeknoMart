<?php
// Product creation, editing, stock intake, and deletion.
if ($action === 'delete') {
    $conn->begin_transaction();
    try {
        $existing = one('SELECT id_barang FROM tb_barang WHERE id_barang=? FOR UPDATE', [$id]);
        if (!$existing) throw new DomainException('Produk tidak ditemukan.');
        if (one('SELECT id_barang FROM details WHERE id_barang=? LIMIT 1', [$id])) throw new DomainException('Produk memiliki riwayat pesanan dan tidak dapat dihapus. Ubah stok menjadi 0 untuk menghentikan penjualan.');
        db('DELETE FROM keranjang WHERE id_barang=?', [$id]);
        db('UPDATE tb_pengeluaran SET id_barang=NULL WHERE id_barang=?', [$id]);
        db('DELETE FROM tb_barang WHERE id_barang=?', [$id]);
        $conn->commit();
    } catch (Throwable $ex) { $conn->rollback(); throw $ex; }
    flash('Produk berhasil dihapus.');
} elseif ($action === 'stock') {
    $qty = positive($_POST['stok'] ?? 0, 'Stok tambahan');
    $cost = positive($_POST['harga_beli'] ?? 0, 'Harga beli');
    $conn->begin_transaction();
    try {
        $product = one('SELECT * FROM tb_barang WHERE id_barang=? FOR UPDATE', [$id]);
        if (!$product || (int)$product['stok'] + $qty > 2147483647) throw new DomainException('Produk atau jumlah stok tidak valid.');
        db('UPDATE tb_barang SET stok=stok+? WHERE id_barang=?', [$qty, $id]);
        db('INSERT INTO tb_pengeluaran (id_barang,harga,qty,tanggal) VALUES (?,?,?,NOW())', [$id, $cost, $qty]);
        $conn->commit();
    } catch (Throwable $ex) { $conn->rollback(); throw $ex; }
    flash('Stok dan pengeluaran berhasil dicatat.');
} elseif ($action === 'save') {
    $name = required('nama_barang', 255);
    $category = required('kategori');
    $brand = required('merk');
    $description = required('keterangan', 5000);
    $price = positive($_POST['harga'] ?? 0, 'Harga jual');
    $stock = filter_var($_POST['stok'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]);
    if ($stock === false) throw new DomainException('Stok harus berupa angka nol atau lebih.');
    $existing = $id ? one('SELECT * FROM tb_barang WHERE id_barang=?', [$id]) : null;
    if ($id && !$existing) throw new DomainException('Produk tidak ditemukan.');
    $cost = $id ? 0 : positive($_POST['harga_beli'] ?? 0, 'Harga beli');
    $image = upload_image($_FILES['img'] ?? null, (bool)$id);
    $conn->begin_transaction();
    try {
        if ($id) {
            db('UPDATE tb_barang SET nama_barang=?,kategori=?,merk=?,keterangan=?,harga=?,stok=?,img=? WHERE id_barang=?', [$name, $category, $brand, $description, $price, $stock, $image ?: $existing['img'], $id]);
        } else {
            db('INSERT INTO tb_barang (nama_barang,kategori,merk,keterangan,harga,stok,img,terjual) VALUES (?,?,?,?,?,?,?,0)', [$name, $category, $brand, $description, $price, $stock, $image]);
            $newId = $conn->insert_id;
            if ($stock > 0) db('INSERT INTO tb_pengeluaran (id_barang,harga,qty,tanggal) VALUES (?,?,?,NOW())', [$newId, $cost, $stock]);
        }
        $conn->commit();
    } catch (Throwable $ex) { $conn->rollback(); throw $ex; }
    flash('Produk berhasil disimpan.');
} else {
    throw new DomainException('Tindakan tidak valid.');
}
redirect('bangjanganbang/all_product.php');
