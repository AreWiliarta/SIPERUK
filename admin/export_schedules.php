<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

// Set header for Excel download
$filename = 'laporan_jadwal_siperuk_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Fetch data
$sql = "
    SELECT b.id, b.event_name, b.start_time, b.end_time,
           r.name as room_name,
           u.name as user_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'APPROVED'
    ORDER BY b.start_time DESC
";
$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color: #f3f4f6; font-weight: bold;">
            <th>ID Jadwal</th>
            <th>Nama Ruangan</th>
            <th>Nama Kegiatan</th>
            <th>Pemohon</th>
            <th>Waktu Mulai</th>
            <th>Waktu Selesai</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['room_name']); ?></td>
            <td><?php echo htmlspecialchars($row['event_name']); ?></td>
            <td><?php echo htmlspecialchars($row['user_name']); ?></td>
            <td><?php echo $row['start_time']; ?></td>
            <td><?php echo $row['end_time']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php exit; ?>
