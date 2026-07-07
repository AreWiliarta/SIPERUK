<?php
require 'config/db.php';

echo "Memulai proses penyisipan data dummy...\n";

// Tambahkan beberapa pengguna tambahan
$hash = password_hash('password123', PASSWORD_BCRYPT);
$usersData = [
    ['Dosen A', 'dosen_a@siperuk.com', 'USER'],
    ['Mahasiswa B', 'mhs_b@siperuk.com', 'USER'],
    ['Organisasi BEM', 'bem@siperuk.com', 'USER']
];

$userIds = [];
// Asumsikan admin id 1, user id 2 sudah ada dari setup awal.
$userIds[] = 2; 

foreach ($usersData as $u) {
    // Cek apakah sudah ada
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$u[1]]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$u[0], $u[1], $hash, $u[2]]);
        $userIds[] = $pdo->lastInsertId();
    }
}

// Tambahkan beberapa ruangan tambahan
$roomsData = [
    ['Laboratorium Komputer 1', 40, 'AC, 40 PC, Proyektor, Papan Tulis', 'Gedung F Lt. 2', 'ACTIVE'],
    ['Ruang Sidang Tesis', 15, 'AC, Proyektor Interaktif, Meja Bundar', 'Gedung Pascasarjana', 'ACTIVE'],
    ['Aula Terbuka', 200, 'Kipas Angin, Panggung, Sound System', 'Gedung Sayap Kiri', 'ACTIVE'],
    ['Ruang Kelas B2', 45, 'AC, Proyektor, Papan Tulis', 'Gedung B Lt. 1', 'MAINTENANCE'],
];

$roomIds = [];
// Coba kumpulkan ID ruangan yang sudah ada
$stmt = $pdo->query("SELECT id FROM rooms");
while ($row = $stmt->fetch()) {
    $roomIds[] = $row['id'];
}

foreach ($roomsData as $r) {
    $check = $pdo->prepare("SELECT id FROM rooms WHERE name = ?");
    $check->execute([$r[0]]);
    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO rooms (name, capacity, facilities, location, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$r[0], $r[1], $r[2], $r[3], $r[4]]);
        $roomIds[] = $pdo->lastInsertId();
    }
}

// Tambahkan data booking (Peminjaman)
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 days'));
$tomorrow = date('Y-m-d', strtotime('+1 days'));
$next_week = date('Y-m-d', strtotime('+5 days'));

$bookingsData = [
    // [user_id, room_id, start, end, event_name, status, desc, reject_reason]
    
    // Hari ini (APPROVED)
    [$userIds[0], $roomIds[0] ?? 1, "$today 09:00:00", "$today 12:00:00", "Rapat Koordinasi Mingguan", "APPROVED", "Rapat rutin", null],
    [$userIds[1] ?? 2, $roomIds[1] ?? 2, "$today 13:00:00", "$today 15:30:00", "Kuliah Pengganti RPL", "APPROVED", "", null],
    
    // Kemarin (APPROVED)
    [$userIds[2] ?? 2, $roomIds[2] ?? 3, "$yesterday 08:00:00", "$yesterday 16:00:00", "Seminar Nasional IT", "APPROVED", "Dihadiri 150 peserta", null],
    
    // Masa Depan (PENDING)
    [$userIds[0], $roomIds[0] ?? 1, "$next_week 10:00:00", "$next_week 12:00:00", "Diskusi Kelompok", "PENDING", "", null],
    [$userIds[1] ?? 2, $roomIds[2] ?? 3, "$next_week 13:00:00", "$next_week 17:00:00", "Latihan Teater Kampus", "PENDING", "Butuh akses panggung", null],
    
    // Masa Depan (REJECTED)
    [$userIds[2] ?? 2, $roomIds[1] ?? 2, "$tomorrow 09:00:00", "$tomorrow 11:00:00", "Rapat Mendadak", "REJECTED", "", "Pengajuan mendadak dan ada perbaikan proyektor."],
    
    // Masa Depan (APPROVED)
    [$userIds[0], $roomIds[0] ?? 1, "$tomorrow 14:00:00", "$tomorrow 16:00:00", "Pertemuan BEM", "APPROVED", "", null]
];

foreach ($bookingsData as $b) {
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, room_id, start_time, end_time, event_name, status, description, rejection_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7]]);
}

echo "Data dummy berhasil dimasukkan!\n";
