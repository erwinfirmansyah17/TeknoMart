<?php
// Admin customer directory.

    $search=input('q'); $users=query('SELECT id_user,username,nama,email,telp,alamat FROM tb_user WHERE nama LIKE ? OR username LIKE ? OR email LIKE ? ORDER BY id_user DESC',["%$search%","%$search%","%$search%"]); ?>
    <section class="panel"><div class="table-toolbar"><form method="get" class="search"><?= icon('search') ?><input name="q" value="<?= e($search) ?>" aria-label="Cari pelanggan" placeholder="Cari nama, username, atau email…"><button>Cari</button></form><span class="muted"><?= count($users) ?> pelanggan</span></div><div class="table-scroll"><table><thead><tr><th>Pelanggan</th><th>Email</th><th>Telepon</th><th>Alamat</th></tr></thead><tbody><?php foreach($users as $u): ?><tr><td><strong><?= e($u['nama']) ?></strong><small class="table-subtitle">@<?= e($u['username']) ?></small></td><td><?= e($u['email']) ?></td><td><?= e($u['telp']?:'—') ?></td><td><?= e($u['alamat']) ?></td></tr><?php endforeach; if(!$users): ?><tr><td colspan="4">Tidak ada pelanggan ditemukan.</td></tr><?php endif; ?></tbody></table></div></section>
    <?php
