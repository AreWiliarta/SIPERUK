<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    
    if ($action === 'update') {
        $room_id = (int)($_POST['room_id'] ?? 0);
        $start_date = $_POST['start_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $event_name = trim($_POST['event_name'] ?? '');
        
        $start_datetime_str = $start_date . ' ' . $start_time . ':00';
        $end_datetime_str = $end_date . ' ' . $end_time . ':00';
        
        $start_ts = strtotime($start_datetime_str);
        $end_ts = strtotime($end_datetime_str);
        
        if ($end_ts <= $start_ts) {
            $error = 'Waktu selesai harus lebih besar dari waktu mulai.';
        } else {
            // Cek bentrok (kecuali dengan dirinya sendiri)
            $overlap_stmt = $pdo->prepare("
                SELECT id FROM bookings 
                WHERE room_id = ? 
                AND status IN ('PENDING', 'APPROVED')
                AND id != ?
                AND start_time < ? 
                AND end_time > ?
            ");
            $overlap_stmt->execute([$room_id, $booking_id, $end_datetime_str, $start_datetime_str]);
            if ($overlap_stmt->rowCount() > 0) {
                $error = 'Jadwal bentrok! Ruangan sudah dipakai di jam tersebut.';
            } else {
                $update_stmt = $pdo->prepare("UPDATE bookings SET room_id=?, start_time=?, end_time=?, event_name=? WHERE id=?");
                if ($update_stmt->execute([$room_id, $start_datetime_str, $end_datetime_str, $event_name, $booking_id])) {
                    $msg = 'Jadwal berhasil diperbarui.';
                }
            }
        }
    } elseif ($action === 'cancel') {
        $reason = trim($_POST['rejection_reason'] ?? 'Dibatalkan paksa oleh Admin.');
        $cancel_stmt = $pdo->prepare("UPDATE bookings SET status = 'REJECTED', rejection_reason = ? WHERE id = ?");
        if ($cancel_stmt->execute([$reason, $booking_id])) {
            $msg = 'Jadwal berhasil dibatalkan.';
        }
    }
}


// Ambil list ruangan untuk dropdown filter dan form edit
$stmt = $pdo->query("SELECT id, name FROM rooms WHERE status = 'ACTIVE' ORDER BY name ASC");
$rooms = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>

<?php require_once 'header.php'; ?>

<!-- FullCalendar CSS & JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<style>
    .fc-event {
        cursor: pointer;
        border: none;
        border-radius: 4px;
        padding: 4px;
        font-size: 12px;
    }
    .fc-toolbar-title {
        font-size: 1.25rem !important;
        font-weight: 700;
        color: var(--text-main);
    }
    .fc-timegrid-slot { height: 40px; }

    /* Animasi Modal Edit */
    .modal-overlay {
        display: none;
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .modal-content {
        display: none;
        position: fixed; top: 50%; left: 50%; transform: translate(-50%, -60%);
        width: 90%; max-width: 600px;
        z-index: 10000;
        opacity: 0;
        transition: all 0.3s ease;
    }
    .modal-active .modal-overlay { display: block; opacity: 1; }
    .modal-active .modal-content { display: block; opacity: 1; transform: translate(-50%, -50%); }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div>
        <h2>Manajemen Jadwal Ruangan</h2>
        <p style="color: var(--text-muted); margin-top: 4px;">Lihat detail dan kelola jadwal ruangan terpakai secara langsung.</p>
    </div>
    
    <div>
        <select id="roomFilter" class="form-control" style="width: auto; min-width: 200px;" onchange="onRoomSelected()">
            <option value="" disabled selected>-- Pilih Ruangan Terlebih Dahulu --</option>
            <?php foreach ($rooms as $room): ?>
                <option value="<?php echo $room['id']; ?>"><?php echo h($room['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
    <a href="schedules.php" class="btn btn-secondary">Tampilan Tabel</a>
    <a href="calendar.php" class="btn btn-primary" style="pointer-events: none;">Tampilan Kalender</a>
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


<div style="margin-bottom: 16px; font-size: 14px;">
    <span style="display: inline-block; width: 12px; height: 12px; background-color: #3b82f6; border-radius: 2px; margin-right: 4px;"></span> Telah Disetujui (Booked) &nbsp;&nbsp;
    <span style="display: inline-block; width: 12px; height: 12px; background-color: #f59e0b; border-radius: 2px; margin-right: 4px;"></span> Pending
</div>

<div class="glass-card" style="padding: 24px; position: relative;">
    <!-- Blocker overlay -->
    <div id="calendar-blocker" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 50; cursor: pointer;" onclick="showSelectRoomWarning()"></div>
    <div id="calendar" style="opacity: 0.5; pointer-events: none; transition: opacity 0.3s ease;"></div>
</div>


<!-- Modal Edit Jadwal (Sama seperti di schedules.php) -->
<div id="modal-container">
    <div class="modal-overlay" onclick="closeEditModal()"></div>
    <div class="modal-content glass-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0;">Edit Jadwal</h3>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="booking_id" id="edit-id">
            
            <div class="form-group">
                <label class="form-label">Kegiatan</label>
                <input type="text" name="event_name" id="edit-event" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Ruangan</label>
                <select name="room_id" id="edit-room" class="form-control">
                    <?php foreach($rooms as $rm): ?>
                        <option value="<?php echo $rm['id']; ?>"><?php echo h($rm['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid grid-cols-2" style="margin-bottom: 0;">
                <div class="form-group">
                    <label class="form-label">Tgl Mulai</label>
                    <input type="date" name="start_date" id="edit-sd" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Mulai</label>
                    <input type="time" name="start_time" id="edit-st" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tgl Selesai</label>
                    <input type="date" name="end_date" id="edit-ed" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jam Selesai</label>
                    <input type="time" name="end_time" id="edit-et" class="form-control" required>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <button type="button" onclick="cancelBooking()" class="btn btn-danger">Batalkan Jadwal</button>
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Tutup</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
        
        <!-- Form Cancel Terpisah -->
        <form id="form-cancel" method="POST" style="display: none;">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="booking_id" id="cancel-id">
            <input type="hidden" name="rejection_reason" id="cancel-reason">
        </form>
    </div>
</div>


<script>
    function onRoomSelected() {
        var roomId = document.getElementById('roomFilter').value;
        if (roomId) {
            document.getElementById('calendar-blocker').style.display = 'none';
            document.getElementById('calendar').style.opacity = '1';
            document.getElementById('calendar').style.pointerEvents = 'auto';
            if (typeof calendar !== 'undefined') {
                calendar.refetchEvents();
            }
        }
    }

    function showSelectRoomWarning() {
        Swal.fire({
            icon: 'warning',
            title: 'Pilih Ruangan',
            text: 'Silakan pilih ruangan terlebih dahulu pada menu dropdown di kanan atas untuk melihat dan mengelola jadwal.',
            confirmButtonColor: '#2563eb'
        });
    }

    var calendar;
    
    // Format helper Date to YYYY-MM-DD
    function formatDateYMD(dateObj) {
        let month = '' + (dateObj.getMonth() + 1);
        let day = '' + dateObj.getDate();
        let year = dateObj.getFullYear();
        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;
        return [year, month, day].join('-');
    }
    
    // Format helper Date to HH:MM
    function formatTimeHM(dateObj) {
        let hours = '' + dateObj.getHours();
        let minutes = '' + dateObj.getMinutes();
        if (hours.length < 2) hours = '0' + hours;
        if (minutes.length < 2) minutes = '0' + minutes;
        return [hours, minutes].join(':');
    }

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'id',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            initialView: 'timeGridWeek', // Tabel kotak kotak Senin - Minggu
            allDaySlot: false,
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay,dayGridMonth'
            },
            dayHeaderContent: function(arg) {
                let days = ['MIN', 'SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB'];
                let dayName = days[arg.date.getDay()];
                
                if (arg.view.type === 'dayGridMonth') {
                    return {
                        html: `<div style="text-align: center; padding: 8px 0;">
                                   <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">${dayName}</div>
                               </div>`
                    };
                }
                
                let dateNum = arg.date.getDate();
                return {
                    html: `<div style="text-align: center; padding: 4px 0;">
                               <div style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">${dayName}</div>
                               <div style="font-size: 18px; font-weight: 700; color: var(--text-main); margin-top: 2px;">${dateNum}</div>
                           </div>`
                };
            },
            themeSystem: 'standard',
            height: 750,
            events: function(info, successCallback, failureCallback) {
                var roomId = document.getElementById('roomFilter').value;
                if (!roomId) {
                    successCallback([]); // Kembalikan array kosong jika belum milih ruangan
                    return;
                }
                var url = '../api/events.php?room_id=' + roomId;
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        successCallback(data);
                    })
                    .catch(error => {
                        console.error('Error fetching events:', error);
                        failureCallback(error);
                    });
            },
            eventClick: function(info) {
                var props = info.event.extendedProps;
                var startDate = info.event.start.toLocaleString('id-ID', {dateStyle: 'full', timeStyle: 'short'});
                var endDate = info.event.end ? info.event.end.toLocaleString('id-ID', {dateStyle: 'full', timeStyle: 'short'}) : 'N/A';
                
                // Pop-up Detail
                Swal.fire({
                    title: info.event.title,
                    html: `
                        <div style="text-align: left; margin-top: 16px; font-size: 14px; line-height: 1.6;">
                            <p style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 8px;"><b>Ruangan:</b> <br><span style="color:var(--primary-color); font-weight: 500;">${props.room_name}</span></p>
                            <p style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 8px;"><b>Waktu Pelaksanaan:</b> <br>${startDate} <br>s/d<br> ${endDate}</p>
                            <p style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 8px;"><b>Digunakan Oleh:</b> <br>${props.user_name}</p>
                            <p><b>Untuk Kegiatan / Deskripsi:</b> <br>${props.description || '-'}</p>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#10b981',
                    cancelButtonText: 'Edit Jadwal',
                    confirmButtonText: 'Tutup Detail'
                }).then((result) => {
                    // Jika Admin klik "Edit Jadwal"
                    if (result.dismiss === Swal.DismissReason.cancel) {
                        openEditModal(info.event);
                    }
                });
            }
        });
        
        calendar.render();
    });
    
    // Fungsi Edit Jadwal dari Kalender
    function openEditModal(eventObj) {
        var props = eventObj.extendedProps;
        
        document.getElementById('edit-id').value = eventObj.id;
        document.getElementById('edit-event').value = props.event_name;
        document.getElementById('edit-room').value = props.room_id;
        
        document.getElementById('edit-sd').value = formatDateYMD(eventObj.start);
        document.getElementById('edit-st').value = formatTimeHM(eventObj.start);
        
        if (eventObj.end) {
            document.getElementById('edit-ed').value = formatDateYMD(eventObj.end);
            document.getElementById('edit-et').value = formatTimeHM(eventObj.end);
        } else {
            document.getElementById('edit-ed').value = formatDateYMD(eventObj.start);
            document.getElementById('edit-et').value = formatTimeHM(eventObj.start);
        }
        
        document.getElementById('modal-container').classList.add('modal-active');
    }

    function closeEditModal() {
        document.getElementById('modal-container').classList.remove('modal-active');
    }

    function cancelBooking() {
        Swal.fire({
            title: 'Batalkan Jadwal?',
            text: 'Masukkan alasan pembatalan untuk dikirim ke user:',
            input: 'textarea',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonText: 'Batal',
            confirmButtonText: 'Ya, Batalkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('cancel-id').value = document.getElementById('edit-id').value;
                document.getElementById('cancel-reason').value = result.value || 'Dibatalkan oleh Admin';
                document.getElementById('form-cancel').submit();
            }
        });
    }
</script>

<?php require_once 'footer.php'; ?>
