<?php
// Cek apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Cek apakah user adalah admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
}

// Wajib login, jika tidak redirect ke halaman login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../index.php"); // Asumsi dipanggil dari dalam folder /admin/ atau /user/
        exit;
    }
}

// Wajib admin, jika tidak redirect ke dashboard user
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../user/index.php");
        exit;
    }
}

// Escape HTML untuk mencegah XSS
function h($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Format Tanggal
function formatDate($datetime) {
    return date('d M Y, H:i', strtotime($datetime));
}

// ==========================================
// SECURITY (CSRF)
// ==========================================
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('Validasi keamanan form (CSRF Token) gagal! Silakan muat ulang halaman dan coba lagi.');
    }
}

// ==========================================
// BUSINESS LOGIC
// ==========================================
function check_booking_overlap($pdo, $room_id, $start_time, $end_time, $exclude_booking_id = 0) {
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE room_id = ? 
        AND status IN ('PENDING', 'APPROVED')
        AND id != ?
        AND start_time < ? 
        AND end_time > ?
    ");
    $stmt->execute([$room_id, $exclude_booking_id, $end_time, $start_time]);
    return $stmt->rowCount() > 0;
}

function handleImageUpload($fileArr) {
    if (isset($fileArr) && $fileArr['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($fileArr['error'] !== UPLOAD_ERR_OK) {
            return 'error_upload_' . $fileArr['error'];
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        // Fallback check extension if mime type is empty or weird
        $ext = strtolower(pathinfo($fileArr['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (!in_array($fileArr['type'], $allowedTypes) && !in_array($ext, $allowedExts)) {
            return 'error_type';
        }
        
        if ($fileArr['size'] > 5 * 1024 * 1024) { // Max 5MB
            return 'error_size';
        }
        
        $filename = uniqid('img_') . '.' . $ext;
        $dest = __DIR__ . '/../uploads/rooms/' . $filename;
        
        if (move_uploaded_file($fileArr['tmp_name'], $dest)) {
            return 'uploads/rooms/' . $filename;
        } else {
            return 'error_move';
        }
    }
    return null;
}

// ==========================================
// UI HELPERS
// ==========================================
function renderPagination($current_page, $total_pages, $query_params = []) {
    if ($total_pages <= 1) return '';
    $html = '<div style="display: flex; justify-content: center; gap: 8px; margin-top: 32px; flex-wrap: wrap; margin-bottom: 24px;">';
    $base_qs = '';
    foreach ($query_params as $key => $val) {
        if ($val !== '' && $val !== null) $base_qs .= urlencode($key) . '=' . urlencode($val) . '&';
    }
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    if ($current_page > 1) $html .= '<a href="?'.$base_qs.'page='.($current_page-1).'" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">&laquo; Prev</a>';
    if ($start > 1) {
        $html .= '<a href="?'.$base_qs.'page=1" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">1</a>';
        if ($start > 2) $html .= '<span style="padding: 6px 12px; font-size: 13px; color: var(--text-muted);">...</span>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $class = ($i === $current_page) ? 'btn-primary' : 'btn-secondary';
        $html .= '<a href="?'.$base_qs.'page='.$i.'" class="btn '.$class.'" style="padding: 6px 12px; font-size: 13px;">'.$i.'</a>';
    }
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) $html .= '<span style="padding: 6px 12px; font-size: 13px; color: var(--text-muted);">...</span>';
        $html .= '<a href="?'.$base_qs.'page='.$total_pages.'" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">'.$total_pages.'</a>';
    }
    if ($current_page < $total_pages) $html .= '<a href="?'.$base_qs.'page='.($current_page+1).'" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">Next &raquo;</a>';
    $html .= '</div>';
    return $html;
}
