<?php
// Add to cart and buy-now actions.

            if (!$user) { flash('Silakan masuk untuk berbelanja.', 'info'); redirect('pages/login.php'); }
            $qty = positive($_POST['qty'] ?? 0);
            if ($qty > $product['stok']) throw new DomainException('Jumlah melebihi stok tersedia.');
            if ($action === 'buy') { $_SESSION['buy_now'] = [$id => $qty]; redirect('pages/rinciannow.php'); }
            save_cart($userId, $id, $qty, true); flash('Produk berhasil ditambahkan ke keranjang.'); redirect('pages/keranjang.php');
        