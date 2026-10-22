<?php
// Product discovery: filters, search, sorting, and pagination.
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';

$category = input('kategori');
$brand = input('merk');
$search = input('search_query');
$sort = input('sort', $route === 'filterprice' ? (input('harga') === 'desc' ? 'price_desc' : 'price_asc') : 'recommended');
$sortOptions = [
    'recommended' => 'average_rating DESC, b.terjual DESC',
    'price_asc' => 'b.harga ASC',
    'price_desc' => 'b.harga DESC',
    'newest' => 'b.id_barang DESC',
];
if (!isset($sortOptions[$sort])) $sort = 'recommended';

$categories = query('SELECT kategori, COUNT(*) AS count FROM tb_barang GROUP BY kategori ORDER BY kategori');
$brands = $category !== ''
    ? query('SELECT DISTINCT merk FROM tb_barang WHERE kategori = ? ORDER BY merk', [$category])
    : query('SELECT DISTINCT merk FROM tb_barang ORDER BY merk');

$filters = [];
$parameters = [];
if ($category !== '') { $filters[] = 'b.kategori = ?'; $parameters[] = $category; }
if ($brand !== '') { $filters[] = 'b.merk = ?'; $parameters[] = $brand; }
if ($search !== '') {
    $filters[] = '(b.nama_barang LIKE ? OR b.merk LIKE ? OR b.kategori LIKE ?)';
    array_push($parameters, "%$search%", "%$search%", "%$search%");
}
$where = $filters ? ' WHERE ' . implode(' AND ', $filters) : '';
$total = (int)one('SELECT COUNT(*) AS n FROM tb_barang b' . $where, $parameters)['n'];
$pageCount = max(1, (int)ceil($total / 12));
$page = max(1, min($pageCount, (int)input('page', '1')));
$offset = ($page - 1) * 12;
$products = query(
    'SELECT b.*, (SELECT AVG(rating) FROM details d WHERE d.id_barang=b.id_barang AND rating>0) AS average_rating FROM tb_barang b' . $where . ' ORDER BY ' . $sortOptions[$sort] . " LIMIT 12 OFFSET $offset",
    $parameters
);

store_header('Koleksi produk');
page_title('EXPLORE THE COLLECTION', $search !== '' ? 'Hasil pencarian: ' . $search : ($category ?: 'Temukan teknologi Anda.'), 'Pilihan elektronik untuk bekerja, menikmati hiburan, dan menjalani hari.');
?>
<div class="catalog-layout">
    <aside class="filter-panel">
        <form method="get" action="<?= e(url('pages/product.php')) ?>">
            <h3>Filter produk</h3>
            <input type="hidden" name="search_query" value="<?= e($search) ?>">
            <label class="field"><span>Kategori</span><select name="kategori"><option value="">Semua kategori</option><?php foreach ($categories as $item): ?><option <?= $category === $item['kategori'] ? 'selected' : '' ?> value="<?= e($item['kategori']) ?>"><?= e($item['kategori']) ?></option><?php endforeach; ?></select></label>
            <label class="field"><span>Merek</span><select name="merk"><option value="">Semua merek</option><?php foreach ($brands as $item): ?><option <?= $brand === $item['merk'] ? 'selected' : '' ?> value="<?= e($item['merk']) ?>"><?= e($item['merk']) ?></option><?php endforeach; ?></select></label>
            <label class="field"><span>Urutkan</span><select name="sort"><?php foreach (['recommended'=>'Rekomendasi','price_asc'=>'Harga terendah','price_desc'=>'Harga tertinggi','newest'=>'Terbaru'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
            <button class="button wide">Terapkan filter</button><a class="filter-reset" href="<?= e(url('pages/product.php')) ?>">Atur ulang</a>
        </form>
    </aside>
    <section>
        <div class="result-bar"><span><strong><?= $total ?></strong> produk ditemukan</span><span class="muted">Halaman <?= $page ?> dari <?= $pageCount ?></span></div>
        <?php if (!$products) empty_state('Produk belum ditemukan', 'Coba kata kunci lain atau atur ulang filter Anda.'); ?>
        <div class="product-grid catalog-grid"><?php foreach ($products as $product) product_card($product); ?></div>
        <nav class="pagination" aria-label="Halaman katalog">
            <?php for ($number = max(1, $page - 2); $number <= min($pageCount, $page + 2); $number++): ?>
                <a <?= $number === $page ? 'aria-current="page" class="active"' : '' ?> href="<?= e(url('pages/product.php?' . http_build_query(['kategori'=>$category,'merk'=>$brand,'search_query'=>$search,'sort'=>$sort,'page'=>$number]))) ?>"><?= $number ?></a>
            <?php endfor; ?>
        </nav>
    </section>
</div>
<?php store_footer(); ?>
