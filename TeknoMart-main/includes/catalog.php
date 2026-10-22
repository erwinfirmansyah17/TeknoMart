<?php
// Compatibility router used by the existing index.php and pages/ entry points.
if ($route === 'home') {
    require __DIR__ . '/user/home.php';
} else {
    require __DIR__ . '/user/products.php';
}
