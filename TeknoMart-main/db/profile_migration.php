<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/bootstrap.php';
db('CREATE TABLE IF NOT EXISTS user_profiles (id_user INT NOT NULL PRIMARY KEY, photo VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
echo "Profile storage ready\n";
