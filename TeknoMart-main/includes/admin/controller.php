<?php
require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__) . '/layout.php';
$admin = require_user(true);
$error = '';
$id = (int)input('id', '0');

// Handle form submissions and prepare data shared by the admin views.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = input('action');
        if (in_array($route, ['all_product', 'edit_product', 'add_product'], true)) {
            require __DIR__ . '/actions/products.php';
        } elseif (in_array($route, ['konfirmasi', 'diproses', 'batal', 'refund', 'selesai'], true)) {
            require __DIR__ . '/actions/orders.php';
        }
    } catch (DomainException $ex) { $error = $ex->getMessage(); }
    catch (mysqli_sql_exception $ex) { error_log($ex->getMessage()); $error = 'Data belum dapat disimpan. Periksa isian lalu coba lagi.'; }
}
$report = in_array($route,['pendapatan','pengeluaran','revenue','outcome'],true);
if ($report) {
    try { [$start,$end]=report_dates(); } catch(DomainException $ex) { $error=$ex->getMessage(); $start=date('Y-m-01'); $end=date('Y-m-d'); }
    $income=in_array($route,['pendapatan','revenue'],true);
    $data=$income
        ? query("SELECT DATE(o.tanggal) AS tanggal,SUM(d.harga_satuan*d.qty) AS total FROM orders o JOIN details d ON d.id_orders=o.id_orders WHERE o.status IN ('Selesai','Selesai Belum Dinilai') AND o.tanggal>=? AND o.tanggal<DATE_ADD(?,INTERVAL 1 DAY) GROUP BY DATE(o.tanggal) ORDER BY tanggal",[$start,$end])
        : query('SELECT p.tanggal,COALESCE(b.nama_barang,\'Produk dihapus\') AS nama_barang,p.harga,p.qty,p.harga*p.qty AS total FROM tb_pengeluaran p LEFT JOIN tb_barang b ON b.id_barang=p.id_barang WHERE p.tanggal>=? AND p.tanggal<DATE_ADD(?,INTERVAL 1 DAY) ORDER BY p.tanggal DESC',[$start,$end]);
    if (in_array($route,['revenue','outcome'],true)) {
        if ($error) { flash($error,'error'); redirect('bangjanganbang/'.($income?'pendapatan':'pengeluaran').'.php'); }
        header('Content-Type: text/csv; charset=utf-8'); header('Content-Disposition: attachment; filename="'.($income?'Pemasukan':'Pengeluaran').'-'.$start.'-'.$end.'.csv"');
        $out=fopen('php://output','w'); fwrite($out,"\xEF\xBB\xBF"); fputcsv($out,$income?['Tanggal','Pemasukan']:['Tanggal','Produk','Harga beli','Jumlah','Pengeluaran']);
        foreach($data as $row) { foreach($row as &$value) if (is_string($value) && preg_match('/^[=+@\-\t\r]/',$value)) $value="'".$value; unset($value); fputcsv($out,$row); } fclose($out); exit;
    }
}
$titles=['index'=>'Ringkasan toko','backs'=>'Ringkasan toko','all_product'=>'Semua produk','add_product'=>'Tambah produk & stok','edit_product'=>'Kelola produk','konfirmasi'=>'Konfirmasi pesanan','diproses'=>'Pengiriman','selesai'=>'Pesanan selesai','batal'=>'Pesanan dibatalkan','refund'=>'Pengajuan retur','pendapatan'=>'Laporan pemasukan','pengeluaran'=>'Laporan pengeluaran','user'=>'Pelanggan'];
admin_header($titles[$route],$route,$error);

$viewByRoute = [
    'index' => 'dashboard.php', 'backs' => 'dashboard.php',
    'all_product' => 'products.php', 'edit_product' => 'products.php', 'add_product' => 'products.php',
    'pendapatan' => 'reports.php', 'pengeluaran' => 'reports.php', 'revenue' => 'reports.php', 'outcome' => 'reports.php',
    'user' => 'customers.php',
    'konfirmasi' => 'orders.php', 'diproses' => 'orders.php', 'selesai' => 'orders.php', 'batal' => 'orders.php', 'refund' => 'orders.php',
];
require __DIR__ . '/views/' . ($viewByRoute[$route] ?? 'orders.php');
admin_footer();
