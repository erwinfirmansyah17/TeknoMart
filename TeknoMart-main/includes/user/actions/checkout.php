<?php
// Create an order from checkout.

            $items = [];
            if ($route === 'rinciannow') $items = $_SESSION['buy_now'] ?? [];
            else foreach (query('SELECT id_barang, SUM(qty) AS qty FROM keranjang WHERE id_user = ? GROUP BY id_barang', [$userId]) as $row) {
                if (in_array((int)$row['id_barang'], $_SESSION['selected_cart_items'] ?? [], true)) $items[(int)$row['id_barang']] = (int)$row['qty'];
            }
            $orderId = checkout($userId, $items, $_POST, $route === 'rincian');
            unset($_SESSION['buy_now'], $_SESSION['selected_cart_items']);
            flash('Pesanan berhasil dibuat. Lanjutkan pembayaran.'); redirect('pages/pembayaran.php?id=' . $orderId);
        