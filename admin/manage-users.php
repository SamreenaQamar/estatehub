<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// =========================================================
// SECURITY: Admin-only access
// =========================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['user_type'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// CSRF token for all state-changing POST actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function verify_csrf() {
    global $csrf_token;
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        die('Invalid request. Please refresh the page and try again.');
    }
}

// =========================================================
// SCHEMA DETECTION (optional columns)
// =========================================================
$has_city = false;
$has_last_login = false;
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM users");
if ($colCheck) {
    while ($col = mysqli_fetch_assoc($colCheck)) {
        if ($col['Field'] === 'city') $has_city = true;
        if ($col['Field'] === 'last_login') $has_last_login = true;
    }
}

$has_properties_table = false;
$properties_seller_col = null;
$tblCheck = mysqli_query($conn, "SHOW TABLES LIKE 'properties'");
if ($tblCheck && mysqli_num_rows($tblCheck) > 0) {
    $has_properties_table = true;
    $pcCheck = mysqli_query($conn, "SHOW COLUMNS FROM properties");
    if ($pcCheck) {
        while ($col = mysqli_fetch_assoc($pcCheck)) {
            if (in_array($col['Field'], ['seller_id', 'user_id', 'agent_id'], true)) {
                $properties_seller_col = $col['Field'];
                break;
            }
        }
    }
}

// =========================================================
// ALLOWED VALUES
// =========================================================
$ALLOWED_ROLES = ['admin', 'seller', 'user'];
$ALLOWED_STATUSES = ['active', 'pending', 'blocked', 'deleted'];

function role_label($role) {
    if ($role === 'user') return 'Buyer';
    return ucfirst($role);
}
function role_class($role) {
    return $role === 'user' ? 'buyer' : $role;
}
function status_label($status) {
    $status = $status ?: 'active';
    return ucfirst($status);
}

$message = '';
$messageType = '';

// Pick up a flash message left by a previous redirect (see PRG pattern below)
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Helper: redirect back to this page (preserving filter/search/page) after a
// state-changing POST, carrying the result message via session flash.
// This avoids the "status doesn't seem to update" issue caused by resubmitted
// POST data / stale form state, and guarantees a fresh SELECT on reload.
function redirect_with_flash($msg, $type) {
    $_SESSION['flash_message'] = $msg;
    $_SESSION['flash_type'] = $type;
    $qs = $_GET;
    $url = 'manage-users.php' . (!empty($qs) ? '?' . http_build_query($qs) : '');
    header('Location: ' . $url);
    exit();
}

// =========================================================
// ADD / EDIT / STATUS ACTIONS (same logic as before)
// =========================================================
if (isset($_POST['add_user'])) {
    verify_csrf();
    $name      = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = in_array($_POST['user_type'], $ALLOWED_ROLES, true) ? $_POST['user_type'] : 'user';
    $status    = in_array($_POST['status'], ['active', 'pending', 'blocked'], true) ? $_POST['status'] : 'pending';
    $phone     = trim($_POST['phone']);
    $city      = trim($_POST['city'] ?? '');

    if ($name === '' || $email === '' || empty($_POST['password'])) {
        $message = "Full name, email and password are required.";
        $messageType = "error";
    } else {
        if ($has_city) {
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, user_type, status, phone, city, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, 'sssssss', $name, $email, $password, $user_type, $status, $phone, $city);
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, password, user_type, status, phone, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $password, $user_type, $status, $phone);
        }

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            redirect_with_flash("User added successfully!", "success");
        } else {
            $message = (mysqli_errno($conn) === 1062)
                ? "That email address is already registered."
                : "Could not add user. Please try again.";
            $messageType = "error";
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST['edit_user'])) {
    verify_csrf();
    $id        = (int)$_POST['user_id'];
    $name      = trim($_POST['full_name']);
    $user_type = in_array($_POST['user_type'], $ALLOWED_ROLES, true) ? $_POST['user_type'] : 'user';
    $status    = in_array($_POST['status'], $ALLOWED_STATUSES, true) ? $_POST['status'] : 'active';
    $phone     = trim($_POST['phone']);
    $city      = trim($_POST['city'] ?? '');

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        if ($has_city) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, password=?, user_type=?, status=?, phone=?, city=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $password, $user_type, $status, $phone, $city, $id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, password=?, user_type=?, status=?, phone=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssi', $name, $password, $user_type, $status, $phone, $id);
        }
    } else {
        if ($has_city) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, user_type=?, status=?, phone=?, city=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssssi', $name, $user_type, $status, $phone, $city, $id);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, user_type=?, status=?, phone=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'ssssi', $name, $user_type, $status, $phone, $id);
        }
    }

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        redirect_with_flash("User updated successfully!", "success");
    } else {
        $message = "Could not update user. Please try again.";
        $messageType = "error";
        mysqli_stmt_close($stmt);
    }
}

