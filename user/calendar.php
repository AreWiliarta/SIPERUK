<?php
require_once __DIR__ . '/../includes/init.php';
requireLogin();

// Ambil list ruangan untuk dropdown filter
$stmt = $pdo->query("SELECT id, name FROM rooms WHERE status = 'ACTIVE' ORDER BY name ASC");
$rooms = $stmt->fetchAll();
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
    /* Memperbaiki tinggi baris kalender agar lebih proporsional */
    .fc-timegrid-slot { height: 40px; }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <div>
        <h2>Jadwal & Ketersediaan Ruangan</h2>
        <p>Periksa jadwal kegiatan dan pastikan ketersediaan ruangan sebelum Anda melakukan peminjaman.</p>
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

<div style="margin-bottom: 16px; font-size: 14px;">
    <span style="display: inline-block; width: 12px; height: 12px; background-color: #3b82f6; border-radius: 2px; margin-right: 4px;"></span> Telah Disetujui (Booked) &nbsp;&nbsp;
    <span style="display: inline-block; width: 12px; height: 12px; background-color: #f59e0b; border-radius: 2px; margin-right: 4px;"></span> Pending
</div>

<div class="glass-card" style="padding: 24px; position: relative;">
    <!-- Blocker overlay -->
    <div id="calendar-blocker" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 50; cursor: pointer;" onclick="showSelectRoomWarning()"></div>
    <div id="calendar" style="opacity: 0.5; pointer-events: none; transition: opacity 0.3s ease;"></div>
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
            text: 'Silakan pilih ruangan terlebih dahulu pada menu dropdown di kanan atas untuk melihat ketersediaan jadwal.',
            confirmButtonColor: '#2563eb'
        });
    }

    var calendar;
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'id',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            initialView: 'timeGridWeek', // Kotak-kotak dari Senin-Minggu ke bawah jamnya
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
                    successCallback([]);
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
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'Tutup'
                });
            }
        });
        
        calendar.render();
    });
</script>

<?php require_once 'footer.php'; ?>
