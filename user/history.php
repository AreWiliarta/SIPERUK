<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    
    if ($action === 'cancel' && $booking_id > 0) {
        $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$booking_id, $_SESSION['user_id']]);
        $booking = $stmt->fetch();
        
        if ($booking && $booking['status'] === 'PENDING') {
            $update = $pdo->prepare("UPDATE bookings SET status = 'REJECTED', rejection_reason = 'Dibatalkan sendiri oleh pemohon.' WHERE id = ?");
            if ($update->execute([$booking_id])) {
                $msg = 'Pengajuan berhasil dibatalkan.';
            }
        } else {
            $error = 'Hanya pengajuan berstatus Menunggu yang dapat dibatalkan.';
        }
    }
}

// Pagination logic
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$count_stmt->execute([$_SESSION['user_id']]);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$stmt = $pdo->prepare("
    SELECT b.*, r.name as room_name 
    FROM bookings b 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>

<?php require_once 'header.php'; ?>

<div style="margin-bottom: 24px;">
    <h2>Riwayat Pengajuan Saya</h2>
    <p>Pantau status persetujuan jadwal yang telah Anda ajukan.</p>
</div>

<?php if ($msg): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?php echo h($msg); ?>', timer: 3000 });
    });
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({ icon: 'error', title: 'Gagal', text: '<?php echo h($error); ?>' });
    });
</script>
<?php endif; ?>

<div class="grid grid-cols-1">
    <?php foreach ($bookings as $b): ?>
    <div class="glass-card">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <h3 style="margin-bottom: 4px;"><?php echo h($b['event_name']); ?></h3>
                <p style="font-size: 14px; margin-bottom: 16px;">
                    Diajukan pada: <?php echo formatDate($b['created_at']); ?>
                </p>
            </div>
            <div>
                <?php if ($b['status'] === 'PENDING'): ?>
                    <span class="badge badge-pending">Menunggu Persetujuan</span>
                <?php elseif ($b['status'] === 'APPROVED'): ?>
                    <span class="badge badge-approved">Disetujui</span>
                <?php else: ?>
                    <span class="badge badge-rejected">Ditolak / Batal</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="background: rgba(255,255,255,0.5); padding: 16px; border-radius: var(--radius-md); margin-bottom: 16px;">
            <div class="grid grid-cols-2">
                <div>
                    <div style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 600;">Ruangan</div>
                    <div style="font-weight: 500;"><?php echo h($b['room_name']); ?></div>
                </div>
                <div>
                    <div style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 600;">Waktu Kegiatan</div>
                    <div style="font-weight: 500;">
                        <?php echo formatDate($b['start_time']); ?> <br>
                        <span style="color: var(--text-muted); font-size: 14px;">s/d <?php echo formatDate($b['end_time']); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($b['description']): ?>
            <div style="margin-top: 16px;">
                <div style="color: var(--text-muted); font-size: 12px; text-transform: uppercase; font-weight: 600;">Deskripsi Kegiatan</div>
                <div style="font-size: 14px;"><?php echo nl2br(h($b['description'])); ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($b['status'] === 'REJECTED' && $b['rejection_reason']): ?>
            <div style="margin-top: 16px; padding: 12px; background: #fee2e2; border-radius: 8px;">
                <div style="color: #991b1b; font-size: 12px; font-weight: 600;">ALASAN PENOLAKAN:</div>
                <div style="font-size: 14px; color: #7f1d1d;"><?php echo h($b['rejection_reason']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($b['status'] === 'PENDING'): ?>
        <div style="display: flex; gap: 12px; justify-content: flex-end;">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                <button type="button" onclick="confirmCancel(this.form)" class="btn btn-danger">Batalkan Pengajuan</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <?php if (count($bookings) === 0): ?>
    <div class="glass-card" style="text-align: center; padding: 48px; color: var(--text-muted);">
        Anda belum pernah mengajukan peminjaman ruangan.
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php echo renderPagination($page, $total_pages); ?>

<script>
function confirmCancel(form) {
    Swal.fire({
        title: 'Batalkan Pengajuan?',
        text: 'Anda yakin ingin membatalkan pengajuan jadwal ini?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Ya, Batalkan!'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>
