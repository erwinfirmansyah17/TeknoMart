<?php
// Save product ratings and confirm receipt.

            if (!in_array($order['status'], ['Dikirim','Selesai Belum Dinilai'], true)) throw new DomainException('Pesanan ini belum dapat dinilai atau sudah selesai.');
            $details = order_details($id); $reviews = [];
            foreach ($details as $item) {
                $pid = (int)$item['id_barang'];
                $rating = positive($_POST['ratings'][$pid] ?? 0, 'Rating');
                $review = $_POST['reviews'][$pid] ?? '';
                if ($rating > 5 || !is_string($review) || mb_strlen($review) > 255) throw new DomainException('Rating harus 1–5 dan ulasan maksimal 255 karakter.');
                $file = null;
                if (isset($_FILES['photos']['name'][$pid])) foreach (['name','type','tmp_name','error','size'] as $key) $file[$key] = $_FILES['photos'][$key][$pid];
                $reviews[] = [$rating, trim($review), upload_image($file, true), $id, $pid];
            }
            $conn->begin_transaction();
            try {
                $locked = owned_order($id, $userId, true);
                if (!in_array($locked['status'], ['Dikirim','Selesai Belum Dinilai'], true)) throw new DomainException('Pesanan sudah diproses.');
                foreach ($reviews as $review) db('UPDATE details SET rating = ?, ulasan = ?, foto = ? WHERE id_orders = ? AND id_barang = ?', $review);
                db("UPDATE orders SET status = 'Selesai' WHERE id_orders = ?", [$id]); $conn->commit();
            } catch (Throwable $ex) { $conn->rollback(); throw $ex; }
            flash('Terima kasih. Pesanan selesai dan ulasan Anda tersimpan.'); redirect('pages/pesanan.php');
        