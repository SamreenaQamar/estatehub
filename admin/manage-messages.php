<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['read'])) {
    $id = (int)$_GET['read'];
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE id = $id");
    header("Location: manage-messages.php?filter=" . ($_GET['filter'] ?? 'all'));
    exit();
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM messages WHERE id = $id");
    header("Location: manage-messages.php?filter=" . ($_GET['filter'] ?? 'all'));
    exit();
}

if (isset($_POST['reply'])) {
    $msg_id = (int)$_POST['message_id'];
    $reply = mysqli_real_escape_string($conn, $_POST['reply_message']);

    $result = mysqli_query($conn, "SELECT sender_id, property_id FROM messages WHERE id = $msg_id");
    $msg = mysqli_fetch_assoc($result);

    $admin_id = $_SESSION['user_id'];
    $sender_id = $msg['sender_id'];
    $property_id = $msg['property_id'];

    mysqli_query($conn,
        "INSERT INTO messages (sender_id, receiver_id, property_id, message, is_read, created_at) 
         VALUES ($admin_id, $sender_id, $property_id, 'Admin Reply: $reply', 0, NOW())"
    );

    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE id = $msg_id");

    $message_sent = "Reply sent successfully!";
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$query = "SELECT m.*, u.full_name as sender_name, u.email as sender_email, p.title as property_title 
          FROM messages m 
          JOIN users u ON m.sender_id = u.id 
          LEFT JOIN properties p ON m.property_id = p.id 
          WHERE 1 = 1";

if ($filter == 'unread') {
    $query .= " AND m.is_read = 0";
} elseif ($filter == 'read') {
    $query .= " AND m.is_read = 1";
}

$query .= " ORDER BY m.created_at DESC";

$messages = mysqli_query($conn, $query);
$total = mysqli_num_rows($messages);

$stats = [
    'users' => 0,
    'pending' => 0,
    'unread' => 0
];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['users'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

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

$page_title = 'Messages';
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

        /* ===== FILTER TABS ===== */
        .filter-tabs {
            display:flex;
            gap:10px;
            margin-bottom:24px;
            flex-wrap:wrap;
        }
        .filter-tab {
            padding:10px 22px;
            border-radius:30px;
            font-size:13px;
            font-weight:700;
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
        .filter-tab .count {
            background:rgba(255,255,255,0.2);
            padding:1px 10px;
            border-radius:20px;
            font-size:11px;
            margin-left:4px;
        }
        .filter-tab.active .count {
            background:rgba(255,255,255,0.25);
        }

        /* ===== MESSAGE CARDS ===== */
        .message-card {
            background:#fff;
            border-radius:18px;
            padding:24px 28px;
            border:1px solid #edf2f7;
            margin-bottom:16px;
            transition:.3s;
            position:relative;
        }
        .message-card:hover {
            box-shadow:0 8px 30px rgba(0,0,0,0.06);
            transform:translateY(-2px);
        }
        .message-card.unread {
            border-left:4px solid #0E7A4E;
            background:#fafffe;
        }
        .message-card.unread .sender-name {
            color:#0E7A4E;
        }

        .message-header {
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            margin-bottom:12px;
            flex-wrap:wrap;
            gap:8px;
        }
        .sender-info {
            display:flex;
            align-items:center;
            gap:14px;
        }
        .sender-avatar {
            width:44px;
            height:44px;
            border-radius:12px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:17px;
            color:white;
            flex-shrink:0;
        }
        .sender-avatar.avatar-green {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
        }
        .sender-details .sender-name {
            font-size:16px;
            font-weight:700;
            color:#0b1a2e;
        }
        .sender-details .sender-email {
            font-size:12px;
            color:#94a3b8;
            font-weight:500;
        }
        .message-time {
            font-size:12px;
            color:#94a3b8;
            font-weight:500;
            white-space:nowrap;
        }

        .message-body {
            font-size:14.5px;
            color:#334155;
            line-height:1.7;
            margin-bottom:14px;
            padding:0 0 0 58px;
        }

        .property-ref {
            font-size:13px;
            color:#0E7A4E;
            font-weight:600;
            padding:0 0 0 58px;
            margin-bottom:12px;
        }
        .property-ref i {
            margin-right:6px;
        }

        .message-actions {
            display:flex;
            gap:8px;
            padding:0 0 0 58px;
            flex-wrap:wrap;
        }

        .btn {
            padding:8px 18px;
            border-radius:30px;
            font-size:12px;
            font-weight:700;
            border:none;
            cursor:pointer;
            transition:.3s;
            display:inline-flex;
            align-items:center;
            gap:6px;
            text-decoration:none;
            font-family:'Inter',sans-serif;
        }
        .btn-primary {
            background:#0E7A4E;
            color:white;
        }
        .btn-primary:hover {
            background:#0a5c3a;
            transform:translateY(-2px);
            box-shadow:0 4px 12px rgba(14,122,78,.3);
        }
        .btn-ghost {
            background:#f1f5f9;
            color:#475569;
        }
        .btn-ghost:hover {
            background:#e2e8f0;
        }
        .btn-danger {
            background:#ef4444;
            color:white;
        }
        .btn-danger:hover {
            background:#dc2626;
        }
        .btn-sm { padding:6px 14px; font-size:11px; }

        /* ===== REPLY BOX ===== */
        .reply-box {
            display:none;
            padding:16px 0 0 58px;
            margin-top:12px;
            border-top:1px solid #edf2f7;
        }
        .reply-box.active {
            display:block;
        }
        .reply-box textarea {
            width:100%;
            padding:12px 16px;
            border:2px solid #e9ecef;
            border-radius:14px;
            font-size:14px;
            resize:vertical;
            min-height:80px;
            font-family:'Inter',sans-serif;
            transition:.3s;
        }
        .reply-box textarea:focus {
            outline:none;
            border-color:#0E7A4E;
            box-shadow:0 0 0 4px rgba(14,122,78,.10);
        }
        .reply-box .btn {
            margin-top:10px;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align:center;
            padding:60px 20px;
            color:#94a3b8;
        }
        .empty-state .icon {
            font-size:56px;
            color:#d1d5db;
            margin-bottom:16px;
            display:block;
        }
        .empty-state h3 {
            font-size:18px;
            font-weight:700;
            color:#475569;
            margin-bottom:6px;
        }
        .empty-state p {
            font-size:14px;
        }

        /* ===== ALERT ===== */
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
        .alert-success {
            background:#dcfce7;
            color:#15803d;
            border:1px solid #86efac;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
        }
        @media (max-width:768px) {
            .content-inner { padding:16px; }
            .topbar { padding:12px 16px; flex-wrap:wrap; }
            .message-card { padding:18px 16px; }
            .message-body,
            .property-ref,
            .message-actions,
            .reply-box {
                padding-left:0;
            }
            .message-header { flex-direction:column; }
            .message-time { align-self:flex-start; }
            .sender-avatar { width:38px; height:38px; font-size:14px; }
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
                <h1>Message Center</h1>
                <p>View and manage all user messages and inquiries</p>
            </div>

            <?php if (isset($message_sent)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message_sent; ?>
                </div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    All <span class="count"><?php echo $total; ?></span>
                </a>
                <a href="?filter=unread" class="filter-tab <?php echo $filter == 'unread' ? 'active' : ''; ?>">
                    Unread <span class="count"><?php echo $stats['unread']; ?></span>
                </a>
                <a href="?filter=read" class="filter-tab <?php echo $filter == 'read' ? 'active' : ''; ?>">
                    Read
                </a>
            </div>

            <!-- Messages -->
            <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
                <?php while ($msg = mysqli_fetch_assoc($messages)): ?>
                    <div class="message-card <?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                        <div class="message-header">
                            <div class="sender-info">
                                <div class="sender-avatar avatar-green">
                                    <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                </div>
                                <div class="sender-details">
                                    <div class="sender-name"><?php echo htmlspecialchars($msg['sender_name']); ?></div>
                                    <div class="sender-email"><?php echo htmlspecialchars($msg['sender_email']); ?></div>
                                </div>
                            </div>
                            <div class="message-time">
                                <i class="far fa-clock"></i>
                                <?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?>
                            </div>
                        </div>

                        <div class="message-body">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>

                        <?php if (!empty($msg['property_title'])): ?>
                            <div class="property-ref">
                                <i class="fas fa-home"></i> Regarding: <?php echo htmlspecialchars($msg['property_title']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="message-actions">
                            <?php if (!$msg['is_read']): ?>
                                <a href="?read=<?php echo $msg['id']; ?>&filter=<?php echo $filter; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-check"></i> Mark as Read
                                </a>
                            <?php endif; ?>
                            <button class="btn btn-ghost btn-sm" onclick="toggleReply(<?php echo $msg['id']; ?>)">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                            <a href="?delete=<?php echo $msg['id']; ?>&filter=<?php echo $filter; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this message?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>

                        <div class="reply-box" id="reply-<?php echo $msg['id']; ?>">
                            <form method="POST">
                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                <textarea name="reply_message" rows="3" placeholder="Type your reply to <?php echo htmlspecialchars($msg['sender_name']); ?>..."></textarea>
                                <button type="submit" name="reply" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Reply
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <span class="icon"><i class="fas fa-inbox"></i></span>
                    <h3>No messages found</h3>
                    <p>All caught up! No messages to display.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function toggleReply(id) {
    var box = document.getElementById('reply-' + id);
    box.classList.toggle('active');

    if (box.classList.contains('active')) {
        var textarea = box.querySelector('textarea');
        setTimeout(function() { textarea.focus(); }, 100);
    }
}

// Auto-close reply boxes when clicking outside
document.addEventListener('click', function(e) {
    var replyBoxes = document.querySelectorAll('.reply-box.active');
    replyBoxes.forEach(function(box) {
        if (!box.contains(e.target) && !e.target.closest('.message-actions')) {
            box.classList.remove('active');
        }
    });
});
</script>

</body>
</html>