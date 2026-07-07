<?php
require_once __DIR__ . '/includes/init.php';

// Turn off foreign key checks to safely truncate
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
$pdo->exec('TRUNCATE TABLE bookings');
$pdo->exec('TRUNCATE TABLE rooms');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$rooms = [
    ['name' => 'Aula', 'capacity' => 200, 'facilities' => 'AC Sentral, Proyektor Utama, Sound System, Panggung, Kursi VIP', 'location' => 'Gedung Utama Lt. 1'],
    ['name' => 'Lab Networking', 'capacity' => 40, 'facilities' => 'AC, PC Workstation, Switch Cisco, Router MikroTik, Proyektor', 'location' => 'Gedung Lab Lt. 2'],
    ['name' => 'Lab Bisnis Intelegent', 'capacity' => 40, 'facilities' => 'AC, PC Dual Monitor, Proyektor, Papan Tulis Interaktif', 'location' => 'Gedung Lab Lt. 2'],
    ['name' => 'Lab Programing', 'capacity' => 40, 'facilities' => 'AC, PC High-End, Proyektor, Papan Tulis Kaca', 'location' => 'Gedung Lab Lt. 3'],
    ['name' => 'Lab Web', 'capacity' => 40, 'facilities' => 'AC, PC, Proyektor, Papan Tulis', 'location' => 'Gedung Lab Lt. 3'],
    ['name' => 'Lab Multimedia', 'capacity' => 40, 'facilities' => 'AC, Mac Studio, Pen Tablet, Green Screen, Kamera, Proyektor', 'location' => 'Gedung Lab Lt. 3'],
];

// Generate classrooms
for ($lantai = 1; $lantai <= 4; $lantai++) {
    for ($nomor = 1; $nomor <= 9; $nomor++) {
        $kode = $lantai . '.' . $nomor; // e.g. 1.1, 2.1
        $rooms[] = [
            'name' => "Ruang Kelas $kode",
            'capacity' => 40,
            'facilities' => 'AC, Proyektor, Papan Tulis, Kursi Mahasiswa',
            'location' => "Gedung Kelas Lt. $lantai"
        ];
    }
}

$stmt = $pdo->prepare("INSERT INTO rooms (name, capacity, facilities, location, status) VALUES (?, ?, ?, ?, 'ACTIVE')");

foreach ($rooms as $r) {
    $stmt->execute([$r['name'], $r['capacity'], $r['facilities'], $r['location']]);
}

echo "Berhasil memasukkan " . count($rooms) . " ruangan ke database.\n";
