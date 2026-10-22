<?php
// Update profile details or photo.

            if ($action === 'delete_photo') {
                $currentPhoto = profile_photo($userId);
                if ($currentPhoto !== '') {
                    db('DELETE FROM user_profiles WHERE id_user = ?', [$userId]);
                    $photoPath = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $currentPhoto);
                    $uploadRoot = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'upload');
                    if ($photoPath && $uploadRoot && strpos($photoPath, $uploadRoot . DIRECTORY_SEPARATOR) === 0 && preg_match('/^upload\/[a-f0-9]{32}\.(jpg|png|webp)$/', $currentPhoto) && is_file($photoPath)) unlink($photoPath);
                }
                flash('Foto profil berhasil dihapus.'); redirect('pages/profile.php');
            }
            $name = required('nama'); $phone = required('telp',20); $address = required('alamat');
            $photo = upload_image($_FILES['profile_photo'] ?? null, true);
            db('UPDATE tb_user SET nama = ?, telp = ?, alamat = ? WHERE id_user = ?', [$name, $phone, $address, $userId]);
            if ($photo !== '') db('INSERT INTO user_profiles (id_user,photo) VALUES (?,?) ON DUPLICATE KEY UPDATE photo=VALUES(photo)', [$userId,$photo]);
            flash('Profil berhasil diperbarui.'); redirect('pages/profile.php');
        