<?php
require_once 'header.php';

// Pagination & Search settings
$search = trim(htmlspecialchars($_GET['search'] ?? ''));
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 9; // Show 9 rooms per page (3x3 grid)
$offset = ($page - 1) * $limit;

$where_clause = "status = 'ACTIVE'";
$params = [];

if ($search) {
    $where_clause .= " AND (name LIKE ? OR facilities LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

// Get Total Rows
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE $where_clause");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Paginated Rooms
$sql = "SELECT * FROM rooms WHERE $where_clause ORDER BY name ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
    <div>
        <h2>Katalog Ruangan</h2>
        <p>Pilih ruangan yang ingin Anda pinjam untuk kegiatan.</p>
    </div>
    
    <div style="width: 100%; max-width: 400px;">
        <form method="GET" action="" style="display: flex; gap: 8px;">
            <input type="text" name="search" class="form-control" placeholder="Cari nama ruangan, fasilitas, lokasi..." value="<?php echo h($search); ?>" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if($search): ?>
                <a href="index.php" class="btn btn-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="grid grid-cols-3">
    <?php foreach ($rooms as $room): ?>
    <div class="glass-card" style="display: flex; flex-direction: column; padding: 0; overflow: hidden;">
        
        <?php if(!empty($room['image'])): ?>
            <!-- Actual Room Image -->
            <div style="height: 180px; width: 100%; background-image: url('../<?php echo h($room['image']); ?>'); background-size: cover; background-position: center; position: relative;">
                <div style="position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.75); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; backdrop-filter: blur(4px);">
                    Kapasitas: <?php echo $room['capacity']; ?> Org
                </div>
            </div>
        <?php else: ?>
            <!-- Placeholder Image if no real image -->
            <div style="height: 180px; background-color: #f1f5f9; display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid var(--border-color);">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <div style="position: absolute; bottom: 12px; right: 12px; background: rgba(0,0,0,0.6); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                    Kapasitas: <?php echo $room['capacity']; ?> Org
                </div>
            </div>
        <?php endif; ?>
        
        <div style="padding: 20px; flex: 1; display: flex; flex-direction: column;">
            <h3 style="margin-bottom: 8px; font-size: 18px;"><?php echo h($room['name']); ?></h3>
            <p style="font-size: 13px; margin-bottom: 16px; flex: 1;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: -2px; margin-right: 4px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                <?php echo h($room['location']); ?>
            </p>
            
            <div style="margin-bottom: 20px;">
                <strong style="font-size: 13px; display: block; margin-bottom: 4px;">Fasilitas:</strong>
                <div style="font-size: 13px; color: var(--text-muted);">
                    <?php echo nl2br(h($room['facilities'])); ?>
                </div>
            </div>
            
            <a href="book.php?room_id=<?php echo $room['id']; ?>" class="btn btn-primary" style="width: 100%;">Ajukan Peminjaman</a>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (count($rooms) === 0): ?>
    <div class="glass-card" style="grid-column: span 3; text-align: center; padding: 48px; color: var(--text-muted);">
        <?php if ($search): ?>
            <p>Tidak ada ruangan yang cocok dengan pencarian "<b><?php echo h($search); ?></b>".</p>
        <?php else: ?>
            <p>Tidak ada ruangan aktif saat ini.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php echo renderPagination($page, $total_pages, ['search' => $search]); ?>

<?php require_once 'footer.php'; ?>
