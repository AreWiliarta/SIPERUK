<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SIPERUK</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a href="index.php" class="navbar-brand">
            <img src="../assets/img/logo.png" alt="SIPERUK Logo" style="height: 130px; margin: -45px 0; object-fit: contain;">
        </a>
        <div class="nav-links">
            <?php $current = basename($_SERVER['PHP_SELF']); ?>
            <a href="index.php" class="nav-link <?php echo $current === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
            <a href="rooms.php" class="nav-link <?php echo $current === 'rooms.php' ? 'active' : ''; ?>">Ruangan</a>
            <a href="schedules.php" class="nav-link <?php echo ($current === 'schedules.php' || $current === 'calendar.php') ? 'active' : ''; ?>">Jadwal Ruangan</a>
            <a href="approvals.php" class="nav-link <?php echo $current === 'approvals.php' ? 'active' : ''; ?>">Persetujuan</a>
            <a href="#" onclick="confirmLogout(event)" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">Keluar</a>
        </div>
    </div>
</nav>

<script>
function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Keluar dari Sistem?',
        text: 'Apakah Anda yakin ingin keluar dari akun ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../logout.php';
        }
    });
}
</script>

<div class="container" style="padding-top: 32px; padding-bottom: 32px;">