if (isset($_POST['change_status'])) {
    verify_csrf();
    $id     = (int)$_POST['user_id'];
    $action = $_POST['action_type'] ?? '';

    $map = [
        'block'   => 'blocked',
        'unblock' => 'active',
        'approve' => 'active',
        'delete'  => 'deleted',
        'restore' => 'active',
    ];

    if (isset($map[$action]) && $id > 0) {
        if ($id === (int)$_SESSION['user_id'] && in_array($action, ['block', 'delete'], true)) {
            $message = "You cannot block or delete your own admin account.";
            $messageType = "error";
        } else {
            $new_status = $map[$action];
            $stmt = mysqli_prepare($conn, "UPDATE users SET status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'si', $new_status, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                $labels = [
                    'block' => 'blocked', 'unblock' => 'unblocked', 'approve' => 'approved',
                    'delete' => 'deleted (soft delete)', 'restore' => 'restored',
                ];
                redirect_with_flash("User has been " . $labels[$action] . " successfully.", "success");
            } else {
                $message = "Could not update user status.";
                $messageType = "error";
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// =========================================================
// PAGINATION & FILTER
// =========================================================
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$where  = [];
$params = [];
$types  = '';

if (in_array($filter, ['admin', 'seller', 'user'], true)) {
    $where[] = "user_type = ?";
    $params[] = $filter;
    $types .= 's';
    $where[] = "status != 'deleted'";
} elseif (in_array($filter, $ALLOWED_STATUSES, true)) {
    $where[] = "status = ?";
    $params[] = $filter;
    $types .= 's';
} else {
    $where[] = "status != 'deleted'";
}

if ($search !== '') {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}

$total_props_select = "";
if ($has_properties_table && $properties_seller_col) {
    $total_props_select = ", (SELECT COUNT(*) FROM properties p WHERE p.{$properties_seller_col} = users.id) AS total_properties";
}

// Count total for pagination
$count_sql = "SELECT COUNT(*) as total FROM users";
if ($where) {
    $count_sql .= " WHERE " . implode(' AND ', $where);
}
$count_stmt = mysqli_prepare($conn, $count_sql);
if ($types !== '') {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
mysqli_stmt_close($count_stmt);

// Pagination variables
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_rows / $limit);

// Main query with LIMIT
$query = "SELECT users.*" . $total_props_select . " FROM users";
if ($where) {
    $query .= " WHERE " . implode(' AND ', $where);
}
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conn, $query);
$types .= 'ii';
$params[] = $limit;
$params[] = $offset;
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);

// =========================================================
// DASHBOARD STATISTICS
// =========================================================
$user_stats = [
    'total' => 0, 'active' => 0, 'pending' => 0, 'blocked' => 0, 'deleted' => 0,
    'admin' => 0, 'seller' => 0, 'user' => 0,
];

$r = mysqli_query($conn, "SELECT status, COUNT(*) c FROM users GROUP BY status");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        $s = $row['status'] ?: 'active';
        if (isset($user_stats[$s])) $user_stats[$s] = (int)$row['c'];
    }
}
$user_stats['total'] = $user_stats['active'] + $user_stats['pending'] + $user_stats['blocked'];

$r = mysqli_query($conn, "SELECT user_type, COUNT(*) c FROM users WHERE status != 'deleted' GROUP BY user_type");
if ($r) {
    while ($row = mysqli_fetch_assoc($r)) {
        if (isset($user_stats[$row['user_type']])) $user_stats[$row['user_type']] = (int)$row['c'];
    }
}

$stats = [
    'users' => $user_stats['total'],
    'pending' => 0,
    'unread' => 0,
];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties WHERE status = 'Pending'");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['pending'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['unread'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

// Profile pic for topbar
$admin_id = $_SESSION['user_id'];
$profile_pic_path = '';
$upload_dir = "../uploads/profiles/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$found = glob($upload_dir . "admin_" . $admin_id . "_*");
if (!empty($found)) {
    $profile_pic_path = $found[0] . '?t=' . time();
}

// Avatar color palette (rotates per row, soft-background style like the reference design)
$AVATAR_PALETTE = [
    ['bg' => '#dcfce7', 'color' => '#0E7A4E'], // green
    ['bg' => '#dbeafe', 'color' => '#2563eb'], // blue
    ['bg' => '#ffedd5', 'color' => '#c2410c'], // orange
    ['bg' => '#fce7f3', 'color' => '#be185d'], // pink
    ['bg' => '#f1f5f9', 'color' => '#475569'], // grey
    ['bg' => '#ede9fe', 'color' => '#6d28d9'], // purple
];

