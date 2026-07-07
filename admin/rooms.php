<?php
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$msg = '';
$error = '';

// Handle Create / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim(htmlspecialchars($_POST['name'] ?? ''));
        $capacity = (int)($_POST['capacity'] ?? 0);
        $facilities = trim(htmlspecialchars($_POST['facilities'] ?? ''));
        $location = trim(htmlspecialchars($_POST['location'] ?? ''));
        $status = $_POST['status'] ?? 'ACTIVE';
        
        $imagePath = handleImageUpload($_FILES['room_image'] ?? null);
        
        if ($imagePath === 'error_type') {
            $error = "Gagal: Format gambar harus JPG, JPEG, PNG, atau WEBP.";
        } elseif ($imagePath === 'error_size') {
            $error = "Gagal: Ukuran gambar maksimal 5MB.";
        } elseif ($imagePath === 'error_move') {
            $error = "Gagal: Tidak dapat menyimpan gambar ke folder server (Permission denied).";
        } elseif ($imagePath === 'error_upload_1' || $imagePath === 'error_upload_2') {
            $error = "Gagal: File terlalu besar! Melebihi batas maksimal server PHP (biasanya 2MB). Silakan kompres foto Anda atau ubah 'upload_max_filesize' di php.ini.";
        } elseif (strpos($imagePath, 'error_upload_') === 0) {
            $error = "Gagal: Kesalahan saat mengunggah gambar (Kode error PHP: " . str_replace('error_upload_', '', $imagePath) . ").";
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (name, capacity, facilities, location, status, image) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $capacity, $facilities, $location, $status, $imagePath])) {
                $msg = 'Ruangan berhasil ditambahkan.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim(htmlspecialchars($_POST['name'] ?? ''));
        $capacity = (int)($_POST['capacity'] ?? 0);
        $facilities = trim(htmlspecialchars($_POST['facilities'] ?? ''));
        $location = trim(htmlspecialchars($_POST['location'] ?? ''));
        $status = $_POST['status'] ?? 'ACTIVE';
        
        $imagePath = handleImageUpload($_FILES['room_image'] ?? null);
        
        if ($imagePath === 'error_type') {
            $error = "Gagal: Format gambar harus JPG, JPEG, PNG, atau WEBP.";
        } elseif ($imagePath === 'error_size') {
            $error = "Gagal: Ukuran gambar maksimal 5MB.";
        } elseif ($imagePath === 'error_move') {
            $error = "Gagal: Tidak dapat menyimpan gambar ke folder server (Permission denied).";
        } elseif ($imagePath === 'error_upload_1' || $imagePath === 'error_upload_2') {
            $error = "Gagal: File terlalu besar! Melebihi batas maksimal server PHP (biasanya 2MB). Silakan kompres foto Anda atau ubah 'upload_max_filesize' di php.ini.";
        } elseif (strpos($imagePath, 'error_upload_') === 0) {
            $error = "Gagal: Kesalahan saat mengunggah gambar (Kode error PHP: " . str_replace('error_upload_', '', $imagePath) . ").";
        } else {
            if ($imagePath) {
                // Update dengan gambar baru
                $stmt = $pdo->prepare("UPDATE rooms SET name=?, capacity=?, facilities=?, location=?, status=?, image=? WHERE id=?");
                $exec = $stmt->execute([$name, $capacity, $facilities, $location, $status, $imagePath, $id]);
            } else {
                // Update tanpa mengubah gambar
                $stmt = $pdo->prepare("UPDATE rooms SET name=?, capacity=?, facilities=?, location=?, status=? WHERE id=?");
                $exec = $stmt->execute([$name, $capacity, $facilities, $location, $status, $id]);
            }
            
            if ($exec) {
                $msg = 'Ruangan berhasil diperbarui.';
                if ($status === 'MAINTENANCE' || $status === 'DELETED') {
                    $cancelStmt = $pdo->prepare("UPDATE bookings SET status = 'REJECTED', rejection_reason = 'Ruangan sedang dalam masa pemeliharaan atau tidak tersedia.' WHERE room_id = ? AND status = 'PENDING' AND start_time > NOW()");
                    $cancelStmt->execute([$id]);
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Soft delete
        $stmt = $pdo->prepare("UPDATE rooms SET status = 'DELETED' WHERE id = ?");
        if ($stmt->execute([$id])) {
            $msg = 'Ruangan berhasil dihapus secara sistem (Soft Delete).';
        }
    }
}

// Pagination & Search logic
$search = trim(htmlspecialchars($_GET['search'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clause = "status != 'DELETED'";
$params = [];

if ($search) {
    $where_clause .= " AND (name LIKE ? OR facilities LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE $where_clause");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$rooms = $stmt->fetchAll();
$csrf_token = generate_csrf_token();
?>

<?php require_once 'header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <h2 style="margin: 0;">Manajemen Ruangan</h2>
    
    <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
        <button onclick="document.getElementById('form-tambah').style.display = 'block';" class="btn btn-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Tambah Ruangan
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

<?php if ($error): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({ icon: 'error', title: 'Gagal', text: '<?php echo h($error); ?>' });
    });
</script>
<?php endif; ?>

<!-- Form Tambah -->
<div id="form-tambah" class="glass-card" style="display: none; margin-bottom: 32px;">
    <h3 style="margin-bottom: 16px;">Tambah Ruangan Baru</h3>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <div class="grid grid-cols-2">
            <div class="form-group">
                <label class="form-label">Nama Ruangan</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kapasitas (Orang)</label>
                <input type="number" name="capacity" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Lokasi</label>
                <input type="text" name="location" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="ACTIVE">Aktif</option>
                    <option value="MAINTENANCE">Dalam Perawatan (Maintenance)</option>
                </select>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Foto Ruangan (Maks 2MB, Opsional)</label>
                <input type="file" name="room_image" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp" style="padding: 8px;">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Fasilitas</label>
            <textarea name="facilities" class="form-control" rows="3" required></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button type="button" onclick="document.getElementById('form-tambah').style.display = 'none';" class="btn btn-secondary">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Ruangan</button>
        </div>
    </form>
</div>

<div style="margin-bottom: 24px; display: flex; justify-content: flex-end;">
    <form method="GET" action="" style="display: flex; gap: 8px;">
        <input type="text" name="search" class="form-control" placeholder="Cari ruangan, fasilitas..." value="<?php echo h($search); ?>" style="width: 250px; padding: 8px 12px;">
        <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">Cari</button>
        <?php if($search): ?>
            <a href="rooms.php" class="btn btn-secondary" style="padding: 8px 16px;">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Daftar Ruangan -->
<div class="glass-card">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <th style="padding: 12px; width: 60px;">Foto</th>
                    <th style="padding: 12px;">Nama Ruangan</th>
                    <th style="padding: 12px;">Kapasitas</th>
                    <th style="padding: 12px;">Lokasi</th>
                    <th style="padding: 12px;">Status</th>
                    <th style="padding: 12px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr style="border-bottom: 1px solid var(--border-color);">
                    <td style="padding: 12px;">
                        <?php if(!empty($room['image'])): ?>
                            <img src="../<?php echo h($room['image']); ?>" alt="Room" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;">
                        <?php else: ?>
                            <div style="width: 48px; height: 48px; background: #f1f5f9; border-radius: 4px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 12px; font-weight: 500;"><?php echo h($room['name']); ?></td>
                    <td style="padding: 12px;"><?php echo h($room['capacity']); ?> Org</td>
                    <td style="padding: 12px; color: var(--text-muted); font-size: 14px;"><?php echo h($room['location']); ?></td>
                    <td style="padding: 12px;">
                        <span class="badge <?php echo $room['status'] === 'ACTIVE' ? 'badge-approved' : 'badge-rejected'; ?>">
                            <?php echo $room['status'] === 'ACTIVE' ? 'Aktif' : 'Maintenance'; ?>
                        </span>
                    </td>
                    <td style="padding: 12px;">
                        <button onclick="editRoom(<?php echo htmlspecialchars(json_encode($room), ENT_QUOTES, 'UTF-8'); ?>)" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;">Edit</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php echo renderPagination($page, $total_pages, ['search' => $search]); ?>
</div>

<!-- Modal / Form Edit -->
<div id="modal-edit-container">
    <div class="modal-overlay" onclick="closeEditRoom()" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 998;" id="edit-overlay"></div>
    <div id="form-edit" class="glass-card" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 999; width: 90%; max-width: 600px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="margin: 0;">Edit Ruangan</h3>
            <button type="button" onclick="closeEditRoom()" style="background: none; border: none; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="id" id="edit-id">
            <div class="grid grid-cols-2">
                <div class="form-group">
                    <label class="form-label">Nama Ruangan</label>
                    <input type="text" name="name" id="edit-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kapasitas</label>
                    <input type="number" name="capacity" id="edit-capacity" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="location" id="edit-location" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit-status" class="form-control">
                        <option value="ACTIVE">Aktif</option>
                        <option value="MAINTENANCE">Dalam Perawatan (Maintenance)</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="form-label">Ganti Foto Ruangan (Biarkan kosong jika tidak ingin mengubah)</label>
                    <input type="file" name="room_image" class="form-control" accept="image/png, image/jpeg, image/jpg, image/webp" style="padding: 8px;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Fasilitas</label>
                <textarea name="facilities" id="edit-facilities" class="form-control" rows="3" required></textarea>
            </div>
            <div style="display: flex; justify-content: space-between; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <button type="button" onclick="deleteRoom()" class="btn btn-danger">Hapus Ruangan</button>
                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="closeEditRoom()" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form Hapus -->
<form id="form-delete" method="POST" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete-id">
</form>

<script>
function editRoom(room) {
    document.getElementById('edit-id').value = room.id;
    document.getElementById('edit-name').value = room.name;
    document.getElementById('edit-capacity').value = room.capacity;
    document.getElementById('edit-location').value = room.location;
    document.getElementById('edit-status').value = room.status;
    document.getElementById('edit-facilities').value = room.facilities;
    
    document.getElementById('edit-overlay').style.display = 'block';
    document.getElementById('form-edit').style.display = 'block';
}

function closeEditRoom() {
    document.getElementById('edit-overlay').style.display = 'none';
    document.getElementById('form-edit').style.display = 'none';
}

function deleteRoom() {
    Swal.fire({
        title: 'Hapus Ruangan?',
        text: 'Apakah Anda yakin ingin menghapus ruangan ini? Data peminjaman sebelumnya akan tetap tersimpan dengan aman (Soft Delete).',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-id').value = document.getElementById('edit-id').value;
            document.getElementById('form-delete').submit();
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>
