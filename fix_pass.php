<?php
require 'config/db.php';

// Generate hash for 'password123'
$hash = password_hash('password123', PASSWORD_BCRYPT);

// Update all users to have 'password123' as their password
$pdo->exec("UPDATE users SET password_hash = '$hash'");
echo "Passwords updated successfully!\n";
