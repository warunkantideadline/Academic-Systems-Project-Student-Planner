<?php
// test_permission.php — hapus setelah dipakai
$path = __DIR__ . '/data/';
echo is_writable($path) ? 'WRITABLE ✅' : 'NOT WRITABLE ❌';
echo '<br>Path: ' . $path;
echo '<br>Exists: ' . (is_dir($path) ? 'YES' : 'NO');