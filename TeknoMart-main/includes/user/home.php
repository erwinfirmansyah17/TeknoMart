<?php
// Storefront home: active offers, featured categories, and featured products.
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';

$categories = query('SELECT kategori, COUNT(*) AS count FROM tb_barang GROUP BY kategori ORDER BY kategori');
$products = query('SELECT b.*, (SELECT AVG(rating) FROM details d WHERE d.id_barang=b.id_barang AND rating>0) AS average_rating FROM tb_barang b ORDER BY (stok>0) DESC, terjual DESC LIMIT 8');
$today = date('Y-m-d');
$slides = [];

foreach (require __DIR__ . '/promotions.php' as $promotion) {
    if ($today < ($promotion['starts'] ?? '') || $today > ($promotion['ends'] ?? '')) continue;
    foreach (array_slice($promotion['product_ids'] ?? [], 0, 3) as $productId) {
        $product = one('SELECT * FROM tb_barang WHERE id_barang=? AND stok>0', [(int)$productId]);
        if (!$product) continue;
        $offer = product_offer($product);
        if (!$offer['discount'] || $offer['label'] !== ($promotion['label'] ?? '')) continue;
        $slides[] = [
            'product' => $product,
            'offer' => $offer,
            'headline' => $promotion['headline'] ?? 'Penawaran spesial',
            'description' => $promotion['description'] ?? 'Harga spesial untuk waktu terbatas.',
        ];
    }
}

if (!$slides) {
    $hero = one("SELECT * FROM tb_barang WHERE kategori='Headphone' AND stok>0 ORDER BY harga DESC LIMIT 1") ?: ($products[0] ?? null);
    if ($hero) {
        $slides[] = [
            'product' => $hero,
            'offer' => product_offer($hero),
            'headline' => 'Upgrade harimu.',
            'description' => 'Temukan perangkat pilihan yang membuat keseharian lebih berarti.',
        ];
    }
}

store_header('Teknomart');
?>
<section class="hero-carousel" aria-label="Penawaran dan produk pilihan" data-carousel data-interval="6500">
    <div class="hero-slides">
        <?php foreach ($slides as $index => $slide): $hero = $slide['product']; $offer = $slide['offer']; ?>
            <article class="hero hero-slide <?= $index === 0 ? 'active' : '' ?>" data-slide aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>">
                <div class="hero-copy">
                    <span class="eyebrow"><span class="live-dot"></span><?= $offer['discount'] ? 'PENAWARAN SPESIAL' : 'PILIHAN TEKNOLOGI, SETIAP HARI' ?></span>
                    <h1><?= e($slide['headline']) ?><br><em><?= e($offer['discount'] ? $offer['label'] : 'Temukan yang lebih berarti.') ?></em></h1>
                    <p><?= e($slide['description']) ?></p>
                    <div class="hero-actions">
                        <a class="button" href="<?= e(url('pages/deskripsi.php?id=' . $hero['id_barang'])) ?>"><?= $offer['discount'] ? 'Ambil penawaran' : 'Lihat produk' ?> <?= icon('arrow') ?></a>
                        <span><?= $offer['discount'] ? 'HEMAT ' . rupiah($offer['discount']) : 'Dipilih untuk' ?><br><strong><?= e($hero['kategori']) ?></strong></span>
                    </div>
                    <div class="hero-footnote"><span><?= sprintf('%02d', $index + 1) ?> / <?= sprintf('%02d', count($slides)) ?></span> <?= $offer['discount'] ? 'PROMO AKTIF ' . $offer['discount'] . '% HEMAT' : 'WORK. PLAY. LIVE BETTER.' ?></div>
                </div>
                <div class="hero-visual">
                    <div class="hero-ring"></div><span class="hero-vertical">EVERYDAY, ELEVATED.</span>
                    <a class="hero-image-link" href="<?= e(url('pages/deskripsi.php?id=' . $hero['id_barang'])) ?>"><img src="<?= e(url($hero['img'])) ?>" alt="<?= e($hero['nama_barang']) ?>"></a>
                    <a class="hero-product" href="<?= e(url('pages/deskripsi.php?id=' . $hero['id_barang'])) ?>">
                        <span class="eyebrow"><?= $offer['discount'] ? e($offer['label']) : 'PILIHAN UNTUK ANDA' ?></span>
                        <strong><?= e($hero['merk'] . ' · ' . $hero['kategori']) ?></strong>
                        <span><?php if ($offer['discount']): ?><del><?= rupiah($offer['original']) ?></del> <strong class="discount-value"><?= rupiah($offer['price']) ?></strong><?php else: ?><?= rupiah($hero['harga']) ?><?php endif; ?> <?= icon('arrow') ?></span>
                    </a>
                    <span class="hero-number">TM / <?= sprintf('%02d', $index + 1) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?php if (count($slides) > 1): ?>
        <button class="carousel-control previous" type="button" aria-label="Penawaran sebelumnya" data-carousel-prev>‹</button>
        <button class="carousel-control next" type="button" aria-label="Penawaran berikutnya" data-carousel-next>›</button>
        <div class="carousel-dots" aria-label="Pilih penawaran">
            <?php foreach ($slides as $index => $slide): ?><button type="button" aria-label="Tampilkan penawaran <?= $index + 1 ?>" aria-current="<?= $index === 0 ? 'true' : 'false' ?>" data-carousel-dot="<?= $index ?>"></button><?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="benefits">
    <div><?= icon('box') ?><span><strong>Pilihan lengkap</strong><small>Perangkat untuk setiap kebutuhan</small></span></div>
    <div><?= icon('shield') ?><span><strong>Belanja lebih nyaman</strong><small>Detail produk yang jelas</small></span></div>
    <div><?= icon('truck') ?><span><strong>Pengiriman pilihan Anda</strong><small>Pilih kurir saat checkout</small></span></div>
