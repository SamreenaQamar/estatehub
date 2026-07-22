<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$total_users = 0;
$total_properties = 0;
$total_messages = 0;
$total_revenue = 0;
$pending_count = 0;
$active_users = 0;
$unread_msgs = 0;

$queries = [
    'users' => "SELECT COUNT(*) as total FROM users",
    'properties' => "SELECT COUNT(*) as total FROM properties",
    'messages' => "SELECT COUNT(*) as total FROM messages",
    'revenue' => "SELECT COALESCE(SUM(price), 0) as total FROM properties WHERE status = 'Sold'",
    'pending' => "SELECT COUNT(*) as total FROM properties WHERE status = 'Pending'",
    'active' => "SELECT COUNT(*) as total FROM users WHERE status = 'active'",
    'unread' => "SELECT COUNT(*) as total FROM messages WHERE is_read = 0"
];

foreach ($queries as $key => $sql) {
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $value = $row['total'] ?? 0;
        switch ($key) {
            case 'users': $total_users = $value; break;
            case 'properties': $total_properties = $value; break;
            case 'messages': $total_messages = $value; break;
            case 'revenue': $total_revenue = $value; break;
            case 'pending': $pending_count = $value; break;
            case 'active': $active_users = $value; break;
            case 'unread': $unread_msgs = $value; break;
        }
    }
}

$recent_users = mysqli_query($conn,
    "SELECT id, full_name, email, user_type, status, created_at 
     FROM users ORDER BY created_at DESC LIMIT 6"
);

$recent_props = mysqli_query($conn,
    "SELECT p.id, p.title, p.price, p.status, p.created_at, u.full_name as owner 
     FROM properties p 
     JOIN users u ON p.user_id = u.id 
     ORDER BY p.created_at DESC LIMIT 6"
);

$recent_msgs = mysqli_query($conn,
    "SELECT m.id, m.message, m.is_read, m.created_at, u.full_name as sender, u.email as sender_email 
     FROM messages m 
     JOIN users u ON m.sender_id = u.id 
     ORDER BY m.created_at DESC LIMIT 5"
);

$chart_labels = [];
$chart_views = [];
for ($i = 6; $i >= 0; $i--) {
    $chart_labels[] = date('D', strtotime("-$i days"));
    $chart_views[] = rand(100, 500);
}

$type_data = [];
$type_colors = [
    'House' => '#16A366',
    'Apartment' => '#10b981',
    'Commercial' => '#f59e0b',
    'Plot' => '#8b5cf6',
    'Villa' => '#ec4899'
];

$type_query = mysqli_query($conn, "SELECT property_type, COUNT(*) as count FROM properties GROUP BY property_type");
if ($type_query) {
    while ($t = mysqli_fetch_assoc($type_query)) {
        $type_data[$t['property_type']] = $t['count'];
    }
}
$total_types = array_sum($type_data) ?: 1;

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

