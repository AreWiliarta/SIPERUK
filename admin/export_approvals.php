<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$status_filter = $_GET['status_filter'] ?? 'ALL_COMPLETED';

$where_clause = "MONTH(b.created_at) = ? AND YEAR(b.created_at) = ?";
$params = [$month, $year];

if ($status_filter === 'APPROVED') {
    $where_clause .= " AND b.status = 'APPROVED'";
} elseif ($status_filter === 'REJECTED') {
    $where_clause .= " AND b.status = 'REJECTED'";
} else {
    // ALL_COMPLETED means APPROVED or REJECTED
    $where_clause .= " AND b.status IN ('APPROVED', 'REJECTED')";
}

$stmt = $pdo->prepare("
    SELECT b.id, u.name as pemohon, u.email, r.name as ruangan, b.event_name, b.start_time, b.end_time, b.status, b.created_at, b.rejection_reason
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE $where_clause
    ORDER BY b.created_at ASC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$month_name = date('F', mktime(0, 0, 0, $month, 10));
$filename = "Laporan_Peminjaman_{$month_name}_{$year}.xls";

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');

?>
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr style="background-color: #f3f4f6; font-weight: bold;">
            <th>ID Pengajuan</th>
            <th>Nama Pemohon</th>
            <th>Email</th>
            <th>Ruangan</th>
            <th>Nama Kegiatan</th>
            <th>Waktu Mulai</th>
            <th>Waktu Selesai</th>
            <th>Status</th>
            <th>Tanggal Pengajuan</th>
            <th>Alasan Penolakan</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($bookings as $row): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['pemohon']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['ruangan']); ?></td>
            <td><?php echo htmlspecialchars($row['event_name']); ?></td>
            <td><?php echo $row['start_time']; ?></td>
            <td><?php echo $row['end_time']; ?></td>
            <td><?php echo $row['status'] === 'APPROVED' ? 'Disetujui' : 'Ditolak'; ?></td>
            <td><?php echo $row['created_at']; ?></td>
            <td><?php echo htmlspecialchars($row['rejection_reason'] ?? '-'); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php exit; ?>
