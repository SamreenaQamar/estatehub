<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = 'Notifications';
$notifications = [];

$msg_query = mysqli_query($conn,
    "SELECT m.id, m.message, m.is_read, m.created_at, u.full_name as sender_name, u.email as sender_email, p.title as property_title 
     FROM messages m 
     JOIN users u ON m.sender_id = u.id 
     LEFT JOIN properties p ON m.property_id = p.id 
     ORDER BY m.created_at DESC 
     LIMIT 20"
);

if ($msg_query) {
    while ($row = mysqli_fetch_assoc($msg_query)) {
        $notifications[] = [
            'type' => 'message',
            'id' => $row['id'],
            'icon' => 'fa-envelope',
            'color' => '#16A366',
            'title' => 'New Message from ' . $row['sender_name'],
            'text' => substr($row['message'], 0, 80) . '...',
            'time' => $row['created_at'],
            'link' => 'manage-messages.php',
            'is_read' => (bool)$row['is_read']
        ];
    }
}

$user_query = mysqli_query($conn, "SELECT id, full_name, email, user_type, created_at FROM users ORDER BY created_at DESC LIMIT 10");

if ($user_query) {
    while ($row = mysqli_fetch_assoc($user_query)) {
        $time_diff = time() - strtotime($row['created_at']);
        if ($time_diff < 86400 * 7) {
            $notifications[] = [
                'type' => 'user',
                'id' => $row['id'],
                'icon' => 'fa-user-plus',
                'color' => '#10b981',
                'title' => 'New User Registered',
                'text' => $row['full_name'] . ' (' . $row['email'] . ') joined as ' . $row['user_type'],
                'time' => $row['created_at'],
                'link' => 'manage-users.php',
                'is_read' => true
            ];
        }
    }
}

$inq_query = mysqli_query($conn, "SELECT id, name, email, subject, message, is_replied, created_at FROM inquiries ORDER BY created_at DESC LIMIT 10");

if ($inq_query) {
    while ($row = mysqli_fetch_assoc($inq_query)) {
        $notifications[] = [
            'type' => 'inquiry',
            'id' => $row['id'],
            'icon' => 'fa-question-circle',
            'color' => '#f59e0b',
            'title' => 'New Inquiry: ' . ($row['subject'] ?? 'General Inquiry'),
            'text' => $row['name'] . ' (' . $row['email'] . ') - ' . substr($row['message'], 0, 60) . '...',
            'time' => $row['created_at'],
            'link' => 'manage-inquiries.php',
            'is_read' => (bool)$row['is_replied']
        ];
    }
}

$prop_query = mysqli_query($conn,
    "SELECT p.id, p.title, p.price, p.created_at, u.full_name as owner_name 
     FROM properties p 
     JOIN users u ON p.user_id = u.id 
     ORDER BY p.created_at DESC 
     LIMIT 10"
);

if ($prop_query) {
    while ($row = mysqli_fetch_assoc($prop_query)) {
        $time_diff = time() - strtotime($row['created_at']);
        if ($time_diff < 86400 * 7) {
            $notifications[] = [
                'type' => 'property',
                'id' => $row['id'],
                'icon' => 'fa-home',
                'color' => '#8b5cf6',
                'title' => 'New Property Listed',
                'text' => $row['title'] . ' by ' . $row['owner_name'] . ' - PKR ' . number_format($row['price'] / 1000000, 1) . 'M',
                'time' => $row['created_at'],
                'link' => 'manage-properties.php',
                'is_read' => true
            ];
        }
    }
}

usort($notifications, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) {
        $unread_count++;
    }
}

function timeAgoNotify($timestamp) {
    $diff = time() - strtotime($timestamp);

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hrs ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';

    return date('M d, Y', strtotime($timestamp));
}

// Stats for sidebar (NO BADGES - SAME AS INDEX)
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

