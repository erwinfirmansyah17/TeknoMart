<?php
function product_offer($product) {
    $price = (int)$product['harga'];
    $best = ['original' => $price, 'price' => $price, 'discount' => 0, 'label' => ''];
    $today = date('Y-m-d');
    foreach (require __DIR__ . '/promotions.php' as $promo) {
        if ($today < ($promo['starts'] ?? '') || $today > ($promo['ends'] ?? '') || !in_array((int)$product['id_barang'], $promo['product_ids'] ?? [], true)) continue;
        $percent = (int)($promo['percent'] ?? 0);
        if ($percent < 1 || $percent > 100) continue;
        $discount = (int)floor($price * $percent / 100);
        if ($discount > $best['discount']) $best = ['original'=>$price,'price'=>$price-$discount,'discount'=>$discount,'label'=>(string)$promo['label']];
    }
    return $best;
}
function profile_photo($userId) {
    return one('SELECT photo FROM user_profiles WHERE id_user = ?', [(int)$userId])['photo'] ?? '';
}
