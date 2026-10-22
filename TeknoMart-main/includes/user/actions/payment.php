<?php
// Submit payment proof.

            if ($order['status'] !== 'Belum Bayar') throw new DomainException('Bukti pembayaran sudah dikirim atau pesanan tidak dapat dibayar.');
            $image = upload_image($_FILES['bukti'] ?? null);
            $updated = db("UPDATE orders SET bukti_pembayaran = ?, status = 'Menunggu Konfirmasi' WHERE id_orders = ? AND id_user = ? AND status = 'Belum Bayar'", [$image, $id, $userId]);
            if (!$updated->affected_rows) throw new DomainException('Status pesanan telah berubah. Muat ulang halaman.');
            flash('Bukti pembayaran dikirim. Tunggu konfirmasi admin.'); redirect('pages/pesanan.php');
        