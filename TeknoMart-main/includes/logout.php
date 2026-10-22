<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/layout.php';
$adminLogout = $adminLogout ?? false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if ($adminLogout) unset($_SESSION['id_admin'], $_SESSION['admin_username']);
        else unset($_SESSION['id_user'], $_SESSION['username'], $_SESSION['buy_now'], $_SESSION['selected_cart_items']);
        session_regenerate_id(true);
        flash('Anda berhasil keluar.');
        redirect($adminLogout ? 'bangjanganbang/login_admin.php' : 'index.php');
    } catch (DomainException $ex) { $error = $ex->getMessage(); }
}
store_header('Keluar dari akun', $error);
page_title('SAMPAI JUMPA LAGI', 'Keluar dari akun?');
echo '<section class="panel narrow"><p>Anda dapat masuk kembali kapan saja.</p><form method="post">' . csrf() . '<button class="button">Ya, keluar</button></form></section>';
store_footer();