$page_title = 'Admin Dashboard';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        /* ===== SIDEBAR - SAME AS NOTIFICATIONS PAGE ===== */
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

        /* ===== LOGO ===== */
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

        /* ===== NO BADGES - REMOVED ===== */

        /* ===== LOGOUT LINK ===== */
        .logout-link {
            color:#b8c2cc !important;
        }
        .logout-link:hover {
            background:linear-gradient(135deg,#dc2626,#ef4444) !important;
            color:#fff !important;
            box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
        }

        /* ===== TOP BAR - SAME AS NOTIFICATIONS ===== */
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
        .topbar-menu-btn { display:none; background:none; border:none; cursor:pointer; padding:6px; }

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
        /* .count removed - no badge on bell */
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

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            border-radius:24px;
            padding:35px 40px;
            margin-bottom:28px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            flex-wrap:wrap;
            color:white;
        }
        .welcome-banner h1 { font-size:21px; font-weight:700; }
        .welcome-banner p { font-size:15px; opacity:0.9; margin-top:4px; }
        .welcome-meta { display:flex; align-items:center; gap:16px; font-size:14px; opacity:0.85; }
        .welcome-meta span { display:flex; align-items:center; gap:6px; }

        /* Stats Grid */
        .stats-grid {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:16px;
            margin-bottom:26px;
        }
        .stat-card {
            background:#fff;
            border-radius:16px;
            padding:16px;
            border:1px solid #edf2f7;
            position:relative;
            overflow:hidden;
            transition:.3s ease;
            cursor:pointer;
        }
        .stat-card::before {
            content:'';
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:3px;
            background:#0E7A4E;
        }
        .stat-card:hover {
            transform:translateY(-4px);
            border-color:#0E7A4E;
            box-shadow:0 12px 25px rgba(14,122,78,.12);
        }
        .stat-icon {
            width:42px;
            height:42px;
            border-radius:12px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-bottom:10px;
            background:rgba(14,122,78,.10);
            color:#0E7A4E;
        }
        .stat-icon svg { width:22px; height:22px; stroke:currentColor; fill:none; stroke-width:2; }
        .stat-number { font-size:26px; font-weight:800; color:#0b1a2e; line-height:1; margin-bottom:6px; }
        .stat-label { font-size:13px; color:#64748b; margin-bottom:6px; font-weight:500; }
        .stat-growth { font-size:11px; font-weight:600; display:flex; align-items:center; gap:4px; }
        .stat-growth.up { color:#0E7A4E; }
        .stat-growth.down { color:#ef4444; }
        .stat-growth.neutral { color:#94a3b8; }

        /* Content Grid */
        .content-grid {
            display:grid;
            grid-template-columns:1.5fr 1fr;
            gap:20px;
        }
        .content-card {
            background:#fff;
            border-radius:18px;
            padding:20px;
            border:1px solid #edf2f7;
            box-shadow:0 3px 10px rgba(15,23,42,.03);
        }
        .content-card:hover {
            box-shadow:0 10px 25px rgba(15,23,42,.05);
        }
        .card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:18px;
            padding-bottom:12px;
            border-bottom:1px solid #edf2f7;
        }
        .card-header h3 {
            font-size:18px;
            font-weight:700;
            color:#0b1a2e;
        }
        .card-header a {
            color:#0E7A4E;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
        }
        .card-header a:hover { color:#0a5c3a; }

        /* Property Items */
        .property-item {
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:12px;
            border-radius:12px;
            transition:.3s;
            margin-bottom:8px;
        }
        .property-item:hover { background:#f8fafc; }
        .property-item:last-child { margin-bottom:0; }
        .property-item .info h4 { font-size:14px; font-weight:700; color:#0b1a2e; margin-bottom:4px; }
        .property-item .meta { font-size:12px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .status-badge {
            padding:3px 10px;
            border-radius:30px;
            font-size:10px;
            font-weight:700;
        }
        .status-active { background:#dcfce7; color:#15803d; }
        .status-pending { background:#fef3c7; color:#b45309; }
        .status-sold { background:#e5e7eb; color:#374151; }
        .property-item .price { color:#0E7A4E; font-weight:800; font-size:14px; }

        /* Messages */
        .message-item {
            display:flex;
            align-items:flex-start;
            gap:12px;
            padding:12px 0;
            border-bottom:1px solid #edf2f7;
        }
        .message-item:last-child { border-bottom:none; }
        .msg-avatar {
            width:40px;
            height:40px;
            border-radius:50%;
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:14px;
            flex-shrink:0;
        }
        .msg-body { flex:1; min-width:0; }
        .msg-name { font-size:13px; font-weight:700; color:#0b1a2e; margin-bottom:4px; }
        .msg-preview { font-size:12px; color:#64748b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .msg-time { font-size:11px; color:#94a3b8; white-space:nowrap; }

        .empty-state {
            text-align:center;
            padding:35px 20px;
        }
        .empty-state svg { width:50px; height:50px; color:#cbd5e1; margin-bottom:10px; }
        .empty-state p { color:#64748b; font-size:13px; margin-bottom:8px; }
        .empty-state a { color:#0E7A4E; text-decoration:none; font-weight:700; }

        /* Chart Cards */
        .charts-row {
            display:grid;
            grid-template-columns:1.5fr 1fr;
            gap:24px;
            margin-bottom:32px;
        }
        .chart-card {
            background:#fff;
            border-radius:18px;
            padding:24px;
            border:1px solid #edf2f7;
        }
        .chart-card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
        }
        .chart-card-header h2 { font-size:16px; font-weight:700; color:#0b1a2e; }

        .bar-chart-container {
            position:relative;
            padding-left:50px;
            padding-bottom:35px;
        }
        .y-axis-labels {
            position:absolute;
            left:0;
            top:0;
            bottom:35px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
        }
        .y-axis-labels span { font-size:11px; color:#94a3b8; font-weight:600; text-align:right; width:42px; }
        .bars-wrapper {
            display:flex;
            align-items:flex-end;
            gap:16px;
            height:200px;
            border-bottom:2px solid #e9ecef;
            border-left:2px solid #e9ecef;
            padding-left:16px;
        }
        .bar-item {
            flex:1;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:flex-end;
            height:100%;
            gap:6px;
        }
        .bar {
            width:100%;
            max-width:40px;
            background:linear-gradient(180deg, #0E7A4E, #16a34a);
            border-radius:6px 6px 0 0;
            cursor:pointer;
            transition:.3s;
            min-height:6px;
        }
        .bar:hover { filter:brightness(1.2); transform:scaleY(1.04); transform-origin:bottom; }
        .bar-label { font-size:11px; color:#94a3b8; font-weight:600; }

        .donut-container {
            display:flex;
            align-items:center;
            gap:30px;
        }
        .donut-chart {
            width:160px;
            height:160px;
            border-radius:50%;
            flex-shrink:0;
        }
        .donut-legend { display:flex; flex-direction:column; gap:14px; flex:1; }
        .legend-item { display:flex; align-items:center; gap:12px; }
        .legend-dot { width:14px; height:14px; border-radius:6px; flex-shrink:0; }
        .legend-label { font-size:13px; color:#475569; flex:1; font-weight:500; }
        .legend-value { font-weight:700; font-size:14px; color:#0b1a2e; }

        /* Bottom Row */
        .bottom-row {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:20px;
            margin-top:20px;
        }
        .data-card {
            background:#fff;
            border-radius:18px;
            padding:20px;
            border:1px solid #edf2f7;
        }
        .data-card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:16px;
        }
        .data-card-header h2 { font-size:16px; font-weight:700; color:#0b1a2e; }
        .view-all-btn {
            font-size:12px;
            font-weight:600;
            color:#0E7A4E;
            text-decoration:none;
        }
        .view-all-btn:hover { color:#0a5c3a; }

        .data-list-item {
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:12px 8px;
            border-radius:10px;
            transition:.3s;
            cursor:pointer;
        }
        .data-list-item:hover { background:#f8fafc; }
        .data-user-info { display:flex; align-items:center; gap:12px; }
        .data-user-avatar {
            width:40px;
            height:40px;
            border-radius:10px;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:15px;
            flex-shrink:0;
            color:white;
        }
        .avatar-blue { background:linear-gradient(135deg,#0E7A4E,#16a34a); }
        .avatar-green { background:linear-gradient(135deg,#16a34a,#22c55e); }
        .avatar-purple { background:linear-gradient(135deg,#8b5cf6,#a78bfa); }
        .avatar-pink { background:linear-gradient(135deg,#ec4899,#f472b6); }
        .avatar-amber { background:linear-gradient(135deg,#f59e0b,#fbbf24); }
        .avatar-cyan { background:linear-gradient(135deg,#06b6d4,#22d3ee); }

        .data-user-details h4 {
            font-size:14px;
            font-weight:600;
            color:#0b1a2e;
        }
        .data-user-details span { font-size:12px; color:#64748b; display:block; margin-top:2px; }

        .status-indicator {
            width:10px;
            height:10px;
            border-radius:50%;
            display:inline-block;
        }
        .status-active { background:#22c55e; box-shadow:0 0 8px rgba(34,197,94,0.4); }
        .status-pending { background:#f59e0b; box-shadow:0 0 8px rgba(245,158,11,0.4); }

        .unread-dot {
            width:8px;
            height:8px;
            border-radius:50%;
            background:#0E7A4E;
            display:inline-block;
            margin-left:6px;
        }

        .message-preview {
            font-size:12px;
            color:#94a3b8;
            max-width:140px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }

        /* Responsive */
        @media (max-width:1200px) {
            .stats-grid { grid-template-columns:repeat(2,1fr); }
            .charts-row { grid-template-columns:1fr; }
            .bottom-row { grid-template-columns:1fr 1fr; }
        }
        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
            .content-grid { grid-template-columns:1fr; }
        }
        @media (max-width:768px) {
            .content-inner { padding:18px; }
            .topbar { padding:12px 18px; }
            .stats-grid { grid-template-columns:1fr 1fr; gap:12px; }
            .bottom-row { grid-template-columns:1fr; }
            .welcome-banner { flex-direction:column; align-items:flex-start; }
            .welcome-banner h1 { font-size:20px; }
            .user-info { display:none; }
            .charts-row { grid-template-columns:1fr; }
            .donut-container { flex-direction:column; }
        }
        @media (max-width:480px) {
            .stats-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR - FULL (no badges) ===== -->
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
            <!-- ===== NEW WISHLIST LINK (under Main Menu) ===== -->
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

        <!-- TOP BAR - NO BADGE ON BELL -->
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

        

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='manage-users.php'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 1 0-16 0"/></svg>
                    </div>
                    <div class="stat-number"><?php echo number_format($total_users); ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-growth up">↗ 12.5% from last month</div>
                </div>

                <div class="stat-card" onclick="window.location.href='manage-properties.php'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div class="stat-number"><?php echo number_format($total_properties); ?></div>
                    <div class="stat-label">Total Properties</div>
                    <div class="stat-growth up">↗ 8.3% from last month</div>
                </div>

                <div class="stat-card" onclick="window.location.href='manage-messages.php'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="stat-number"><?php echo number_format($total_messages); ?></div>
                    <div class="stat-label">Total Messages</div>
                    <div class="stat-growth up">↗ 24.1% from last month</div>
                </div>

                <div class="stat-card" onclick="window.location.href='reports.php'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 22v-4M4 12H2M22 12h-2M19.07 4.93l-2.83 2.83M6.76 16.24l-2.83 2.83M17.24 16.24l2.83 2.83M6.76 7.76l-2.83-2.83"/></svg>
                    </div>
                    <div class="stat-number">$<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-growth up">↗ 15.7% from last month</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-card-header">
                        <h2><i class="fas fa-chart-bar" style="color:#0E7A4E; margin-right:8px;"></i>Property Views</h2>
                    </div>
                    <div class="bar-chart-container">
                        <div class="y-axis-labels">
                        </div>
                        <div class="bars-wrapper">
                            <?php foreach ($chart_views as $i => $value): ?>
                                <div class="bar-item">
                                    <div class="bar" style="height: <?php echo max(4, ($value/500)*200); ?>px;" title="<?php echo $value; ?> views"></div>
                                    <span class="bar-label"><?php echo $chart_labels[$i]; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-card-header">
                        <h2><i class="fas fa-chart-pie" style="color:#8b5cf6; margin-right:8px;"></i>Property Types</h2>
                    </div>
                    <div class="donut-container">
                        <?php
                        $conic = '';
                        $cumulative = 0;
                        if (!empty($type_data)) {
                            foreach ($type_data as $type => $count) {
                                $pct = round(($count / $total_types) * 100);
                                $color = $type_colors[$type] ?? '#94a3b8';
                                $conic .= "$color {$cumulative}% " . ($cumulative + $pct) . "%, ";
                                $cumulative += $pct;
                            }
                            $conic = rtrim($conic, ', ');
                        } else {
                            $conic = "#94a3b8 0% 100%";
                        }
                        ?>
                        <div class="donut-chart" style="background: conic-gradient(<?php echo $conic; ?>);"></div>
                        <div class="donut-legend">
                            <?php if (!empty($type_data)): ?>
                                <?php foreach ($type_data as $type => $count): ?>
                                    <?php 
                                    $pct = round(($count / $total_types) * 100);
                                    $color = $type_colors[$type] ?? '#94a3b8';
                                    ?>
                                    <div class="legend-item">
                                        <div class="legend-dot" style="background: <?php echo $color; ?>"></div>
                                        <span class="legend-label"><?php echo htmlspecialchars($type); ?></span>
                                        <span class="legend-value"><?php echo $pct; ?>%</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="legend-item">
                                    <div class="legend-dot" style="background:#94a3b8;"></div>
                                    <span class="legend-label">No data</span>
                                    <span class="legend-value">100%</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="bottom-row">
                <div class="data-card">
                    <div class="data-card-header">
                        <h2><i class="fas fa-user-friends" style="color:#0E7A4E; margin-right:8px;"></i>Recent Users</h2>
                        <a href="manage-users.php" class="view-all-btn">View All →</a>
                    </div>
                    <div class="data-list">
                        <?php
                        $avatar_colors = ['avatar-blue', 'avatar-green', 'avatar-purple', 'avatar-pink', 'avatar-amber', 'avatar-cyan'];
                        if ($recent_users && mysqli_num_rows($recent_users) > 0):
                            $i = 0;
                            while ($user = mysqli_fetch_assoc($recent_users)):
                                $initials = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
                        ?>
                            <div class="data-list-item">
                                <div class="data-user-info">
                                    <div class="data-user-avatar <?php echo $avatar_colors[$i % 6]; ?>"><?php echo $initials; ?></div>
                                    <div class="data-user-details">
                                        <h4>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                            <span class="status-indicator <?php echo ($user['status'] ?? '') == 'active' ? 'status-active' : 'status-pending'; ?>"></span>
                                        </h4>
                                        <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                                    </div>
                                </div>
                                <span style="font-size:11px; color:#94a3b8;"><?php echo date('M d', strtotime($user['created_at'] ?? 'now')); ?></span>
                            </div>
                        <?php 
                            $i++;
                            endwhile;
                        else:
                        ?>
                            <div style="padding:20px; text-align:center; color:#94a3b8;">No users yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <h2><i class="fas fa-home" style="color:#16a34a; margin-right:8px;"></i>Latest Properties</h2>
                        <a href="manage-properties.php" class="view-all-btn">View All →</a>
                    </div>
                    <div class="data-list">
                        <?php if ($recent_props && mysqli_num_rows($recent_props) > 0): ?>
                            <?php while ($prop = mysqli_fetch_assoc($recent_props)): ?>
                                <div class="data-list-item">
                                    <div class="data-user-info">
                                        <div class="data-user-avatar avatar-green" style="font-size:18px;">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="data-user-details">
                                            <h4><?php echo htmlspecialchars($prop['title']); ?></h4>
                                            <span>By <?php echo htmlspecialchars($prop['owner'] ?? 'N/A'); ?> · $<?php echo number_format($prop['price'] ?? 0); ?></span>
                                        </div>
                                    </div>
                                    <span class="status-indicator <?php echo strtolower($prop['status'] ?? '') == 'active' ? 'status-active' : 'status-pending'; ?>"></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="padding:20px; text-align:center; color:#94a3b8;">No properties yet</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="data-card">
                    <div class="data-card-header">
                        <h2><i class="fas fa-envelope" style="color:#8b5cf6; margin-right:8px;"></i>Unread Messages</h2>
                        <a href="manage-messages.php" class="view-all-btn">View All →</a>
                    </div>
                    <div class="data-list">
                        <?php if ($recent_msgs && mysqli_num_rows($recent_msgs) > 0): ?>
                            <?php while ($msg = mysqli_fetch_assoc($recent_msgs)): ?>
                                <div class="data-list-item">
                                    <div class="data-user-info">
                                        <div class="data-user-avatar avatar-purple"><?php echo strtoupper(substr($msg['sender'] ?? 'U', 0, 1)); ?></div>
                                        <div class="data-user-details">
                                            <h4><?php echo htmlspecialchars($msg['sender']); ?> <span class="unread-dot"></span></h4>
                                            <span class="message-preview"><?php echo htmlspecialchars(substr($msg['message'] ?? '', 0, 35)); ?>...</span>
                                        </div>
                                    </div>
                                    <span style="font-size:10px; color:#94a3b8;"><?php echo date('h:i A', strtotime($msg['created_at'] ?? 'now')); ?></span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="padding:20px; text-align:center; color:#94a3b8;">No messages yet</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bars = document.querySelectorAll('.bar');
    bars.forEach(function(bar) {
        bar.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.textContent = this.getAttribute('title');
            tooltip.style.cssText = 'position:fixed;background:#0f172a;color:white;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;pointer-events:none;font-family:Inter,sans-serif;box-shadow:0 8px 25px rgba(0,0,0,0.3);';
            document.body.appendChild(tooltip);
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
            tooltip.style.top = rect.top - 40 + 'px';
        });
        bar.addEventListener('mouseleave', function() {
            const tooltips = document.querySelectorAll('div[style*="position:fixed;background:#0f172a;"]');
            tooltips.forEach(function(t) { t.remove(); });
        });
    });
});
</script>

</body>
</html>