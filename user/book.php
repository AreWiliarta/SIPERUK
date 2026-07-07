<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

$room_id = (int)($_GET['room_id'] ?? 0);
$msg = '';
$error = '';

// Ambil info ruangan
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND status = 'ACTIVE'");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$room) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    $event_name = trim(htmlspecialchars($_POST['event_name'] ?? ''));
    $description = trim(htmlspecialchars($_POST['description'] ?? ''));
    $start_date = $_POST['start_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if ($event_name && $start_date && $start_time && $end_date && $end_time) {
        $start_datetime_str = $start_date . ' ' . $start_time . ':00';
        $end_datetime_str = $end_date . ' ' . $end_time . ':00';
        
        $start_ts = strtotime($start_datetime_str);
        $end_ts = strtotime($end_datetime_str);
        $now_ts = time();
        
        // Validasi 1: End time harus > Start time
        if ($end_ts <= $start_ts) {
            $error = 'Waktu selesai harus lebih besar dari waktu mulai.';
        } else {
            // Validasi 2: H-2
            // H-2 berarti tanggal mulai minimal adalah (Hari Ini + 2 Hari) pada jam 00:00
            $h2_ts = strtotime(date('Y-m-d') . ' +2 days');
            if ($start_ts < $h2_ts) {
                $error = 'Pengajuan harus dilakukan maksimal H-2 sebelum pelaksanaan kegiatan.';
            } else {
                // Validasi 3: Double Booking menggunakan fungsi refactoring
                if (check_booking_overlap($pdo, $room_id, $start_datetime_str, $end_datetime_str)) {
                    $error = 'Jadwal bentrok! Ruangan sudah dipesan pada waktu tersebut (termasuk yang masih menunggu persetujuan). Silakan pilih waktu lain.';
                } else {
                    // Simpan
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO bookings (user_id, room_id, start_time, end_time, event_name, description)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    if ($insert_stmt->execute([$_SESSION['user_id'], $room_id, $start_datetime_str, $end_datetime_str, $event_name, $description])) {
                        $msg = 'Pengajuan berhasil dikirim dan sedang menunggu persetujuan Admin.';
                    } else {
                        $error = 'Terjadi kesalahan sistem.';
                    }
                }
            }
        }
    } else {
        $error = 'Semua field yang bertanda * wajib diisi.';
    }
}
$csrf_token = generate_csrf_token();
?>

<?php require_once 'header.php'; ?>

<div style="margin-bottom: 24px;">
    <h2>Formulir Pengajuan Peminjaman</h2>
    <p>Ruangan: <b><?php echo h($room['name']); ?></b></p>
</div>

<?php if ($msg): ?>
<div style="background-color: var(--success); color: white; padding: 12px; border-radius: var(--radius-md); margin-bottom: 24px;">
    <?php echo h($msg); ?>
    <br><br>
    <a href="history.php" class="btn btn-secondary">Lihat Riwayat Saya</a>
</div>
<?php elseif ($error): ?>
<div style="background-color: #fee2e2; color: #991b1b; padding: 12px; border-radius: var(--radius-md); margin-bottom: 24px;">
    <?php echo h($error); ?>
</div>
<?php endif; ?>

<?php if (!$msg): ?>
<div class="glass-card" style="max-width: 800px;">
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="form-group">
            <label class="form-label">Nama Kegiatan *</label>
            <input type="text" name="event_name" class="form-control" required placeholder="Contoh: Rapat Himpunan Mahasiswa" value="<?php echo h($_POST['event_name'] ?? ''); ?>">
        </div>
        
        <div class="grid grid-cols-2" style="margin-bottom: 0;">
            <div class="form-group">
                <label class="form-label">Tanggal Mulai *</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required value="<?php echo h($_POST['start_date'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Jam Mulai *</label>
                <input type="time" name="start_time" class="form-control" required value="<?php echo h($_POST['start_time'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Selesai *</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required value="<?php echo h($_POST['end_date'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Jam Selesai *</label>
                <input type="time" name="end_time" class="form-control" required value="<?php echo h($_POST['end_time'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Deskripsi Tambahan</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Tuliskan detail acara jika diperlukan..."><?php echo h($_POST['description'] ?? ''); ?></textarea>
        </div>
        
        <div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--warning); padding: 12px; margin-bottom: 24px; font-size: 14px;">
            <b>Catatan Penting:</b><br>
            1. Peminjaman harus dilakukan maksimal <b>H-2</b> sebelum pelaksanaan.<br>
            2. Slot waktu yang sudah diajukan akan dikunci (*pending booking*) agar tidak bentrok dengan pemohon lain.
        </div>
        
        <div style="display: flex; gap: 12px;">
            <button type="submit" class="btn btn-primary">Ajukan Peminjaman</button>
            <a href="index.php" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
</div>

<script>
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');

    // Set minimum date to H+2
    const today = new Date();
    const h2 = new Date(today);
    h2.setDate(h2.getDate() + 2);
    const minDateStr = h2.toISOString().split('T')[0];
    
    startDateInput.min = minDateStr;
    endDateInput.min = minDateStr;

    function validateDateTime() {
        // 1. End Date minimum is Start Date
        if (startDateInput.value) {
            endDateInput.min = startDateInput.value;
            if (endDateInput.value && endDateInput.value < startDateInput.value) {
                endDateInput.value = startDateInput.value;
            }
        }

        // 2. If same day, End Time minimum is Start Time
        if (startDateInput.value && endDateInput.value && startDateInput.value === endDateInput.value) {
            if (startTimeInput.value) {
                endTimeInput.min = startTimeInput.value;
                if (endTimeInput.value && endTimeInput.value <= startTimeInput.value) {
                    endTimeInput.value = '';
                }
            }
        } else {
            endTimeInput.removeAttribute('min');
        }
    }

    startDateInput.addEventListener('change', validateDateTime);
    endDateInput.addEventListener('change', validateDateTime);
    startTimeInput.addEventListener('change', validateDateTime);
</script>
<?php endif; ?>

<?php require_once 'footer.php'; ?>
