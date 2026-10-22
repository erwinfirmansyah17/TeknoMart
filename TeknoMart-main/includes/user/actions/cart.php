<?php
// Cart quantity, removal, and checkout selection.

            if ($action === 'remove') { db('DELETE FROM keranjang WHERE id_user = ? AND id_barang = ?', [$userId, $id]); flash('Produk dihapus dari keranjang.'); }
            elseif ($action === 'update') { save_cart($userId, $id, $_POST['qty'] ?? 0); flash('Jumlah produk diperbarui.'); }
            elseif ($action === 'checkout') {
                $selected = $_POST['selected'] ?? [];
                if (!is_array($selected) || !$selected) throw new DomainException('Pilih minimal satu produk untuk checkout.');
                $_SESSION['selected_cart_items'] = array_values(array_unique(array_map('intval', $selected)));
                redirect('pages/rincian.php');
            }
            redirect('pages/keranjang.php');
        