$page_title = 'Manage Users';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EstateHub Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        /* ===== SIDEBAR - SAME AS ESTATEHUB THEME (UNCHANGED) ===== */
        .sidebar {
            width: 250px;
            min-width: 250px;
            background: #000000;
            padding: 24px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar::-webkit-scrollbar { width:4px; }
        .sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12); border-radius:10px; }

        .logo {
            padding:0 4px 20px;
            margin-bottom:20px;
            border-bottom:1px solid rgba(255,255,255,0.06);
        }
        .logo a {
            display:flex;
            align-items:center;
            gap:10px;
            text-decoration:none;
        }
        .logo-icon {
            font-size:30px;
            color:#0E7A4E;
            filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3));
        }
        .logo-text {
            font-size:29px;
            font-weight:800;
            letter-spacing:-0.5px;
            color:#ffffff;
            position:relative;
        }
        .logo-text::after {
            content:'';
            position:absolute;
            left:0;
            bottom:-10px;
            width:45px;
            height:3px;
            background:#0E7A4E;
            border-radius:10px;
        }

        .sidebar-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 8px 12px;
            margin-bottom: 6px;
        }
        .sidebar-menu { list-style:none; padding:0; margin-bottom:12px; }
        .sidebar-menu li { margin-bottom:6px; }

        .sidebar-menu a {
            display:flex;
            align-items:center;
            gap:12px;
            padding:12px 16px;
            width:fit-content;
            min-width:165px;    
            color:#b8c2cc;
            text-decoration:none;
            border-radius:12px;
            font-size:14px;
            font-weight:600;
            transition:all .3s ease;
        }

        .sidebar-menu a:hover {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            transform:translateX(6px);
            box-shadow:0 8px 20px rgba(14,122,78,.35);
        }

        .sidebar-menu a svg {
            width:20px;
            height:20px;
            transition:.3s;
        }

        .sidebar-menu a:hover svg {
            transform:scale(1.15);
        }

        .sidebar-menu a.active {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            box-shadow:0 8px 20px rgba(14,122,78,.35);
        }

        .sidebar-menu a.active svg {
            transform:scale(1.1);
        }

        .nav-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            border-radius: 50px;
            padding: 2px 10px;
            font-size: 10px;
            font-weight: 700;
            min-width: 22px;
            text-align: center;
        }
        .nav-badge.blue { background: #0E7A4E; }

        .logout-link {
            color:#b8c2cc !important;
        }
        .logout-link:hover {
            background:linear-gradient(135deg,#dc2626,#ef4444) !important;
            color:#fff !important;
            box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content { flex:1; overflow-y:auto; }
        .content-inner { padding:28px 32px 40px; }

        /* ===== TOP BAR - matches reference (notification bell + admin dropdown) ===== */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            padding: 14px 32px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top:0;
            z-index:50;
        }
        .topbar-menu-btn { display:none; background:none; border:none; cursor:pointer; padding:6px; margin-right:auto; }

        .topbar-actions { display:flex; align-items:center; gap:22px; }
        .icon-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition:0.2s;
            color:#334155;
        }
        .icon-btn:hover { background:#f1f5f9; }
        .icon-btn svg { width:22px; height:22px; stroke:currentColor; fill:none; stroke-width:2; }
        .icon-btn .count {
            position:absolute; top:-2px; right:-2px;
            background:#ef4444; color:#fff;
            font-size:10px; font-weight:700;
            min-width:18px; height:18px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            border:2px solid #fff;
        }
        .user-chip { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .user-avatar {
            width:38px; height:38px; border-radius:50%;
            background:#0E7A4E; color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:15px; overflow:hidden;
            flex-shrink:0;
        }
        .user-avatar img { width:100%; height:100%; object-fit:cover; }
        .user-info { display:flex; align-items:center; gap:4px; line-height:1.2; }
        .user-name { font-size:14px; font-weight:700; color:#0b1a2e; }
        .user-info i.fa-chevron-down { font-size:11px; color:#94a3b8; }

        /* ============================================================
           CONTENT AREA – MATCHED TO REFERENCE DESIGN
           ============================================================ */
        .page-header {
            margin-bottom:28px;
            position:relative;
        }
        .page-header h1 {
            font-size:28px;
            font-weight:800;
            color:#0b1a2e;
            letter-spacing:-0.3px;
        }
        .page-header .subtitle {
            font-size:14px;
            color:#64748b;
            margin-top:4px;
        }
        .page-header .header-actions {
            position:absolute;
            top:0;
            right:0;
        }

        /* Stats Grid – row 1: 5 small cards, row 2: 3 wide cards (matches reference) */
        .stats-grid-top {
            display:grid;
            grid-template-columns:repeat(5,1fr);
            gap:14px;
            margin-bottom:14px;
        }
        .stats-grid-bottom {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:14px;
            margin-bottom:24px;
        }
        .stat-card {
            background:#fff;
            border-radius:14px;
            padding:16px 18px;
            border:1px solid #edf2f7;
            transition:.3s ease;
            display:flex;
            align-items:center;
            gap:12px;
        }
        .stat-card:hover {
            transform:translateY(-4px);
            box-shadow:0 12px 25px rgba(14,122,78,.10);
        }
        .stat-card .number {
            font-size:22px;
            font-weight:800;
            color:#0b1a2e;
            line-height:1.2;
        }
        .stat-card .label {
            font-size:13px;
            color:#64748b;
            font-weight:500;
            margin-bottom:2px;
        }
        .stat-card .icon {
            font-size:17px;
            display:flex;
            align-items:center;
            justify-content:center;
            width:40px;
            height:40px;
            border-radius:10px;
            flex-shrink:0;
        }
        .stat-card .icon.green { background:#dcfce7; color:#0E7A4E; }
        .stat-card .icon.blue { background:#dbeafe; color:#2563eb; }
        .stat-card .icon.amber { background:#fef3c7; color:#d97706; }
        .stat-card .icon.red { background:#fee2e2; color:#dc2626; }
        .stat-card .icon.gray { background:#f1f5f9; color:#475569; }

        /* Filter Tabs (role filters) */
        .filter-tabs {
            display:flex;
            gap:8px;
            margin-bottom:20px;
            flex-wrap:wrap;
        }
        .filter-tab {
            padding:9px 22px;
            border-radius:30px;
            font-size:13px;
            font-weight:600;
            border:2px solid #e9ecef;
            background:white;
            cursor:pointer;
            transition:.3s;
            text-decoration:none;
            color:#475569;
        }
        .filter-tab:hover,
        .filter-tab.active {
            background:#0E7A4E;
            color:white;
            border-color:#0E7A4E;
        }

        /* Actions Bar */
        .actions-bar {
            display:flex;
            justify-content:flex-end;
            align-items:center;
            margin-bottom:20px;
            flex-wrap:wrap;
            gap:12px;
        }
        .search-box {
            display:flex;
            align-items:center;
            gap:10px;
            background:white;
            border:2px solid #e9ecef;
            border-radius:30px;
            padding:9px 16px 9px 18px;
        }
        .search-box input {
            border:none;
            outline:none;
            font-size:14px;
            width:280px;
            background:transparent;
            font-family:'Inter',sans-serif;
        }
        .search-box i { color:#94a3b8; }

        .btn {
            padding:10px 22px;
            border-radius:30px;
            font-size:13px;
            font-weight:700;
            border:none;
            cursor:pointer;
            transition:.3s;
            display:inline-flex;
            align-items:center;
            gap:8px;
            font-family:'Inter',sans-serif;
        }
        .btn-primary {
            background:#0E7A4E;
            color:white;
        }
        .btn-primary:hover {
            background:#0a5c3a;
            transform:translateY(-2px);
            box-shadow:0 8px 20px rgba(14,122,78,.3);
        }
        .btn-sm { padding:8px 18px; font-size:12px; }
        .btn-outline {
            background:white;
            color:#0E7A4E;
            border:2px solid #0E7A4E;
        }
        .btn-outline:hover { background:#EAF8F1; }
        .btn-success {
            background:#22c55e;
            color:white;
        }
        .btn-success:hover { background:#16a34a; }
        .btn-danger {
            background:#ef4444;
            color:white;
        }
        .btn-danger:hover { background:#dc2626; }
        .btn-warning {
            background:#f59e0b;
            color:white;
        }
        .btn-warning:hover { background:#d97706; }

        /* Table */
        .table-card {
            background:white;
            border-radius:18px;
            border:1px solid #edf2f7;
            overflow:hidden;
        }
        .table-header {
            display:grid;
            grid-template-columns:2fr 1.5fr 1fr 1fr 1fr 1.8fr;
            padding:14px 24px;
            background:#f8fafc;
            border-bottom:2px solid #edf2f7;
            font-size:11px;
            font-weight:700;
            color:#94a3b8;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }
        .table-row {
            display:grid;
            grid-template-columns:2fr 1.5fr 1fr 1fr 1fr 1.8fr;
            padding:16px 24px;
            border-bottom:1px solid #f1f5f9;
            align-items:center;
            transition:.3s;
        }
        .table-row:hover { background:#f8fafc; }
        .table-row:last-child { border-bottom:none; }

        /* User column with avatar */
        .user-cell {
            display:flex;
            align-items:center;
            gap:12px;
        }
        .user-avatar-sm {
            width:44px;
            height:44px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:15px;
            flex-shrink:0;
            overflow:hidden;
        }
        .user-avatar-sm img {
            width:100%; height:100%; object-fit:cover;
        }
        .user-name-id {
            display:flex;
            flex-direction:column;
        }
        .user-name-id .name {
            font-size:14px;
            font-weight:700;
            color:#0b1a2e;
        }
        .user-name-id .id {
            font-size:12px;
            color:#94a3b8;
        }

        /* Contact column */
        .contact-cell {
            display:flex;
            flex-direction:column;
            font-size:13px;
            color:#475569;
        }
        .contact-cell .email { font-weight:500; }
        .contact-cell .phone { font-size:12px; color:#94a3b8; }

        /* Role badges – simple dot + label style (matches reference) */
        .role-badge {
            display:inline-flex;
            align-items:center;
            gap:8px;
            font-size:13.5px;
            font-weight:600;
        }
        .role-badge::before {
            content:'';
            width:8px;
            height:8px;
            border-radius:50%;
            display:inline-block;
        }
        .role-badge.admin { color:#dc2626; }
        .role-badge.admin::before { background:#dc2626; }
        .role-badge.seller { color:#0E7A4E; }
        .role-badge.seller::before { background:#0E7A4E; }
        .role-badge.buyer { color:#2563eb; }
        .role-badge.buyer::before { background:#2563eb; }

        /* Status badges – simple dot + label style (matches reference) */
        .status-badge {
            display:inline-flex;
            align-items:center;
            gap:8px;
            font-size:13.5px;
            font-weight:600;
        }
        .status-badge::before {
            content:'';
            width:8px;
            height:8px;
            border-radius:50%;
            display:inline-block;
        }
        .status-badge.active { color:#15803d; }
        .status-badge.active::before { background:#22c55e; }
        .status-badge.pending { color:#b45309; }
        .status-badge.pending::before { background:#f59e0b; }
        .status-badge.blocked { color:#dc2626; }
        .status-badge.blocked::before { background:#dc2626; }
        .status-badge.deleted { color:#475569; }
        .status-badge.deleted::before { background:#94a3b8; }

        /* Joined */
        .joined-cell {
            font-size:13px;
            color:#0b1a2e;
            font-weight:600;
        }
        .joined-cell .time {
            font-size:12px;
            color:#94a3b8;
            font-weight:500;
            display:block;
            margin-top:2px;
        }

        /* Actions - circular icon buttons (matches reference design) */
        .actions-cell {
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        }
        .action-btn {
            width:36px;
            height:36px;
            border-radius:50%;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            border:1.5px solid #e2e8f0;
            background:#fff;
            cursor:pointer;
            transition:.2s;
            font-size:14px;
            color:#475569;
        }
        .action-btn:hover { transform:translateY(-2px); box-shadow:0 6px 14px rgba(0,0,0,.08); }
        .action-btn.view   { color:#334155; border-color:#e2e8f0; }
        .action-btn.view:hover   { background:#f1f5f9; }
        .action-btn.edit   { color:#d97706; border-color:#fde68a; }
        .action-btn.edit:hover   { background:#fffbeb; }
        .action-btn.block  { color:#dc2626; border-color:#fecaca; }
        .action-btn.block:hover  { background:#fef2f2; }
        .action-btn.delete { color:#475569; border-color:#e2e8f0; }
        .action-btn.delete:hover { background:#f1f5f9; }
        .action-btn.approve,
        .action-btn.unblock { color:#16a34a; border-color:#bbf7d0; }
        .action-btn.approve:hover,
        .action-btn.unblock:hover { background:#f0fdf4; }
        .action-btn.restore { color:#0E7A4E; border-color:#bbf7d0; }
        .action-btn.restore:hover { background:#f0fdf4; }

        /* Pagination */
        .pagination-wrapper {
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:16px 24px;
            background:#fff;
            border-top:1px solid #edf2f7;
            flex-wrap:wrap;
            gap:12px;
        }
        .pagination-info {
            font-size:13px;
            color:#64748b;
        }
        .pagination-links {
            display:flex;
            align-items:center;
            gap:6px;
        }
        .pagination-links a, .pagination-links span {
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-width:36px;
            height:36px;
            border-radius:8px;
            font-size:13px;
            font-weight:600;
            color:#475569;
            text-decoration:none;
            transition:.2s;
            border:1px solid transparent;
        }
        .pagination-links a:hover {
            background:#f1f5f9;
            border-color:#e9ecef;
        }
        .pagination-links .active {
            background:#0E7A4E;
            color:white;
            border-color:#0E7A4E;
        }
        .pagination-links .disabled {
            opacity:0.4;
            pointer-events:none;
        }

        /* Modals (unchanged functionality) */
        .modal-overlay {
            position:fixed;
            top:0; left:0; right:0; bottom:0;
            background:rgba(0,0,0,0.5);
            backdrop-filter:blur(4px);
            z-index:1000;
            display:none;
            align-items:center;
            justify-content:center;
        }
        .modal-overlay.active { display:flex; }
        .modal {
            background:white;
            border-radius:24px;
            padding:32px;
            max-width:560px;
            width:90%;
            max-height:90vh;
            overflow-y:auto;
            box-shadow:0 20px 60px rgba(0,0,0,0.2);
        }
        .modal.modal-sm { max-width:420px; text-align:center; }
        .modal h2 {
            font-size:22px;
            font-weight:800;
            margin-bottom:24px;
        }
        .form-group {
            margin-bottom:18px;
        }
        .form-group label {
            display:block;
            font-size:12px;
            font-weight:700;
            color:#0b1a2e;
            margin-bottom:6px;
            text-transform:uppercase;
            letter-spacing:0.3px;
        }
        .form-group input,
        .form-group select {
            width:100%;
            padding:11px 14px;
            border:2px solid #e9ecef;
            border-radius:12px;
            font-size:14px;
            transition:.3s;
            font-family:'Inter',sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline:none;
            border-color:#0E7A4E;
            box-shadow:0 0 0 4px rgba(14,122,78,.10);
        }
        .form-group input:disabled {
            background:#f8fafc;
            color:#94a3b8;
            cursor:not-allowed;
        }
        .form-row {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
        }
        .modal-actions {
            display:flex;
            gap:12px;
            justify-content:flex-end;
            margin-top:24px;
            padding-top:20px;
            border-top:2px solid #edf2f7;
        }

        .alert {
            padding:14px 20px;
            border-radius:14px;
            margin-bottom:20px;
            font-size:14px;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .alert-success { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
        .alert-error { background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; }

        .detail-row {
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:11px 0;
            border-bottom:1px solid #f1f5f9;
            font-size:14px;
        }
        .detail-row:last-child { border-bottom:none; }
        .detail-label { color:#94a3b8; font-weight:600; }
        .detail-value { color:#0b1a2e; font-weight:700; text-align:right; }

        .confirm-icon {
            width:56px; height:56px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            font-size:22px; margin:0 auto 16px;
        }
        .confirm-icon.warn { background:#fee2e2; color:#dc2626; }
        .confirm-icon.info { background:#dcfce7; color:#0E7A4E; }
        #confirmMessage { font-size:14px; color:#475569; margin-bottom:12px; }
        .confirm-warning {
            background:#fef3c7; color:#b45309;
            padding:12px 16px; border-radius:12px;
            font-size:12.5px; font-weight:500; text-align:left;
        }
        .modal-sm .modal-actions { justify-content:center; }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
            .stats-grid-top { grid-template-columns:repeat(2,1fr); }
            .stats-grid-bottom { grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:768px) {
            .content-inner { padding:16px; }
            .topbar { padding:12px 16px; flex-wrap:wrap; }
            .table-header, .table-row { grid-template-columns:1fr 1fr; gap:8px; }
            .stats-grid-top { grid-template-columns:1fr; }
            .stats-grid-bottom { grid-template-columns:1fr; }
            .form-row { grid-template-columns:1fr; }
            .page-header .header-actions { position:static; margin-top:12px; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR - UNCHANGED ===== -->
    <aside class="sidebar" id="adminSidebar">
        <div class="logo">
            <a href="../index.php">
                <i class="fas fa-home logo-icon"></i>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>

        <div class="sidebar-label">Main Menu</div>
        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                        <path d="M3 9h18M9 21V9"/>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="manage-users.php" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="5"/>
                        <path d="M20 21a8 8 0 1 0-16 0"/>
                    </svg>
                    Users
                    <span class="nav-badge"><?php echo $stats['users']; ?></span>
                </a>
            </li>
            <li>
                <a href="manage-properties.php" class="<?php echo ($current_page == 'manage-properties.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Properties
                    <?php if ($stats['pending'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['pending']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="manage-messages.php" class="<?php echo ($current_page == 'manage-messages.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages
                    <?php if ($stats['unread'] > 0): ?>
                        <span class="nav-badge blue"><?php echo $stats['unread']; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="sidebar-label">System</div>
        <ul class="sidebar-menu">
            <li>
                <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                        <line x1="2" y1="20" x2="22" y2="20"/>
                    </svg>
                    Reports
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Settings
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 3.5-6 8-6s8 2 8 6"/>
                    </svg>
                    Profile
                </a>
            </li>
            <li>
                <a href="../logout.php" class="logout-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Logout
                </a>
            </li>
        </ul>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">

        <!-- TOP BAR -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-actions">
                <button class="icon-btn" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="count"><?php echo $stats['unread'] > 0 ? $stats['unread'] : 5; ?></span>
                </button>
                <a href="profile.php" class="user-chip">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin User'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="user-avatar">
                        <?php if (!empty($profile_pic_path)): ?>
                            <img src="<?php echo $profile_pic_path; ?>" alt="Admin">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </header>

        <!-- ================================================================
             CONTENT AREA
             ================================================================ -->
        <div class="content-inner">

            <!-- Page Header -->
            <div class="page-header">
                <h1>Manage Users</h1>
                <div class="subtitle">View and manage all registered users</div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="openModal('addModal')">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'error' ? 'alert-error' : 'alert-success'; ?>">
                    <i class="fas <?php echo $messageType === 'error' ? 'fa-triangle-exclamation' : 'fa-check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards: Row 1 = 5 cards, Row 2 = 3 cards (matches reference) -->
            <div class="stats-grid-top">
                <div class="stat-card">
                    <span class="icon green"><i class="fas fa-users"></i></span>
                    <div>
                        <div class="label">Total Users</div>
                        <div class="number"><?php echo str_pad($user_stats['total'], 2, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="icon green"><i class="fas fa-user-check"></i></span>
                    <div>
                        <div class="label">Active Users</div>
                        <div class="number"><?php echo str_pad($user_stats['active'], 2, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="icon amber"><i class="fas fa-user-clock"></i></span>
                    <div>
                        <div class="label">Pending Users</div>
                        <div class="number"><?php echo str_pad($user_stats['pending'], 2, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="icon red"><i class="fas fa-user-shield"></i></span>
                    <div>
                        <div class="label">Blocked Users</div>
                        <div class="number"><?php echo str_pad($user_stats['blocked'], 2, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="icon gray"><i class="fas fa-trash-can"></i></span>
                    <div>
                        <div class="label">Deleted Users</div>
                        <div class="number"><?php echo str_pad($user_stats['deleted'], 2, '0', STR_PAD_LEFT); ?></div>
                    </div>
                </div>
            </div>

            <div class="stats-grid-bottom">
                <div class="stat-card">
                    <span class="icon red"><i class="fas fa-user-shield"></i></span>
                    <div>
                        <div class="label">Admins</div>
                        <div class="number"><?php echo $user_stats['admin']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="icon green"><i class="fas fa-store"></i></span>
                    <div>
                        <div class="label">Sellers</div>
                        <div class="number"><?php echo $user_stats['seller']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="icon blue"><i class="fas fa-user"></i></span>
                    <div>
                        <div class="label">Buyers</div>
                        <div class="number"><?php echo $user_stats['user']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs + Search (matches reference layout) -->
            <div class="actions-bar" style="justify-content:space-between;">
                <div class="filter-tabs" style="margin-bottom:0;">
                    <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All Users</a>
                    <a href="?filter=admin" class="filter-tab <?php echo $filter == 'admin' ? 'active' : ''; ?>">Admins</a>
                    <a href="?filter=seller" class="filter-tab <?php echo $filter == 'seller' ? 'active' : ''; ?>">Sellers</a>
                    <a href="?filter=user" class="filter-tab <?php echo $filter == 'user' ? 'active' : ''; ?>">Buyers</a>
                </div>

                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>"><?php endif; ?>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search by name, email or phone..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
            </div>

            <!-- Table -->
            <div class="table-card">
                <div class="table-header">
                    <div>User</div>
                    <div>Contact</div>
                    <div>Role</div>
                    <div>Status</div>
                    <div>Joined</div>
                    <div>Actions</div>
                </div>

                <?php if ($users && mysqli_num_rows($users) > 0): ?>
                    <?php $row_index = 0; ?>
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <?php
                            $status = $user['status'] ?: 'active';
                            $role   = in_array($user['user_type'], $ALLOWED_ROLES, true) ? $user['user_type'] : 'user';
                            $city_val = $has_city ? ($user['city'] ?? '') : '';
                            $last_login_val = $has_last_login ? ($user['last_login'] ?? '') : '';
                            $total_properties = $user['total_properties'] ?? 0;

                            $view_payload = [
                                'name' => $user['full_name'],
                                'email' => $user['email'],
                                'phone' => $user['phone'] ?? '',
                                'role' => role_label($role),
                                'status' => status_label($status),
                                'city' => $city_val !== '' ? $city_val : 'Not provided',
                                'joined' => date('M d, Y', strtotime($user['created_at'])),
                                'last_login' => $last_login_val ? date('M d, Y g:i A', strtotime($last_login_val)) : 'Never logged in',
                                'total_properties' => $role === 'seller' ? $total_properties : null,
                            ];

                            // Get initials for avatar
                            $name_parts = explode(' ', trim($user['full_name']));
                            $initials = '';
                            if (count($name_parts) >= 2) {
                                $initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
                            } else {
                                $initials = strtoupper(substr($user['full_name'], 0, 2));
                            }
                            // Check if profile_pic exists
                            $avatar_img = '';
                            if (!empty($user['profile_pic']) && file_exists("../uploads/profiles/" . $user['profile_pic'])) {
                                $avatar_img = "../uploads/profiles/" . $user['profile_pic'];
                            }

                            $palette = $AVATAR_PALETTE[$row_index % count($AVATAR_PALETTE)];
                            $row_index++;
                        ?>
                        <div class="table-row">
                            <!-- USER column -->
                            <div class="user-cell">
                                <div class="user-avatar-sm" style="background:<?php echo $palette['bg']; ?>; color:<?php echo $palette['color']; ?>;">
                                    <?php if ($avatar_img): ?>
                                        <img src="<?php echo $avatar_img; ?>" alt="Avatar">
                                    <?php else: ?>
                                        <?php echo $initials; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="user-name-id">
                                    <span class="name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                    <span class="id">ID #<?php echo $user['id']; ?></span>
                                </div>
                            </div>

                            <!-- CONTACT column -->
                            <div class="contact-cell">
                                <span class="email"><?php echo htmlspecialchars($user['email']); ?></span>
                                <span class="phone"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span>
                            </div>

                            <!-- ROLE -->
                            <div>
                                <span class="role-badge <?php echo role_class($role); ?>">
                                    <?php echo role_label($role); ?>
                                </span>
                            </div>

                            <!-- STATUS -->
                            <div>
                                <span class="status-badge <?php echo $status; ?>">
                                    <?php echo status_label($status); ?>
                                </span>
                            </div>

                            <!-- JOINED -->
                            <div class="joined-cell">
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                <span class="time"><?php echo date('g:i A', strtotime($user['created_at'])); ?></span>
                            </div>

                            <!-- ACTIONS -->
                            <div class="actions-cell">
                                <!-- View -->
                                <button type="button" class="action-btn view" title="View"
                                    onclick='viewUser(<?php echo json_encode($view_payload); ?>)'>
                                    <i class="fas fa-eye"></i>
                                </button>

                                <?php if ($status !== 'deleted'): ?>
                                    <!-- Edit -->
                                    <button type="button" class="action-btn edit" title="Edit"
                                        onclick="editUser(
                                            <?php echo $user['id']; ?>,
                                            '<?php echo addslashes($user['full_name']); ?>',
                                            '<?php echo addslashes($user['email']); ?>',
                                            '<?php echo $role; ?>',
                                            '<?php echo $status; ?>',
                                            '<?php echo addslashes($user['phone'] ?? ''); ?>',
                                            '<?php echo addslashes($city_val); ?>'
                                        )">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($status === 'active'): ?>
                                    <!-- Block -->
                                    <button type="button" class="action-btn block" title="Block"
                                        onclick="openConfirm('block','<?php echo $user['id']; ?>','<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <!-- Delete -->
                                    <button type="button" class="action-btn delete" title="Delete"
                                        onclick="openConfirm('delete','<?php echo $user['id']; ?>','<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                <?php elseif ($status === 'blocked'): ?>
                                    <!-- Unblock -->
                                    <button type="button" class="action-btn unblock" title="Unblock"
                                        onclick="openConfirm('unblock','<?php echo $user['id']; ?>','<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <!-- Delete -->
                                    <button type="button" class="action-btn delete" title="Delete"
                                        onclick="openConfirm('delete','<?php echo $user['id']; ?>','<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                <?php elseif ($status === 'pending'): ?>
                                    <!-- Approve -->
                                    <button type="button" class="action-btn approve" title="Approve"
                                        onclick="openConfirm('approve','<?php echo $user['id']; ?>','<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <!-- Delete -->
                                    <button type="button" class="action-btn delete" title="Delete"
                                        onclick="openConfirm('delete','<?php echo $user['id']; ?>','<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                <?php elseif ($status === 'deleted'): ?>
                                    <!-- Restore -->
                                    <button type="button" class="action-btn restore" title="Restore"
                                        onclick="openConfirm('restore','<?php echo $user['id']; ?>','<?php echo addslashes($user['full_name']); ?>')">
                                        <i class="fas fa-rotate-left"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding:48px; text-align:center; color:#94a3b8;">No users found</div>
                <?php endif; ?>

                <!-- Pagination -->
                <?php if ($total_rows > 0): ?>
                <div class="pagination-wrapper">
                    <div class="pagination-info">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> users
                    </div>
                    <div class="pagination-links">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"><i class="fas fa-chevron-left"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                        <?php endif; ?>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        if ($start > 1) {
                            echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a>';
                            if ($start > 2) echo '<span>…</span>';
                        }
                        for ($i = $start; $i <= $end; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $i])) . '" class="' . $active . '">' . $i . '</a>';
                        }
                        if ($end < $total_pages) {
                            if ($end < $total_pages - 1) echo '<span>…</span>';
                            echo '<a href="?' . http_build_query(array_merge($_GET, ['page' => $total_pages])) . '">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"><i class="fas fa-chevron-right"></i></a>
                        <?php else: ?>
                            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div> <!-- /.content-inner -->
    </div> <!-- /.main-content -->
</div> <!-- /.dashboard-wrapper -->

<!-- ===== MODALS (unchanged) ===== -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h2>Add New User</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="user_type" required>
                        <option value="user">Buyer</option>
                        <option value="seller">Seller</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="active">Active</option>
                        <option value="pending" selected>Pending</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
                <div></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h2>Edit User</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="user_id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email (read-only)</label>
                    <input type="email" id="edit_email" disabled>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>New Password (optional)</label>
                    <input type="password" name="password">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="edit_phone">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" id="edit_city">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="user_type" id="edit_type" required>
                        <option value="user">Buyer</option>
                        <option value="seller">Seller</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="blocked">Blocked</option>
                        <option value="deleted">Deleted</option>
                    </select>
                </div>
                <div></div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <h2>User Details</h2>
        <div id="viewBody"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-outline" onclick="closeModal('viewModal')">Close</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="confirmModal">
    <div class="modal modal-sm">
        <div class="confirm-icon" id="confirmIcon"><i class="fas fa-triangle-exclamation"></i></div>
        <h2 id="confirmTitle" style="text-align:center; margin-bottom:12px;">Confirm Action</h2>
        <p id="confirmMessage"></p>
        <div class="confirm-warning" id="confirmWarning"></div>
        <form method="POST" id="confirmForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="change_status" value="1">
            <input type="hidden" name="action_type" id="confirmActionType">
            <input type="hidden" name="user_id" id="confirmUserId">
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('confirmModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="confirmSubmitBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function editUser(id, name, email, type, status, phone, city) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_city').value = city;
    openModal('editModal');
}

function viewUser(data) {
    var rows = [
        ['Full Name', data.name],
        ['Email', data.email],
        ['Phone', data.phone || 'Not provided'],
        ['Role', data.role],
        ['Status', data.status],
        ['City', data.city],
        ['Registration Date', data.joined],
        ['Last Login', data.last_login],
    ];
    if (data.total_properties !== null && data.total_properties !== undefined) {
        rows.push(['Total Properties', data.total_properties]);
    }
    var html = rows.map(function(r) {
        return '<div class="detail-row"><span class="detail-label">' + r[0] + '</span><span class="detail-value">' + r[1] + '</span></div>';
    }).join('');
    document.getElementById('viewBody').innerHTML = html;
    openModal('viewModal');
}

var CONFIRM_CONTENT = {
    block:   { title: 'Block User', icon: 'warn', iconClass: 'fa-ban', message: 'Are you sure you want to block this user?', warning: 'This user will not be able to login until unblocked.', btn: 'Block User', btnClass: 'btn-danger' },
    unblock: { title: 'Unblock User', icon: 'info', iconClass: 'fa-unlock', message: 'Are you sure you want to unblock this user?', warning: 'The user will regain full access immediately.', btn: 'Unblock User', btnClass: 'btn-success' },
    approve: { title: 'Approve User', icon: 'info', iconClass: 'fa-check', message: 'Approve this pending registration?', warning: 'The user will be able to log in and use their account right away.', btn: 'Approve User', btnClass: 'btn-success' },
    delete:  { title: 'Delete User', icon: 'warn', iconClass: 'fa-trash', message: 'Are you sure you want to delete this user?', warning: 'The account will be hidden but data will remain stored.', btn: 'Delete User', btnClass: 'btn-danger' },
    restore: { title: 'Restore User', icon: 'info', iconClass: 'fa-rotate-left', message: 'Restore this account?', warning: 'The user will regain access immediately.', btn: 'Restore User', btnClass: 'btn-primary' }
};

function openConfirm(action, id, name) {
    var c = CONFIRM_CONTENT[action];
    if (!c) return;
    document.getElementById('confirmIcon').className = 'confirm-icon ' + c.icon;
    document.getElementById('confirmIcon').innerHTML = '<i class="fas ' + c.iconClass + '"></i>';
    document.getElementById('confirmTitle').textContent = c.title;
    document.getElementById('confirmMessage').textContent = c.message + ' (' + name + ')';
    document.getElementById('confirmWarning').textContent = c.warning;
    document.getElementById('confirmActionType').value = action;
    document.getElementById('confirmUserId').value = id;
    var btn = document.getElementById('confirmSubmitBtn');
    btn.textContent = c.btn;
    btn.className = 'btn ' + c.btnClass;
    openModal('confirmModal');
}

document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>

</body>
</html>