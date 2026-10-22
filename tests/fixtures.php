<?php
if (PHP_SAPI !== 'cli-server' || !function_exists('db')) { http_response_code(404); exit; }
// HTTP integration fixture: temporary tables vanish when the request disconnects.
foreach (['user_profiles','tb_user','tb_admin','tb_barang','jasa_kirim','metode','orders','details','keranjang','tb_pengeluaran','tb_refund'] as $table) {
    $schema=one("SHOW CREATE TABLE $table")['Create Table'];
    $schema=preg_replace('/CREATE TABLE/', 'CREATE TEMPORARY TABLE', $schema, 1);
    $schema=implode("\n",array_filter(explode("\n",$schema),function($line){return strpos($line,'CONSTRAINT')===false;}));
    $schema=preg_replace('/,\n\)/', "\n)", $schema);
    $schema=preg_replace('/AUTO_INCREMENT=\d+/', 'AUTO_INCREMENT=1', $schema);
    db($schema);
}
db("INSERT INTO tb_user VALUES (1,'test-user','Test User','0800000001','Test address','test@example.invalid',?), (2,'other-user','Other User','0800000002','Other address','other@example.invalid',?)", [password_hash('test-password',PASSWORD_BCRYPT),password_hash('other-password',PASSWORD_BCRYPT)]);
db("INSERT INTO user_profiles VALUES (1,'upload/11111111111111111111111111111111.png')");
db("INSERT INTO tb_admin VALUES (1,'test-admin','legacy-password')");
db("INSERT INTO tb_barang VALUES (1,'Test product',100000,'assets/icon/shop.png','Laptop','Test','Description',10,0),(2,'Second product',200000,'assets/icon/shop.png','Monitor','Test','Description',5,0)");
db("INSERT INTO jasa_kirim VALUES (1,'Test courier',20000)");
db("INSERT INTO metode VALUES (1,'Test bank',2000,123456,'')");
db('INSERT INTO keranjang VALUES (1,1,2),(1,2,3)');
foreach ([1=>'Belum Bayar',2=>'Menunggu Konfirmasi',3=>'Diproses',4=>'Dikirim',5=>'Diajukan Retur',6=>'Selesai',7=>'Belum Bayar'] as $id=>$status) {
    db("INSERT INTO orders VALUES (?,?,1,1,'2026-09-30 23:59:59','Test note','Belum Terkonfirmasi',?,'')",[$id,$id===7?2:1,$status]);
    db("INSERT INTO details VALUES (?,1,100000,1,0,'','')",[$id]);
}
db("INSERT INTO tb_refund VALUES (1,5,NOW(),'Damaged item','assets/icon/shop.png','Menunggu')");
db("INSERT INTO tb_pengeluaran VALUES (1,1,70000,1,'2026-09-30 23:59:59')");
ob_start();
register_shutdown_function(function(){
    $snapshot = [
        'users'=>query('SELECT id_user,username,nama FROM tb_user'),
        'products'=>query('SELECT id_barang,nama_barang,stok,terjual,harga FROM tb_barang'),
        'cart'=>query('SELECT * FROM keranjang'),
        'orders'=>query('SELECT id_orders,id_user,status FROM orders ORDER BY id_orders'),
        'details'=>query('SELECT id_orders,rating,ulasan,harga_satuan,qty FROM details'),
        'expenses'=>query('SELECT harga,qty FROM tb_pengeluaran'),
        'refunds'=>query('SELECT status_refund FROM tb_refund'),
        'profiles'=>query('SELECT * FROM user_profiles'),
        'userSession'=>$_SESSION['id_user']??null,
        'adminSession'=>$_SESSION['id_admin']??null,
    ];
    header('X-Test-State: '.base64_encode(json_encode($snapshot)));
    // Remove only images created by this request's temporary fixture records.
    $paths=array_merge(query("SELECT photo AS path FROM user_profiles"),query("SELECT img AS path FROM tb_barang WHERE img LIKE 'upload/%'"),query("SELECT bukti_pembayaran AS path FROM orders WHERE bukti_pembayaran LIKE 'upload/%'"),query("SELECT bukti AS path FROM tb_refund WHERE bukti LIKE 'upload/%'"),query("SELECT foto AS path FROM details WHERE foto LIKE 'upload/%'"));
    foreach ($paths as $row) if(preg_match('~^upload/[a-f0-9]{32}\.(jpg|png|webp)$~',$row['path'])) {
        $path=__DIR__.'/../TeknoMart-main/'.$row['path'];
        if (is_file($path)) unlink($path);
    }
});