</section>

<section class="section">
    <div class="section-title"><div><span class="eyebrow">TEMUKAN KEBUTUHAN ANDA</span><h2>Mulai dari kategori.</h2></div><a class="text-link" href="<?= e(url('pages/product.php')) ?>">Semua kategori <?= icon('arrow') ?></a></div>
    <div class="category-grid">
        <?php
        $categoryIcons = ['laptop'=>'laptop.png','handphone'=>'hp.png','headphone'=>'headphone.png','tv'=>'tv.png','monitor'=>'monitor.png','kulkas'=>'kulkas.png','mesin cuci'=>'mesincuci.png','ac'=>'ac.png'];
        foreach ($categories as $category):
            $image = $categoryIcons[strtolower($category['kategori'])] ?? null;
            if (!$image) continue;
        ?>
            <a class="category-tile" href="<?= e(url('pages/product.php?kategori=' . rawurlencode($category['kategori']))) ?>"><img src="<?= e(url('assets/icon/' . $image)) ?>" alt=""><strong><?= e($category['kategori']) ?></strong><small><?= (int)$category['count'] ?> produk</small></a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <div class="section-title"><div><span class="eyebrow">CURATED FOR YOU</span><h2>Pilihan yang layak dilirik.</h2></div><a class="text-link" href="<?= e(url('pages/product.php')) ?>">Lihat semua <?= icon('arrow') ?></a></div>
    <div class="product-grid"><?php foreach ($products as $product) product_card($product); ?></div>
</section>

<section class="editorial-banner"><div><span class="eyebrow">A LITTLE UPGRADE. A BIG DIFFERENCE.</span><h2>Ruang baru untuk<br>ide-ide besar Anda.</h2><p>Lengkapi meja kerja dengan perangkat pilihan.</p></div><a class="button light" href="<?= e(url('pages/product.php?kategori=Laptop')) ?>">Temukan laptop Anda <?= icon('arrow') ?></a></section>
<?php store_footer(); ?>
