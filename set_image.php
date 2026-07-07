<?php
require_once __DIR__ . '/includes/init.php';
$pdo->exec("UPDATE rooms SET image = 'uploads/rooms/kelas_default.jpg' WHERE name LIKE 'Ruang Kelas%'");
echo 'Success';
