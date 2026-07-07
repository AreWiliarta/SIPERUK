<?php
require_once __DIR__ . '/includes/init.php';

// Turn off foreign key checks
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('TRUNCATE TABLE bookings');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

// Fetch all rooms
$stmt = $pdo->query("SELECT id, name FROM rooms WHERE status = 'ACTIVE'");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rooms) === 0) {
    die("Tidak ada ruangan aktif.");
}

// User ID for bookings (assuming Mahasiswa 1 has ID 2)
$user_id = 2;

$event_names_kelas = ['MK. Algoritma Dasar', 'MK. Basis Data', 'MK. Kalkulus', 'MK. Bahasa Inggris', 'MK. Jaringan Komputer', 'MK. Pemrograman Web', 'MK. Kewarganegaraan'];
$event_names_lab = ['Praktikum Jaringan', 'Praktikum Web', 'Praktikum Pemrograman Dasar', 'Sertifikasi Mikrotik', 'Workshop UI/UX'];
$event_names_aula = ['Seminar Nasional', 'Kuliah Umum', 'Rapat Koordinasi BEM', 'Penyambutan Maba', 'Latihan Teater'];

$statuses = ['APPROVED', 'APPROVED', 'APPROVED', 'APPROVED', 'PENDING', 'REJECTED'];

// Generate bookings for past 3 days and next 7 days
$start_date = strtotime('-3 days');
$end_date = strtotime('+7 days');

$total_inserted = 0;
$insert_stmt = $pdo->prepare("
    INSERT INTO bookings (user_id, room_id, start_time, end_time, event_name, description, status, rejection_reason) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

for ($current_ts = $start_date; $current_ts <= $end_date; $current_ts = strtotime('+1 day', $current_ts)) {
    // Skip Sunday
    if (date('N', $current_ts) == 7) continue;
    
    $date_str = date('Y-m-d', $current_ts);
    
    // Pick 20 random rooms to have bookings today
    $daily_rooms = $rooms;
    shuffle($daily_rooms);
    $selected_rooms = array_slice($daily_rooms, 0, 25);
    
    foreach ($selected_rooms as $room) {
        // Decide how many slots for this room today (1 to 3)
        $num_slots = rand(1, 3);
        $slots = [];
        
        // Available blocks: 08-10, 10-12, 13-15, 15-17
        $available_blocks = [
            ['08:00:00', '10:00:00'],
            ['10:30:00', '12:30:00'],
            ['13:00:00', '15:00:00'],
            ['15:30:00', '17:30:00']
        ];
        shuffle($available_blocks);
        $chosen_blocks = array_slice($available_blocks, 0, $num_slots);
        
        foreach ($chosen_blocks as $block) {
            $start_time = $date_str . ' ' . $block[0];
            $end_time = $date_str . ' ' . $block[1];
            
            // Determine event name based on room name
            if (strpos($room['name'], 'Lab') !== false) {
                $event_name = $event_names_lab[array_rand($event_names_lab)];
            } elseif (strpos($room['name'], 'Aula') !== false) {
                $event_name = $event_names_aula[array_rand($event_names_aula)];
            } else {
                $event_name = $event_names_kelas[array_rand($event_names_kelas)];
            }
            
            $status = $statuses[array_rand($statuses)];
            $rejection_reason = ($status === 'REJECTED') ? 'Ruangan sedang maintenance atau jadwal dosen bentrok.' : NULL;
            $description = "Kegiatan akademik rutin.";
            
            if ($insert_stmt->execute([$user_id, $room['id'], $start_time, $end_time, $event_name, $description, $status, $rejection_reason])) {
                $total_inserted++;
            }
        }
    }
}

echo "Berhasil memasukkan $total_inserted jadwal peminjaman ke database.\n";
