<?php
// Order ratings, receipt confirmation, and return requests.

    $allowed = $route === 'rating'
        ? in_array($order['status'], ['Dikirim', 'Selesai Belum Dinilai'], true)
        : in_array($order['status'], ['Dikirim', 'Selesai', 'Selesai Belum Dinilai'], true) && !one('SELECT id_refund FROM tb_refund WHERE id_orders = ?', [$id]);
    if (!$allowed) {
        page_title('PESANAN #TM-' . $id, 'Periksa status pesanan Anda.');
        empty_state('Tindakan belum tersedia', 'Pesanan ini belum memenuhi syarat atau permintaan Anda sudah diproses.', 'pages/pesanan.php', 'Kembali ke pesanan');
        store_footer();
        return;
    }
    page_title('ORDER #TM-'.$id,$route==='rating'?'Bagaimana pengalaman Anda?':'Ajukan pengembalian.',$route==='rating'?'Konfirmasikan produk sudah diterima dan bagikan ulasan Anda.':'Jelaskan kendala produk dan lampirkan bukti untuk ditinjau admin.'); ?>
    <form method="post" enctype="multipart/form-data" class="panel form-stack narrow"><?= csrf() ?><?php if($route==='rating'): foreach(order_details($id) as $item): $pid=(int)$item['id_barang']; ?><div class="mini-product"><img src="<?= e(url($item['img'])) ?>" alt=""><strong><?= e($item['nama_barang']) ?></strong></div><label class="field"><span>Rating</span><select name="ratings[<?= $pid ?>]" required><option value="">Pilih rating</option><?php for($r=5;$r>=1;$r--): ?><option value="<?= $r ?>"><?= str_repeat('★',$r) ?> (<?= $r ?>)</option><?php endfor; ?></select></label><label class="field"><span>Ulasan (opsional)</span><textarea name="reviews[<?= $pid ?>]" maxlength="255" rows="3"></textarea></label><?php field('Foto produk (opsional)','photos['.$pid.']','','file','accept="image/jpeg,image/png,image/webp"'); endforeach; else: ?><label class="field"><span>Alasan retur</span><textarea name="alasan" maxlength="255" rows="4" required><?= e(input('alasan')) ?></textarea></label><?php field('Foto bukti (maks. 5 MB)','bukti','','file','accept="image/jpeg,image/png,image/webp" required'); endif; ?><button class="button"><?= $route==='rating'?'Pesanan diterima & kirim ulasan':'Kirim pengajuan retur' ?></button></form>
    <?php
