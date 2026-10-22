<?php
// Submit a return request.

            if (!in_array($order['status'], ['Dikirim','Selesai','Selesai Belum Dinilai'], true)) throw new DomainException('Pesanan ini tidak dapat diajukan retur.');
            $reason = required('alasan',255); $image = upload_image($_FILES['bukti'] ?? null);
            $conn->begin_transaction();
            try {
                $locked = owned_order($id, $userId, true);
                if (!in_array($locked['status'], ['Dikirim','Selesai','Selesai Belum Dinilai'], true) || one('SELECT id_refund FROM tb_refund WHERE id_orders = ?', [$id])) throw new DomainException('Retur untuk pesanan ini sudah pernah diajukan.');
                db("INSERT INTO tb_refund (id_orders, tanggal, alasan, bukti, status_refund) VALUES (?, NOW(), ?, ?, 'Menunggu')", [$id, $reason, $image]);
                db("UPDATE orders SET status = 'Diajukan Retur' WHERE id_orders = ?", [$id]); $conn->commit();
            } catch (Throwable $ex) { $conn->rollback(); throw $ex; }
            flash('Pengajuan retur berhasil dikirim.'); redirect('pages/pesanan.php');
        