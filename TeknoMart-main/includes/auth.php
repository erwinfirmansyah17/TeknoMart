<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/layout.php';
$isAdmin = $route === 'login_admin';
$isRegister = $route === 'register';
$isChange = $route === 'ubah_pw';
$isHelp = $route === 'email';
$error = '';
$user = $isChange ? require_user() : null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        if ($isRegister) {
            $username = required('username'); $name = required('nama'); $address = required('alamat'); $email = required('email');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Alamat email tidak valid.');
            if (one('SELECT id_user FROM tb_user WHERE username = ? OR email = ?', [$username, $email])) throw new DomainException('Username atau email sudah digunakan.');
            $password = password_value();
            if ($password !== ($_POST['confirm'] ?? null)) throw new DomainException('Konfirmasi password tidak cocok.');
            db('INSERT INTO tb_user (username, nama, telp, alamat, email, password) VALUES (?, ?, ?, ?, ?, ?)', [$username, $name, '', $address, $email, password_hash($password, PASSWORD_BCRYPT)]);
            flash('Akun berhasil dibuat. Silakan masuk.'); redirect('pages/login.php');
        } elseif ($isChange) {
            $old = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
            if (!password_verify($old, $user['password'])) throw new DomainException('Password saat ini tidak sesuai.');
            $password = password_value();
            if ($password !== ($_POST['confirm'] ?? null)) throw new DomainException('Konfirmasi password tidak cocok.');
            db('UPDATE tb_user SET password = ? WHERE id_user = ?', [password_hash($password, PASSWORD_BCRYPT), (int)$user['id_user']]);
            session_regenerate_id(true); flash('Password berhasil diperbarui.'); redirect('pages/profile.php');
        } elseif (!$isHelp) {
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if (!authenticate(input('username'), $password, $isAdmin)) throw new DomainException('Username atau password salah.');
            flash('Selamat datang kembali.'); redirect($isAdmin ? 'bangjanganbang/index.php' : 'index.php');
        }
    } catch (DomainException $ex) { $error = $ex->getMessage(); }
    catch (mysqli_sql_exception $ex) { error_log($ex->getMessage()); $error = 'Data belum dapat disimpan. Silakan coba lagi.'; }
}
$title = $isRegister ? 'Buat akun Anda' : ($isChange ? 'Ubah password' : ($isHelp ? 'Bantuan akun' : ($isAdmin ? 'Selamat datang, Admin.' : 'Senang melihat Anda lagi.')));
head_html($title, 'auth-page');
?>
<main class="auth-shell">
    <section class="auth-story">
        <a class="brand" href="<?= e(url()) ?>"><?= brand() ?></a>
        <div class="auth-story-content"><span class="eyebrow">YOUR EVERYDAY TECH COMPANION</span><h1>Ide besar.<br>Teknologi tepat.<br><em>Mulai di sini.</em></h1><p>Temukan perangkat yang mendukung pekerjaan, hiburan, dan setiap momen Anda.</p><div class="auth-orbit"><?= icon('box') ?><span>Teknologi untuk<br>hidup lebih baik.</span></div></div>
        <small>© <?= date('Y') ?> TeknoMart</small>
    </section>
    <section class="auth-form-panel"><a class="back-link" href="<?= e(url()) ?>">← Kembali ke toko</a><div class="auth-form-wrap"><span class="eyebrow"><?= $isAdmin ? 'ADMIN WORKSPACE' : 'AKUN TEKNOMART' ?></span><h2><?= e($title) ?></h2><p class="muted"><?= $isRegister ? 'Lengkapi data berikut untuk mulai berbelanja.' : ($isChange ? 'Gunakan password baru yang kuat dan mudah Anda ingat.' : 'Masuk untuk melanjutkan aktivitas Anda.') ?></p>
    <?php notices($error); ?>
    <?php if ($isHelp): ?>
        <div class="notice info">Untuk memulihkan akun, hubungi pengelola toko agar kepemilikan akun Anda dapat diverifikasi. Perubahan password melalui alamat email saja tidak tersedia.</div><p>Masih dapat masuk ke akun? Ubah password dari halaman profil.</p><a class="button" href="<?= e(url('pages/login.php')) ?>">Kembali ke halaman masuk</a>
    <?php else: ?>
    <form method="post" class="form-stack"><?= csrf() ?>
        <?php if ($isRegister) { field('Nama lengkap', 'nama', input('nama'), 'text', 'required maxlength="99" autocomplete="name"'); }
        if (!$isChange) field('Username', 'username', input('username'), 'text', 'required maxlength="99" autocomplete="username"');
        if ($isRegister) { field('Alamat', 'alamat', input('alamat'), 'text', 'required maxlength="99" autocomplete="street-address"'); field('Email', 'email', input('email'), 'email', 'required maxlength="99" autocomplete="email"'); }
        if ($isChange) field('Password saat ini', 'current_password', '', 'password', 'required autocomplete="current-password"');
        field($isChange ? 'Password baru' : 'Password', 'password', '', 'password', 'required autocomplete="' . (($isRegister || $isChange) ? 'new-password' : 'current-password') . '"' . (($isRegister || $isChange) ? ' minlength="8" maxlength="72"' : ''));
        if ($isRegister || $isChange) field('Konfirmasi password', 'confirm', '', 'password', 'required minlength="8" maxlength="72" autocomplete="new-password"'); ?>
        <label class="check-label"><input type="checkbox" data-show-password> Tampilkan password</label>
        <button class="button wide" type="submit"><?= $isRegister ? 'Buat akun' : ($isChange ? 'Simpan password' : 'Masuk') ?> <?= icon('arrow') ?></button>
    </form>
    <?php if (!$isAdmin && !$isChange): ?><p class="auth-switch"><?= $isRegister ? 'Sudah punya akun?' : 'Belum punya akun?' ?> <a href="<?= e(url($isRegister ? 'pages/login.php' : 'pages/register.php')) ?>"><?= $isRegister ? 'Masuk' : 'Daftar sekarang' ?></a></p><?php if (!$isRegister): ?><a class="muted" href="<?= e(url('pages/email.php')) ?>">Lupa password?</a><?php endif; ?><?php endif; ?>
    <?php endif; ?></div></section>
</main></body></html>
