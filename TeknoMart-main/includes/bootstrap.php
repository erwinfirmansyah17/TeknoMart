<?php
// Shared application setup. Credentials can be overridden by the local server.
$requestedScript = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
$includesRoot = realpath(__DIR__);
$isPrivateInclude = $requestedScript && $includesRoot && strpos($requestedScript, $includesRoot . DIRECTORY_SEPARATOR) === 0;
$isPrivateMigration = $requestedScript && dirname($requestedScript) === dirname(__DIR__) . DIRECTORY_SEPARATOR . 'db';
if (PHP_SAPI !== 'cli' && ($isPrivateInclude || $isPrivateMigration)) {
    http_response_code(404);
    exit;
}
date_default_timezone_set('Asia/Jakarta');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '', getenv('DB_NAME') ?: 'tecp5312_elektronik', (int)(getenv('DB_PORT') ?: 3306));
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('TeknoMart database: ' . $exception->getMessage());
    http_response_code(503);
    exit('<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Toko sementara tidak tersedia</title><body><main><h1>Toko sementara tidak tersedia.</h1><p>Koneksi database belum dapat dihubungkan. Silakan coba beberapa saat lagi.</p></main></body></html>');
}

function db($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = '';
        foreach ($params as $value) $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}
function query($sql, $params = []) { return db($sql, $params)->get_result()->fetch_all(MYSQLI_ASSOC); }
function one($sql, $params = []) { return query($sql, $params)[0] ?? null; }
function e($value) { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function rupiah($value) { return 'Rp ' . number_format((float)($value ?? 0), 0, ',', '.'); }
function input($name, $default = '') { $v = $_POST[$name] ?? $_GET[$name] ?? $default; return is_scalar($v) ? trim((string)$v) : $default; }
function positive($value, $label = 'Jumlah') {
    if (!is_scalar($value) || !filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]])) throw new DomainException($label . ' harus berupa angka lebih dari nol.');
    return (int)$value;
}
function required($name, $max = 99) {
    $value = input($name);
    if ($value === '' || mb_strlen($value) > $max) throw new DomainException('Lengkapi ' . str_replace('_', ' ', $name) . ' (maksimal ' . $max . ' karakter).');
    return $value;
}
function url($path = '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base = dirname($script);
    if (in_array(basename($base), ['pages', 'bangjanganbang'])) $base = dirname($base);
    $base = str_replace('\\', '/', $base);
    return rtrim($base, '/.') . '/' . ltrim($path, '/');
}
function redirect($path) { header('Location: ' . url($path)); exit; }
function flash($message, $type = 'success') { $_SESSION['flash'] = [$message, $type]; }
function csrf() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function verify_csrf() {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) throw new DomainException('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
}
function current_user($admin = false) {
    $key = $admin ? 'id_admin' : 'id_user';
    $table = $admin ? 'tb_admin' : 'tb_user';
    return empty($_SESSION[$key]) ? null : one("SELECT * FROM $table WHERE $key = ?", [(int)$_SESSION[$key]]);
}
function require_user($admin = false) {
    $user = current_user($admin);
    if (!$user) { flash('Silakan masuk untuk melanjutkan.', 'info'); redirect($admin ? 'bangjanganbang/login_admin.php' : 'pages/login.php'); }
    return $user;
}
function authenticate($username, $password, $admin = false) {
    $table = $admin ? 'tb_admin' : 'tb_user';
    $key = $admin ? 'id_admin' : 'id_user';
    unset($_SESSION[$key]);
    $user = one("SELECT * FROM $table WHERE username = ? LIMIT 1", [trim($username)]);
    if (!$user || $password === '') return false;
    $stored = $user['password'];
    $legacy = password_get_info($stored)['algoName'] === 'unknown' && substr($stored, 0, 1) !== '$';
    if (!($legacy ? hash_equals($stored, $password) : password_verify($password, $stored))) return false;
    if ($legacy) db("UPDATE $table SET password = ? WHERE $key = ?", [password_hash($password, PASSWORD_BCRYPT), (int)$user[$key]]);
    session_regenerate_id(true);
    $_SESSION[$key] = (int)$user[$key];
    // Separate identities: admin and customer can use the same browser.
    $_SESSION[$admin ? 'admin_username' : 'username'] = $user['username'];
    return true;
}
function password_value($name = 'password') {
    $password = $_POST[$name] ?? '';
    if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) throw new DomainException('Password harus terdiri dari 8–72 karakter tanpa karakter null.');
    return $password;
}
function upload_image($file, $optional = false) {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($optional) return '';
        throw new DomainException('Pilih gambar terlebih dahulu.');
    }
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) throw new DomainException('Gambar gagal diunggah. Ukuran maksimal 5 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext || !getimagesize($file['tmp_name'])) throw new DomainException('Gunakan gambar JPG, PNG, atau WebP.');
    $path = 'upload/' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], dirname(__DIR__) . '/' . $path)) throw new DomainException('Gambar belum dapat disimpan. Silakan coba lagi.');
    return $path;
}
function owned_order($id, $userId, $lock = false) {
    $order = one('SELECT * FROM orders WHERE id_orders = ? AND id_user = ?' . ($lock ? ' FOR UPDATE' : ''), [(int)$id, (int)$userId]);
    if (!$order) throw new DomainException('Pesanan tidak ditemukan.');
    return $order;
}
function order_details($id) {
    return query('SELECT d.*, b.nama_barang, b.img FROM details d JOIN tb_barang b ON b.id_barang = d.id_barang WHERE d.id_orders = ?', [(int)$id]);
}
function report_dates() {
    $start = input('tanggal_awal', date('Y-m-01'));
    $end = input('tanggal_akhir', date('Y-m-d'));
    foreach ([$start, $end] as $date) {
        $parsed = DateTime::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new DomainException('Format tanggal tidak valid.');
    }
    if ($start > $end) throw new DomainException('Tanggal awal harus sebelum tanggal akhir.');
    return [$start, $end];
}
require_once __DIR__ . '/commerce.php';
require_once __DIR__ . '/pricing.php';
