<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    
    if ($action === 'update' && $booking_id > 0) {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        
        $start_datetime_str = $start_date . ' ' . $start_time . ':00';
        $end_datetime_str = $end_date . ' ' . $end_time . ':00';
        
        $start_ts = strtotime($start_datetime_str);
        $end_ts = strtotime($end_datetime_str);
        
        if ($end_ts <= $start_ts) {
            $error = 'Waktu selesai harus lebih besar dari waktu mulai.';
        } else {
            // Check double booking
            if (check_booking_overlap($pdo, $room_id, $start_datetime_str, $end_datetime_str, $booking_id)) {
                $error = 'Jadwal bentrok! Ruangan sudah terisi pada waktu tersebut.';
            } else {
                $stmt = $pdo->prepare("UPDATE bookings SET room_id=?, start_time=?, end_time=? WHERE id=?");
                if ($stmt->execute([$room_id, $start_datetime_str, $end_datetime_str, $booking_id])) {
                    $msg = 'Jadwal berhasil diperbarui.';
                }
            }
        }
    } elseif ($action === 'cancel' && $booking_id > 0) {
        $reason = trim(htmlspecialchars($_POST['reason'] ?? 'Dibatalkan oleh Admin.'));
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'REJECTED', rejection_reason = ? WHERE id = ?");
        if ($stmt->execute([$reason, $booking_id])) {
            $msg = 'Jadwal berhasil dibatalkan secara sepihak.';
        }
    }
}

