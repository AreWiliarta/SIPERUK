<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

header('Content-Type: application/json');

$room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$role = $_SESSION['role'];

if ($room_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT b.id, b.start_time as start, b.end_time as end, b.event_name as title, b.status, b.description, r.name as room_name, r.id as room_id, u.name as user_name 
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status IN ('APPROVED', 'PENDING') AND b.room_id = ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$room_id]);
$bookings = $stmt->fetchAll();

$events = []; // Initialize to prevent undefined variable error if no bookings exist

foreach ($bookings as $b) {
    $color = '#3b82f6'; // Biru statis
    
    $title = "[" . $b['room_name'] . "] " . $b['title'];
    $display_user = $b['user_name'];
    $display_desc = $b['description'];
    
    if ($b['status'] === 'PENDING') {
        if ($role === 'USER') {
            $title = "[" . $b['room_name'] . "] Pending";
            $display_user = "Disembunyikan";
            $display_desc = "";
            $color = '#f59e0b';
        } else {
            $title = "[Pending] [" . $b['room_name'] . "] " . $b['title'];
            $color = '#f59e0b';
        }
    }

    $events[] = [
        'id' => $b['id'],
        'title' => $title,
        'start' => $b['start'],
        'end' => $b['end'],
        'color' => $color,
        'extendedProps' => [
            'room_name' => $b['room_name'],
            'room_id' => $b['room_id'],
            'user_name' => $display_user,
            'description' => $display_desc,
            'event_name' => $b['title'] // the real event name for editing
        ]
    ];
}

echo json_encode($events);
