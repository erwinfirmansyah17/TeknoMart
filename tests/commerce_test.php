<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Run: php -d session.save_path=tests/sessions tests/commerce_test.php
// Temporary tables shadow the real tables on this connection. No live data changes.
require __DIR__ . '/../TeknoMart-main/includes/bootstrap.php';
$results = [];
function check($name, $condition) { global $results; $results[] = [$name, (bool)$condition]; }
function rejected($name, $callback) {
    try { $callback(); check($name, false); } catch (DomainException $e) { check($name, true); }
}
foreach (['tb_user','tb_admin','tb_barang','jasa_kirim','metode','orders','details','keranjang','tb_pengeluaran','tb_refund'] as $table) {
    $schema=one("SHOW CREATE TABLE $table")['Create Table'];
    $schema=preg_replace('/CREATE TABLE/', 'CREATE TEMPORARY TABLE', $schema, 1);
    $schema=implode("\n",array_filter(explode("\n",$schema),function($line){return strpos($line,'CONSTRAINT')===false;}));
    $schema=preg_replace('/,\n\)/', "\n)", $schema);
    db($schema);
}
db("INSERT INTO tb_user VALUES (1,'test-user','Test User','0800000001','Test address','test@example.invalid',?), (2,'other-user','Other User','0800000002','Other address','other@example.invalid',?)", [password_hash('test-password',PASSWORD_BCRYPT),password_hash('other-password',PASSWORD_BCRYPT)]);
db("INSERT INTO tb_admin VALUES (1,'test-admin','legacy-password')");
db("INSERT INTO tb_barang VALUES (1,'Test product',100000,'assets/icon/shop.png','Laptop','Test','Description',10,0),(2,'Second product',200000,'assets/icon/shop.png','Monitor','Test','Description',5,0)");
db("INSERT INTO jasa_kirim VALUES (1,'Test courier',20000)");
db("INSERT INTO metode VALUES (1,'Test bank',2000,123456,'')");
check('Wrong customer password rejected',!authenticate('test-user','wrong'));
check('Failed login has no user session',!isset($_SESSION['id_user']));
check('Hashed customer password accepted',authenticate('test-user','test-password'));
check('Legacy admin password accepted',authenticate('test-admin','legacy-password',true));
check('Legacy password migrated',password_verify('legacy-password',one('SELECT password FROM tb_admin WHERE id_admin=1')['password']));
check('Customer and admin sessions coexist',isset($_SESSION['id_user'],$_SESSION['id_admin']));
check('Username injection rejected',!authenticate("test-admin' OR 1=1 -- ",'legacy-password',true));
check('Failed admin login preserves customer session',isset($_SESSION['id_user'])&&!isset($_SESSION['id_admin']));
$hash=one('SELECT password FROM tb_user WHERE id_user=1')['password'];
check('Stored hash cannot be used as password',!authenticate('test-user',$hash));
authenticate('test-user','test-password');
$_SERVER['SCRIPT_NAME']='/index.php';
check('Root URL uses forward slash on Windows',url('css/app.css')==='/css/app.css');
$_SERVER['SCRIPT_NAME']='/TeknoMart-main/TeknoMart-main/pages/login.php';
check('Nested URL retains application directory',url('css/app.css')==='/TeknoMart-main/TeknoMart-main/css/app.css');
$_POST=['password'=>"abcdefgh\0"];
rejected('Password null byte rejected',function(){password_value();});
$_POST=[]; csrf(); rejected('Missing CSRF rejected',function(){verify_csrf();});
$_POST=['csrf'=>$_SESSION['csrf']]; verify_csrf(); check('Valid CSRF accepted',true);
save_cart(1,1,2,true); save_cart(2,1,3,true); save_cart(1,1,1,true);
check('Cart addition merges quantities',(int)one('SELECT qty FROM keranjang WHERE id_user=1 AND id_barang=1')['qty']===3);
rejected('Negative quantity rejected',function(){save_cart(1,1,-1);});
rejected('Quantity beyond stock rejected',function(){save_cart(1,1,11);});
$data=['nama'=>'Test User','telp'=>'0800000001','alamat'=>'Test address','jasa_kirim'=>1,'metode'=>1,'pesan'=>"It's a test",'id_user'=>2,'harga_satuan'=>1];
$order=checkout(1,[1=>3],$data,true);
check('Checkout uses authenticated owner',(int)one('SELECT id_user FROM orders WHERE id_orders=?',[$order])['id_user']===1);
check('Checkout uses database price',(int)one('SELECT harga_satuan FROM details WHERE id_orders=?',[$order])['harga_satuan']===100000);
check('Stock reserved once',(int)one('SELECT stok FROM tb_barang WHERE id_barang=1')['stok']===7);
check('Own cart cleared',!one('SELECT * FROM keranjang WHERE id_user=1'));
check('Other customer cart preserved',(int)one('SELECT qty FROM keranjang WHERE id_user=2')['qty']===3);
rejected('Other customer order access rejected',function()use($order){owned_order($order,2);});
rejected('Other customer cancellation rejected',function()use($order){cancel_order($order,2);});
cancel_order($order,1);
check('Cancellation restores stock',(int)one('SELECT stok FROM tb_barang WHERE id_barang=1')['stok']===10);
rejected('Repeated cancellation rejected',function()use($order){cancel_order($order,1);});
check('Repeated cancellation cannot increase stock',(int)one('SELECT stok FROM tb_barang WHERE id_barang=1')['stok']===10);
$before=(int)one('SELECT COUNT(*) AS n FROM orders')['n'];
rejected('Checkout with insufficient stock rejected',function()use($data){checkout(1,[1=>2,2=>99],$data);});
check('Failed checkout leaves orders unchanged',(int)one('SELECT COUNT(*) AS n FROM orders')['n']===$before);
check('Failed checkout leaves stock unchanged',(int)one('SELECT stok FROM tb_barang WHERE id_barang=1')['stok']===10);
rejected('Unknown shipping method rejected',function()use($data){$data['jasa_kirim']=999;checkout(1,[1=>1],$data);});
rejected('Empty checkout rejected',function()use($data){checkout(1,[],$data);});
rejected('Non-owned cart selection rejected',function()use($data){checkout(1,[2=>1],$data,true);});
$order2=checkout(1,[1=>2],$data);
rejected('Unpaid order cannot be confirmed',function()use($order2){advance_order($order2,'confirm');});
db("UPDATE orders SET status='Menunggu Konfirmasi' WHERE id_orders=?",[$order2]);
advance_order($order2,'confirm');
check('Confirmation increments sold count',(int)one('SELECT terjual FROM tb_barang WHERE id_barang=1')['terjual']===2);
rejected('Repeated confirmation rejected',function()use($order2){advance_order($order2,'confirm');});
check('Sold count not duplicated',(int)one('SELECT terjual FROM tb_barang WHERE id_barang=1')['terjual']===2);
advance_order($order2,'ship');
check('Shipping changes status',one('SELECT status FROM orders WHERE id_orders=?',[$order2])['status']==='Dikirim');
rejected('Shipped order cannot be cancelled',function()use($order2){cancel_order($order2);});
db("INSERT INTO tb_refund (id_orders,tanggal,alasan,bukti,status_refund) VALUES (?,NOW(),'Test reason','test.jpg','Menunggu')",[$order2]);
db("UPDATE orders SET status='Diajukan Retur' WHERE id_orders=?",[$order2]);
decide_refund($order2,false);
check('Rejected refund returns order to completed status',one('SELECT status FROM orders WHERE id_orders=?',[$order2])['status']==='Selesai');
rejected('Repeated refund decision rejected',function()use($order2){decide_refund($order2,true);});
$_GET=['tanggal_awal'=>'2026-02-30','tanggal_akhir'=>'2026-03-01']; $_POST=[];
rejected('Invalid calendar date rejected',function(){report_dates();});
$_GET=['tanggal_awal'=>'2026-09-30','tanggal_akhir'=>'2026-09-01'];
rejected('Reversed date range rejected',function(){report_dates();});
session_destroy();
foreach($results as [$name,$passed]) echo ($passed?'PASS ':'FAIL ').$name.PHP_EOL;
$failed=count(array_filter($results,function($r){return !$r[1];}));
echo count($results).' tests, '.$failed.' failures'.PHP_EOL;
exit($failed?1:0);
