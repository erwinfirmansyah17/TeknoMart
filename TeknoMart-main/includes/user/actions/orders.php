<?php
// Cancel a customer order.

            cancel_order($id, $userId); flash('Pesanan dibatalkan dan stok dikembalikan.'); redirect('pages/pesanan.php');
        