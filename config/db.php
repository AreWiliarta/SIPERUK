<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // default XAMPP
define('DB_PASS', '936936'); // default XAMPP (kosong)
define('DB_NAME', 'siperuk');

try {
    // Membuat koneksi dengan PDO
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Mode error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Hasil berupa array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Mematikan emulasi prepare untuk keamanan ekstra
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>