// Pagination & Search
$search = trim(htmlspecialchars($_GET['search'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "b.status = 'APPROVED'";
$params = [];

if ($search) {
    $where_clause .= " AND (b.event_name LIKE ? OR r.name LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Get Total Rows
$count_sql = "SELECT COUNT(*) FROM bookings b JOIN rooms r ON b.room_id = r.id JOIN users u ON b.user_id = u.id WHERE $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Get Paged Data
$sql = "
    SELECT b.id, b.event_name, b.description, b.start_time, b.end_time, b.room_id,
           r.name as room_name,
           u.name as user_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN users u ON b.user_id = u.id
    WHERE $where_clause
    ORDER BY b.start_time DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// Get list of all rooms for the edit modal
$rooms_stmt = $pdo->query("SELECT id, name FROM rooms WHERE status != 'DELETED' ORDER BY name ASC");
$all_rooms = $rooms_stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>

<?php require_once 'header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div>
        <h2>Manajemen Jadwal Ruangan</h2>
        <p style="color: var(--text-muted); margin-top: 4px;">Lihat detail dan kelola jadwal ruangan terpakai secara langsung.</p>
    </div>
    <div>
        <a href="export_schedules.php" class="btn btn-success">Export</a>
    </div>
</div>

<div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
    <a href="schedules.php" class="btn btn-primary" style="pointer-events: none;">Tampilan Tabel</a>
    <a href="calendar.php" class="btn btn-secondary">Tampilan Kalender</a>
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

<!-- Form Pencarian -->
<div class="glass-card" style="margin-bottom: 24px; padding: 16px;">
    <form method="GET" action="" style="display: flex; gap: 12px; align-items: center;">
        <input type="text" name="search" class="form-control" placeholder="Cari kegiatan, ruangan, atau pemohon..." value="<?php echo h($search); ?>" style="flex: 1;">
        <button type="submit" class="btn btn-primary">Cari</button>
        <?php if($search): ?>
            <a href="schedules.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Daftar Jadwal Terpakai -->
<div class="glass-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px;">Waktu Kegiatan</th>
                    <th style="padding: 12px;">Ruangan</th>
                    <th style="padding: 12px;">Detail Kegiatan</th>
                    <th style="padding: 12px;">Pemohon</th>
                    <th style="padding: 12px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($schedules as $sched): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px; font-size: 14px;">
                        <div style="font-weight: 600; color: var(--primary-color);">
                            <?php echo date('d M Y', strtotime($sched['start_time'])); ?>
                        </div>
                        <div>
                            <?php echo date('H:i', strtotime($sched['start_time'])) . ' - ' . date('H:i', strtotime($sched['end_time'])); ?>
                        </div>
                    </td>
                    <td style="padding: 12px; font-weight: 500;"><?php echo h($sched['room_name']); ?></td>
                    <td style="padding: 12px;">
                        <div style="font-weight: 500; margin-bottom: 4px;"><?php echo h($sched['event_name']); ?></div>
                        <div style="font-size: 12px; color: var(--text-muted); max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo h($sched['description'] ?: 'Tidak ada deskripsi'); ?>
                        </div>
                    </td>
                    <td style="padding: 12px; font-size: 14px;"><?php echo h($sched['user_name']); ?></td>
                    <td style="padding: 12px;">
                        <button onclick="editSchedule(<?php echo htmlspecialchars(json_encode($sched), ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; margin-right: 4px;">Edit/Pindah</button>
                        <button onclick="cancelSchedule(<?php echo $sched['id']; ?>)" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">Batalkan</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (count($schedules) === 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 32px; color: var(--text-muted);">
                        Tidak ada jadwal terpakai yang ditemukan.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php echo renderPagination($page, $total_pages, ['search' => $search]); ?>
</div>

<!-- Modal / Form Edit Jadwal -->
<div id="modal-edit-container">
    <div class="modal-overlay" onclick="closeEditSchedule()" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998;" id="edit-overlay"></div>
    <div id="form-edit" class="glass-card" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 999; width: 90%; max-width: 500px;">
        <h3 style="margin-bottom: 16px;">Ubah Waktu / Ruangan</h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="booking_id" id="edit-booking-id">
            
            <div class="form-group">
                <label class="form-label">Ruangan</label>
                <select name="room_id" id="edit-room-id" class="form-control" required>
                    <?php foreach($all_rooms as $rm): ?>
                        <option value="<?php echo $rm['id']; ?>"><?php echo h($rm['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2" style="margin-bottom: 0;">
                <div class="form-group">
                    <label class="form-label">Tgl Mulai</label>
                    <input type="date" name="start_date" id="edit-start-date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="start_time" id="edit-start-time" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tgl Selesai</label>
                    <input type="date" name="end_date" id="edit-end-date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="end_time" id="edit-end-time" class="form-control" required>
                </div>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 16px;">
                <button type="button" onclick="closeEditSchedule()" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Batal Sepihak (Hidden) -->
<form id="form-cancel" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="cancel">
    <input type="hidden" name="booking_id" id="cancel-booking-id">
    <input type="hidden" name="reason" id="cancel-reason">
</form>

<script>
function editSchedule(sched) {
    document.getElementById('edit-booking-id').value = sched.id;
    document.getElementById('edit-room-id').value = sched.room_id;
    
    // Split datetime to date and time
    const startObj = new Date(sched.start_time);
    const endObj = new Date(sched.end_time);
    
    // Format YYYY-MM-DD (local)
    document.getElementById('edit-start-date').value = sched.start_time.split(' ')[0];
    document.getElementById('edit-end-date').value = sched.end_time.split(' ')[0];
    
    // Format HH:MM (local)
    document.getElementById('edit-start-time').value = sched.start_time.split(' ')[1].substring(0,5);
    document.getElementById('edit-end-time').value = sched.end_time.split(' ')[1].substring(0,5);
    
    document.getElementById('edit-overlay').style.display = 'block';
    document.getElementById('form-edit').style.display = 'block';
}

function closeEditSchedule() {
    document.getElementById('edit-overlay').style.display = 'none';
    document.getElementById('form-edit').style.display = 'none';
}

function cancelSchedule(booking_id) {
    Swal.fire({
        title: 'Batalkan Jadwal?',
        text: 'Masukkan alasan pembatalan sepihak untuk diinformasikan ke pemohon:',
        input: 'textarea',
        inputPlaceholder: 'Contoh: Ruangan akan digunakan untuk acara mendadak rektorat...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Batalkan Jadwal',
        cancelButtonText: 'Tutup',
        preConfirm: (text) => {
            if (!text) {
                Swal.showValidationMessage('Alasan harus diisi!')
            }
            return text;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('cancel-booking-id').value = booking_id;
            document.getElementById('cancel-reason').value = result.value;
            document.getElementById('form-cancel').submit();
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>
