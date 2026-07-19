<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$messageType = '';

if (isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $sql = "INSERT INTO users (full_name, email, password, user_type, status, phone, created_at) 
            VALUES ('$name', '$email', '$password', '$user_type', '$status', '$phone', NOW())";

    if (mysqli_query($conn, $sql)) {
        $message = "User added successfully!";
        $messageType = "success";
    }
}

if (isset($_POST['edit_user'])) {
    $id = (int)$_POST['user_id'];
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users 
                SET full_name = '$name', email = '$email', password = '$password', 
                    user_type = '$user_type', status = '$status', phone = '$phone' 
                WHERE id = $id";
    } else {
        $sql = "UPDATE users 
                SET full_name = '$name', email = '$email', user_type = '$user_type', 
                    status = '$status', phone = '$phone' 
                WHERE id = $id";
    }

    if (mysqli_query($conn, $sql)) {
        $message = "User updated successfully!";
        $messageType = "success";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id");
    header("Location: manage-users.php?msg=deleted");
    exit();
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $result = mysqli_query($conn, "SELECT status FROM users WHERE id = $id");
    $user = mysqli_fetch_assoc($result);
    $new_status = $user['status'] == 'active' ? 'blocked' : 'active';
    mysqli_query($conn, "UPDATE users SET status = '$new_status' WHERE id = $id");
    header("Location: manage-users.php");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT * FROM users WHERE 1 = 1";
if ($filter != 'all') {
    $query .= " AND user_type = '$filter'";
}
if ($search) {
    $query .= " AND (full_name LIKE '%$search%' OR email LIKE '%$search%')";
}
$query .= " ORDER BY created_at DESC";

$users = mysqli_query($conn, $query);
$total_count = mysqli_num_rows($users);

$user_stats = [
    'total' => 0,
    'active' => 0,
    'pending' => 0,
    'blocked' => 0,
    'admin' => 0,
    'seller' => 0,
    'user' => 0
];

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM users");
if ($result && mysqli_num_rows($result) > 0) {
    $user_stats['total'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE status = 'active'");
if ($result && mysqli_num_rows($result) > 0) {
    $user_stats['active'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE status = 'pending'");
if ($result && mysqli_num_rows($result) > 0) {
    $user_stats['pending'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE status = 'blocked'");
if ($result && mysqli_num_rows($result) > 0) {
    $user_stats['blocked'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$result = mysqli_query($conn, "SELECT user_type, COUNT(*) as c FROM users GROUP BY user_type");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        if (isset($user_stats[$row['user_type']])) {
            $user_stats[$row['user_type']] = $row['c'];
        }
    }
}

$stats = [
    'users' => $user_stats['total'],
    'pending' => 0,
    'unread' => 0
];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties WHERE status = 'Pending'");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['pending'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['unread'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

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

        /* ===== SIDEBAR - SAME AS SELLER ===== */
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

        /* ===== LOGO - SAME AS SELLER ===== */
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

        /* ===== SIDEBAR MENU ===== */
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

        /* ===== LOGOUT LINK ===== */
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

        /* ===== TOP BAR ===== */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 14px 32px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top:0;
            z-index:50;
        }
        .topbar-menu-btn { display:none; background:none; border:none; cursor:pointer; padding:6px; }
        .topbar-search { flex:1; max-width:500px; position:relative; }
        .topbar-search input {
            width:100%;
            padding:9px 40px 9px 16px;
            border-radius:10px;
            border:1px solid #e9ecef;
            background:#f8fafc;
            font-size:14px;
            outline:none;
            transition:0.2s;
        }
        .topbar-search input:focus { border-color:#0E7A4E; background:#fff; }
        .topbar-search svg { position:absolute; right:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#adb5bd; }

        .topbar-actions { display:flex; align-items:center; gap:16px; }
        .icon-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f8fafc;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition:0.2s;
            color:#1e293b;
        }
        .icon-btn:hover { background:#e9ecef; }
        .icon-btn svg { width:20px; height:20px; stroke:currentColor; fill:none; stroke-width:2; }
        .icon-btn .count {
            position:absolute; top:-4px; right:-4px;
            background:#ef4444; color:#fff;
            font-size:10px; font-weight:700;
            min-width:18px; height:18px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            border:2px solid #fff;
        }
        .user-chip { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .user-avatar {
            width:36px; height:36px; border-radius:10px;
            background:#0E7A4E; color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:15px; overflow:hidden;
            flex-shrink:0;
        }
        .user-avatar img { width:100%; height:100%; object-fit:cover; }
        .user-info { display:flex; flex-direction:column; line-height:1.2; }
        .user-name { font-size:14px; font-weight:700; color:#0b1a2e; }
        .user-role { font-size:12px; color:#6b7a8f; }

        /* ===== CONTENT ===== */
        .page-header {
            margin-bottom:28px;
        }
        .page-header h1 {
            font-size:24px;
            font-weight:800;
            color:#0b1a2e;
        }
        .page-header p {
            font-size:14px;
            color:#64748b;
            margin-top:4px;
        }

        .stats-grid {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:16px;
            margin-bottom:24px;
        }
        .stat-card {
            background:#fff;
            border-radius:16px;
            padding:20px;
            border:1px solid #edf2f7;
            transition:.3s ease;
        }
        .stat-card:hover {
            transform:translateY(-4px);
            box-shadow:0 12px 25px rgba(14,122,78,.10);
        }
        .stat-card .number {
            font-size:28px;
            font-weight:800;
            color:#0b1a2e;
            line-height:1;
        }
        .stat-card .label {
            font-size:13px;
            color:#64748b;
            margin-top:6px;
            font-weight:500;
        }
        .stat-card .sub {
            font-size:11px;
            color:#94a3b8;
            margin-top:4px;
        }
        .stat-card .icon {
            font-size:20px;
            margin-bottom:8px;
            display:inline-block;
            padding:8px;
            border-radius:10px;
        }
        .stat-card .icon.green { background:#dcfce7; color:#0E7A4E; }
        .stat-card .icon.blue { background:#dbeafe; color:#2563eb; }
        .stat-card .icon.amber { background:#fef3c7; color:#d97706; }
        .stat-card .icon.red { background:#fee2e2; color:#dc2626; }

        .filter-tabs {
            display:flex;
            gap:8px;
            margin-bottom:20px;
            flex-wrap:wrap;
        }
        .filter-tab {
            padding:8px 18px;
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

        .actions-bar {
            display:flex;
            justify-content:space-between;
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
            padding:8px 16px;
        }
        .search-box input {
            border:none;
            outline:none;
            font-size:14px;
            width:220px;
            background:transparent;
            font-family:'Inter',sans-serif;
        }

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
        .btn-sm { padding:6px 14px; font-size:12px; }
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

        .table-card {
            background:white;
            border-radius:18px;
            border:1px solid #edf2f7;
            overflow:hidden;
        }
        .table-header {
            display:grid;
            grid-template-columns:2fr 1.5fr 1fr 1fr 1fr 1.5fr;
            padding:16px 24px;
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
            grid-template-columns:2fr 1.5fr 1fr 1fr 1fr 1.5fr;
            padding:16px 24px;
            border-bottom:1px solid #f1f5f9;
            align-items:center;
            transition:.3s;
        }
        .table-row:hover { background:#f8fafc; }
        .table-row:last-child { border-bottom:none; }

        .user-info h4 {
            font-size:14px;
            font-weight:700;
            color:#0b1a2e;
        }
        .user-info span {
            font-size:12px;
            color:#94a3b8;
        }

        .status-badge {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:4px 12px;
            border-radius:30px;
            font-size:11px;
            font-weight:700;
        }
        .status-badge.active { background:#dcfce7; color:#15803d; }
        .status-badge.pending { background:#fef3c7; color:#b45309; }
        .status-badge.blocked { background:#fee2e2; color:#dc2626; }

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

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
            .stats-grid { grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:768px) {
            .content-inner { padding:16px; }
            .topbar { padding:12px 16px; flex-wrap:wrap; }
            .table-header, .table-row { grid-template-columns:1fr 1fr; gap:8px; }
            .stats-grid { grid-template-columns:1fr; }
            .form-row { grid-template-columns:1fr; }
            .user-info { display:none; }
            .topbar-search { max-width:none; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR - SAME AS SELLER ===== -->
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
                <a href="manage-users.php" class="<?php echo ($current_page == 'manage-users.php') ? 'active' : ''; ?>">
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

            <div class="topbar-search">
                <input type="text" placeholder="Search anything...">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            <div class="topbar-actions">
                <button class="icon-btn" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="count">3</span>
                </button>
                <a href="profile.php" class="user-chip">
                    <div class="user-avatar">
                        <?php if (!empty($profile_pic_path)): ?>
                            <img src="<?php echo $profile_pic_path; ?>" alt="Admin">
                        <?php else: ?>
                            <i class="fas fa-crown"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                        <span class="user-role">Super Admin</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="content-inner">

            <!-- Page Header -->
            <div class="page-header">
                <h1>User Management</h1>
                <p>Manage all registered users, their roles and account status</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="icon green"><i class="fas fa-users"></i></span>
                    <div class="number"><?php echo $user_stats['total']; ?></div>
                    <div class="label">Total Users</div>
                </div>
                <div class="stat-card">
                    <span class="icon green"><i class="fas fa-user-check"></i></span>
                    <div class="number"><?php echo $user_stats['active']; ?></div>
                    <div class="label">Active Users</div>
                </div>
                <div class="stat-card">
                    <span class="icon amber"><i class="fas fa-user-clock"></i></span>
                    <div class="number"><?php echo $user_stats['pending']; ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="stat-card">
                    <span class="icon red"><i class="fas fa-user-slash"></i></span>
                    <div class="number"><?php echo $user_stats['blocked']; ?></div>
                    <div class="label">Blocked</div>
                </div>
            </div>

            <!-- Filter and Actions -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=admin" class="filter-tab <?php echo $filter == 'admin' ? 'active' : ''; ?>">Admins</a>
                <a href="?filter=seller" class="filter-tab <?php echo $filter == 'seller' ? 'active' : ''; ?>">Sellers</a>
                <a href="?filter=user" class="filter-tab <?php echo $filter == 'user' ? 'active' : ''; ?>">Users</a>
            </div>

            <div class="actions-bar">
                <form method="GET" style="display:flex; gap:10px;">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
                <button class="btn btn-primary" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Add New User
                </button>
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
                    <?php while ($user = mysqli_fetch_assoc($users)): ?>
                        <div class="table-row">
                            <div class="user-info">
                                <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                <span>ID: #<?php echo $user['id']; ?></span>
                            </div>
                            <div class="user-info">
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div>
                                <span style="font-weight:600; text-transform:capitalize;"><?php echo htmlspecialchars($user['user_type']); ?></span>
                            </div>
                            <div>
                                <span class="status-badge <?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span>
                            </div>
                            <div style="font-size:13px; color:#64748b;">
                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                            </div>
                            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                <button class="btn btn-outline btn-sm" onclick="editUser(
                                    <?php echo $user['id']; ?>,
                                    '<?php echo addslashes($user['full_name']); ?>',
                                    '<?php echo addslashes($user['email']); ?>',
                                    '<?php echo $user['user_type']; ?>',
                                    '<?php echo $user['status']; ?>',
                                    '<?php echo addslashes($user['phone'] ?? ''); ?>'
                                )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?toggle=<?php echo $user['id']; ?>" class="btn btn-sm <?php echo $user['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $user['status'] == 'active' ? 'Block' : 'Activate'; ?>
                                </a>
                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding:48px; text-align:center; color:#94a3b8;">No users found</div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h2>Add New User</h2>
        <form method="POST">
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
                    <label>Role</label>
                    <select name="user_type" required>
                        <option value="user">User</option>
                        <option value="seller">Seller</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h2>Edit User</h2>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
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
                    <label>Role</label>
                    <select name="user_type" id="edit_type" required>
                        <option value="user">User</option>
                        <option value="seller">Seller</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" required>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
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

function editUser(id, name, email, type, status, phone) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_phone').value = phone;
    openModal('editModal');
}

document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('active');
    });
});
</script>

</body>
</html>