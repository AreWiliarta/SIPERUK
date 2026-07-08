<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($action === 'APPROVE') {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'APPROVED' WHERE id = ?");
        if ($stmt->execute([$booking_id])) {
            $msg = 'Pengajuan berhasil disetujui.';
        }
    } elseif ($action === 'REJECT') {
        $reason = trim(htmlspecialchars($_POST['rejection_reason'] ?? ''));
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'REJECTED', rejection_reason = ? WHERE id = ?");
        if ($stmt->execute([$reason, $booking_id])) {
            $msg = 'Pengajuan berhasil ditolak.';
        }
    }
}

// Ambil pengajuan berdasarkan filter status, default PENDING
$filter = $_GET['filter'] ?? 'PENDING';
if (!in_array($filter, ['PENDING', 'APPROVED', 'REJECTED'])) {
    $filter = 'PENDING';
}

// Pagination logic
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 3;
$offset = ($page - 1) * $limit;

// Get Total Rows
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE status = ?");
$count_stmt->execute([$filter]);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$stmt = $pdo->prepare("
    SELECT b.*, u.name as user_name, u.email, r.name as room_name 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN rooms r ON b.room_id = r.id 
    WHERE b.status = ?
    ORDER BY b.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute([$filter]);
$bookings = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>

<?php require_once 'header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <h2 style="margin: 0;">Panel Persetujuan</h2>
    
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <div style="display: flex; gap: 8px;">
            <a href="approvals.php?filter=PENDING" class="btn <?php echo $filter === 'PENDING' ? 'btn-primary' : 'btn-secondary'; ?>">Menunggu</a>
            <a href="approvals.php?filter=APPROVED" class="btn <?php echo $filter === 'APPROVED' ? 'btn-primary' : 'btn-secondary'; ?>">Disetujui</a>
            <a href="approvals.php?filter=REJECTED" class="btn <?php echo $filter === 'REJECTED' ? 'btn-primary' : 'btn-secondary'; ?>">Ditolak</a>
        </div>
        <div style="width: 1px; height: 24px; background: var(--border-color); margin: 0 4px;"></div>
        <button onclick="document.getElementById('modal-export').style.display = 'block';" class="btn btn-success" style="display: flex; align-items: center; gap: 6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export Laporan Bulanan
        </button>
    </div>
</div>

<?php if ($msg): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?php echo h($msg); ?>', timer: 3000 });
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
                    Diajukan oleh: <b><?php echo h($b['user_name']); ?></b> (<?php echo h($b['email']); ?>)<br>
                    <span style="color: var(--text-muted); font-size: 13px;">Tanggal Pengajuan: <?php echo formatDate($b['created_at']); ?></span>
                </p>
            </div>
            <div>
                <?php if ($b['status'] === 'PENDING'): ?>
                    <span class="badge badge-pending">Menunggu</span>
                <?php elseif ($b['status'] === 'APPROVED'): ?>
                    <span class="badge badge-approved">Disetujui</span>
                <?php else: ?>
                    <span class="badge badge-rejected">Ditolak</span>
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
            <button onclick="showRejectModal(<?php echo $b['id']; ?>)" class="btn btn-danger">Tolak Pengajuan</button>
            <form method="POST" action="" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                <input type="hidden" name="action" value="APPROVE">
                <button type="button" class="btn btn-success" onclick="confirmApprove(this.form)">Setujui Pengajuan</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <?php if (count($bookings) === 0): ?>
    <div class="glass-card" style="text-align: center; padding: 48px; color: var(--text-muted);">
        Tidak ada data pengajuan.
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php echo renderPagination($page, $total_pages, ['filter' => $filter]); ?>

<!-- Modal Tolak (Hidden) -->
<div id="modal-reject" class="glass-card" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 90%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
    <h3 style="margin-bottom: 16px; color: var(--danger);">Tolak Pengajuan</h3>
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <input type="hidden" name="action" value="REJECT">
        <input type="hidden" name="booking_id" id="reject-booking-id">
        <div class="form-group">
            <label class="form-label">Alasan Penolakan (Wajib Diisi)</label>
            <textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Tuliskan alasan penolakan..."></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
            <button type="button" onclick="document.getElementById('modal-reject').style.display = 'none';" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
        </div>
    </form>
</div>

<!-- Modal Export Laporan Bulanan -->
<div id="modal-export" class="glass-card" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1000; width: 90%; max-width: 400px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);">
    <h3 style="margin-bottom: 16px; color: var(--text-main);">Cetak Laporan Bulanan</h3>
    <form method="GET" action="export_approvals.php" target="_blank">
        <div class="form-group">
            <label class="form-label">Bulan</label>
            <select name="month" class="form-control">
                <?php
                $months = ['1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April', '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
                $curMonth = date('n');
                foreach($months as $num => $name) {
                    $sel = ($num == $curMonth) ? 'selected' : '';
                    echo "<option value=\"$num\" $sel>$name</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Tahun</label>
            <select name="year" class="form-control">
                <?php
                $curYear = date('Y');
                for($y = $curYear; $y >= $curYear - 2; $y--) {
                    echo "<option value=\"$y\">$y</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Status Pengajuan</label>
            <select name="status_filter" class="form-control">
                <option value="ALL_COMPLETED">Semua (Disetujui & Ditolak)</option>
                <option value="APPROVED">Hanya Disetujui</option>
                <option value="REJECTED">Hanya Ditolak</option>
            </select>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
            <button type="button" onclick="document.getElementById('modal-export').style.display = 'none';" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-success" onclick="document.getElementById('modal-export').style.display = 'none';">Download CSV</button>
        </div>
    </form>
</div>

<script>
function showRejectModal(id) {
    document.getElementById('reject-booking-id').value = id;
    document.getElementById('modal-reject').style.display = 'block';
}

function confirmApprove(form) {
    Swal.fire({
        title: 'Setujui Pengajuan?',
        text: 'Apakah Anda yakin ingin menyetujui peminjaman ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Ya, Setujui!'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>