// Admin profile pic
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

        /* ===== SIDEBAR - SAME AS INDEX.PHP ===== */
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

        /* ===== NO BADGES - SAME AS INDEX ===== */

        .logout-link {
            color:#b8c2cc !important;
        }
        .logout-link:hover {
            background:linear-gradient(135deg,#dc2626,#ef4444) !important;
            color:#fff !important;
            box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
        }

        /* ===== TOP BAR - NO SEARCH, NO BADGE ===== */
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

        /* ===== MAIN CONTENT ===== */
        .main-content { flex:1; overflow-y:auto; }
        .content-inner { padding:28px 32px 40px; }

        /* ===== NOTIFICATIONS CONTENT ===== */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: #0b1a2e;
        }
        .page-header h1 i {
            color: #0E7A4E;
            margin-right: 10px;
        }

        .header-badge {
            background: #dcfce7;
            color: #0a5c3a;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notification-item {
            background: #fff;
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            gap: 16px;
            border: 1px solid #edf2f7;
            cursor: pointer;
            transition: all .3s ease;
            position: relative;
            text-decoration: none;
            color: inherit;
            align-items: center;
        }
        .notification-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            transform: translateX(4px);
            border-color: #d1d5db;
        }
        .notification-item.unread {
            border-left: 4px solid #0E7A4E;
            background: #fafffe;
        }

        .notif-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            color: white;
        }

        .notif-content {
            flex: 1;
            min-width: 0;
        }

        .notif-type-badge {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 10px;
            border-radius: 6px;
            margin-bottom: 8px;
            display: inline-block;
        }
        .badge-message { background:#eff6ff; color:#0a5c3a; }
        .badge-user { background:#ecfdf5; color:#059669; }
        .badge-inquiry { background:#fffbeb; color:#d97706; }
        .badge-property { background:#f5f3ff; color:#7c3aed; }

        .notif-title {
            font-size: 15px;
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 4px;
        }
        .notif-text {
            font-size: 13px;
            color: #475569;
            line-height: 1.5;
            max-height: 39px;
            overflow: hidden;
        }
        .notif-time {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
            font-weight: 500;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            background: #0E7A4E;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 6px;
        }

        /* Delete button */
        .delete-notif {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
            padding: 8px;
            border-radius: 50%;
            transition: 0.2s;
            flex-shrink: 0;
            margin-left: 8px;
        }
        .delete-notif:hover {
            background: #fee2e2;
            color: #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            border: 1px solid #edf2f7;
        }
        .empty-icon {
            font-size: 56px;
            color: #d1d5db;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 18px;
            color: #0b1a2e;
            margin-bottom: 6px;
            font-weight: 700;
        }
        .empty-state p {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Removing animation */
        .notification-item.removing {
            opacity: 0;
            transform: translateX(30px);
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
        }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
        }
        @media (max-width:768px) {
            .content-inner { padding:16px; }
            .topbar { padding:12px 16px; flex-wrap:wrap; }
            .notification-item { padding:16px; flex-wrap:wrap; }
            .notif-icon { width:40px; height:40px; font-size:16px; border-radius:10px; }
            .page-header h1 { font-size:20px; }
            .delete-notif { padding:6px; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR - WITH WISHLIST ===== -->
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
                </a>
            </li>
            <li>
                <a href="manage-properties.php" class="<?php echo ($current_page == 'manage-properties.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Properties
                </a>
            </li>
            <li>
                <a href="manage-messages.php" class="<?php echo ($current_page == 'manage-messages.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages
                </a>
            </li>
            <li>
                <a href="wishlist.php" class="<?php echo ($current_page == 'wishlist.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    Wishlist
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
                        <path d="M5 20v-2a7 7 0 0 1 14 0v2"/>
                    </svg>
                    Profile
                </a>
            </li>
            <li>
                <a href="notifications.php" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    Notifications
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

        <!-- TOP BAR - NO SEARCH, NO BADGE -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('adminSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-actions">
                <button class="icon-btn" title="Notifications" onclick="window.location.href='notifications.php'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
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
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <?php if ($unread_count > 0): ?>
                    <span class="header-badge" id="unreadBadge"><?php echo $unread_count; ?> unread</span>
                <?php endif; ?>
            </div>

            <!-- Notifications List -->
            <?php if (!empty($notifications)): ?>
                <div class="notifications-list" id="notificationsList">
                    <?php foreach ($notifications as $notif): ?>
                        <?php $is_unread = !$notif['is_read']; ?>
                        <div class="notification-item <?php echo $is_unread ? 'unread' : ''; ?>" 
                             data-type="<?php echo $notif['type']; ?>" 
                             data-id="<?php echo $notif['id']; ?>">
                            <div class="notif-icon" style="background: <?php echo $notif['color']; ?>;">
                                <i class="fas <?php echo $notif['icon']; ?>"></i>
                            </div>
                            <div class="notif-content">
                                <span class="notif-type-badge badge-<?php echo $notif['type']; ?>"><?php echo ucfirst($notif['type']); ?></span>
                                <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="notif-text"><?php echo htmlspecialchars($notif['text']); ?></div>
                                <div class="notif-time">
                                    <i class="far fa-clock" style="margin-right: 4px;"></i>
                                    <?php echo timeAgoNotify($notif['time']); ?>
                                </div>
                            </div>
                            <?php if ($is_unread): ?>
                                <div class="unread-dot"></div>
                            <?php endif; ?>
                            <button class="delete-notif" title="Delete notification" onclick="event.stopPropagation(); deleteNotification(this, '<?php echo $notif['type']; ?>', <?php echo $notif['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="far fa-bell-slash"></i></div>
                    <h3>No notifications yet</h3>
                    <p>You'll see updates about new users, messages, and properties here.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// ===== DELETE NOTIFICATION =====
function deleteNotification(btn, type, id) {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return;
    }

    const item = btn.closest('.notification-item');

    fetch('delete-notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Slide out and remove the notification item
            item.classList.add('removing');
            setTimeout(() => {
                item.remove();

                // Update unread badge count
                const unreadBadge = document.getElementById('unreadBadge');
                if (unreadBadge) {
                    let count = parseInt(unreadBadge.textContent) || 0;
                    if (item.classList.contains('unread')) {
                        count--;
                        if (count > 0) {
                            unreadBadge.textContent = count + ' unread';
                        } else {
                            unreadBadge.remove();
                        }
                    }
                }

                // If no notifications left, show empty state
                const list = document.getElementById('notificationsList');
                if (list && list.children.length === 0) {
                    list.innerHTML = `
                        <div class="empty-state">
                            <div class="empty-icon"><i class="far fa-bell-slash"></i></div>
                            <h3>No notifications yet</h3>
                            <p>You'll see updates about new users, messages, and properties here.</p>
                        </div>
                    `;
                }
            }, 300);
        } else {
            alert(data.error || 'Failed to delete notification.');
        }
    })
    .catch(() => alert('Network error. Please try again.'));
}
</script>

</body>
</html>