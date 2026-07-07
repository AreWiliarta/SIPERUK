<?php
require_once 'header.php';

// Get Stats
$stmt_pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'PENDING'");
$pending_count = $stmt_pending->fetchColumn();

// Rooms in use today (bookings that are APPROVED and overlap with today)
$today = date('Y-m-d');
$stmt_in_use = $pdo->prepare("
    SELECT COUNT(DISTINCT room_id) 
    FROM bookings 
    WHERE status = 'APPROVED' 
    AND DATE(start_time) <= ? 
    AND DATE(end_time) >= ?
");
$stmt_in_use->execute([$today, $today]);
$in_use_count = $stmt_in_use->fetchColumn();

$stmt_total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'ACTIVE'");
$total_rooms = $stmt_total_rooms->fetchColumn();

// Get recent pending requests
$stmt_recent = $pdo->query("
    SELECT b.*, u.name as user_name, r.name as room_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status = 'PENDING' 
    ORDER BY b.created_at ASC 
    LIMIT 5
");
$recent_pendings = $stmt_recent->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h2>Dashboard Admin</h2>
        <p>Selamat datang, <?php echo h($_SESSION['name']); ?>!</p>
    </div>
</div>

<div class="grid grid-cols-3">
    <div class="glass-card" style="border-left: 4px solid var(--warning);">
        <h3 style="margin-bottom: 8px; color: var(--text-muted); font-size: 14px;">Menunggu Persetujuan</h3>
        <div style="font-size: 32px; font-weight: 700; color: var(--text-main);"><?php echo $pending_count; ?></div>
    </div>
    
    <div class="glass-card" style="border-left: 4px solid var(--primary-color);">
        <h3 style="margin-bottom: 8px; color: var(--text-muted); font-size: 14px;">Ruangan Terpakai Hari Ini</h3>
        <div style="font-size: 32px; font-weight: 700; color: var(--text-main);"><?php echo $in_use_count; ?> <span style="font-size: 16px; color: var(--text-muted); font-weight: 500;">/ <?php echo $total_rooms; ?></span></div>
    </div>
    
    <div class="glass-card" style="border-left: 4px solid var(--success);">
        <h3 style="margin-bottom: 8px; color: var(--text-muted); font-size: 14px;">Total Ruangan Aktif</h3>
        <div style="font-size: 32px; font-weight: 700; color: var(--text-main);"><?php echo $total_rooms; ?></div>
    </div>
</div>

<div class="glass-card" style="margin-top: 32px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h3>Pengajuan Membutuhkan Tindakan</h3>
        <a href="approvals.php" class="btn btn-secondary">Lihat Semua</a>
    </div>
    
    <?php if (count($recent_pendings) > 0): ?>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px; color: var(--text-muted); font-weight: 600; font-size: 14px;">Pemohon</th>
                    <th style="padding: 12px; color: var(--text-muted); font-weight: 600; font-size: 14px;">Kegiatan</th>
                    <th style="padding: 12px; color: var(--text-muted); font-weight: 600; font-size: 14px;">Ruangan</th>
                    <th style="padding: 12px; color: var(--text-muted); font-weight: 600; font-size: 14px;">Waktu</th>
                    <th style="padding: 12px; color: var(--text-muted); font-weight: 600; font-size: 14px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_pendings as $booking): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px; font-size: 14px;">
                        <b><?php echo h($booking['user_name']); ?></b><br>
                        <span style="color: var(--text-muted); font-size: 12px;"><?php echo formatDate($booking['created_at']); ?></span>
                    </td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo h($booking['event_name']); ?></td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo h($booking['room_name']); ?></td>
                    <td style="padding: 12px; font-size: 14px;">
                        <?php echo formatDate($booking['start_time']); ?> <br> 
                        <span style="color: var(--text-muted); font-size: 12px;">s/d <?php echo formatDate($booking['end_time']); ?></span>
                    </td>
                    <td style="padding: 12px;">
                        <a href="approvals.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px;">Tinjau</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--text-muted); padding: 24px 0;">Tidak ada pengajuan yang menunggu persetujuan.</p>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
