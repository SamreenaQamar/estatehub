<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$months = [];
$revenue_data = [];
$property_data = [];
$user_data = [];
$total_revenue = 0;
$total_properties_sold = 0;

for ($i = 11; $i >= 0; $i--) {
    $month_num = date('m', strtotime("-$i months"));
    $year_num = date('Y', strtotime("-$i months"));
    $months[] = date('M', strtotime("-$i months"));

    $result = mysqli_query($conn,
        "SELECT COALESCE(SUM(price), 0) as total 
         FROM properties 
         WHERE status = 'Sold' 
         AND MONTH(created_at) = '$month_num' 
         AND YEAR(created_at) = '$year_num'"
    );
    $rev = 0;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $rev = $row['total'] ?? 0;
    }
    $revenue_data[] = round($rev / 1000, 1);

    $result = mysqli_query($conn,
        "SELECT COUNT(*) as c 
         FROM properties 
         WHERE MONTH(created_at) = '$month_num' 
         AND YEAR(created_at) = '$year_num'"
    );
    $prop_count = 0;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $prop_count = $row['c'] ?? 0;
    }
    $property_data[] = $prop_count;

    $result = mysqli_query($conn,
        "SELECT COUNT(*) as c 
         FROM users 
         WHERE MONTH(created_at) = '$month_num' 
         AND YEAR(created_at) = '$year_num'"
    );
    $user_count = 0;
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user_count = $row['c'] ?? 0;
    }
    $user_data[] = $user_count;
}

$total_revenue_calc = array_sum($revenue_data) * 1000;

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE status = 'Sold'");
$total_properties_sold = 0;
if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $total_properties_sold = $row['c'] ?? 0;
}

$stats = [
    'users' => 0,
    'properties' => 0,
    'messages' => 0,
    'pending' => 0,
    'unread' => 0
];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['users'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['properties'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['messages'] = mysqli_fetch_assoc($result)['total'] ?? 0;
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

$page_title = 'Reports';
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
            margin-bottom:28px;
        }
        .stat-card {
            background:#fff;
            border-radius:16px;
            padding:24px;
            border:1px solid #edf2f7;
            text-align:center;
            transition:.3s;
        }
        .stat-card:hover {
            transform:translateY(-4px);
            box-shadow:0 12px 25px rgba(14,122,78,.10);
        }
        .stat-card .icon {
            font-size:28px;
            display:inline-block;
            padding:12px;
            border-radius:12px;
            margin-bottom:12px;
        }
        .stat-card .icon.blue { background:#dbeafe; color:#0E7A4E; }
        .stat-card .icon.green { background:#dcfce7; color:#16a34a; }
        .stat-card .icon.purple { background:#f3e8ff; color:#8b5cf6; }
        .stat-card .icon.amber { background:#fef3c7; color:#d97706; }
        .stat-card .number {
            font-size:32px;
            font-weight:900;
            color:#0b1a2e;
            line-height:1.1;
        }
        .stat-card .label {
            font-size:13px;
            color:#64748b;
            font-weight:500;
            margin-top:6px;
        }

        .chart-card {
            background:#fff;
            border-radius:18px;
            padding:24px 28px;
            border:1px solid #edf2f7;
            margin-bottom:24px;
        }
        .chart-card .chart-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:20px;
        }
        .chart-card .chart-header h2 {
            font-size:18px;
            font-weight:700;
            color:#0b1a2e;
        }
        .chart-card .chart-header h2 i {
            margin-right:8px;
            color:#0E7A4E;
        }

        .bar-chart {
            display:flex;
            align-items:flex-end;
            gap:14px;
            height:200px;
            border-bottom:2px solid #e9ecef;
            border-left:2px solid #e9ecef;
            padding-left:12px;
            padding-bottom:30px;
        }
        .bar-col {
            flex:1;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:flex-end;
            height:100%;
            gap:6px;
        }
        .bar-fill {
            width:100%;
            max-width:44px;
            border-radius:6px 6px 0 0;
            min-height:4px;
            transition:.3s;
            cursor:pointer;
        }
        .bar-fill:hover {
            filter:brightness(1.15);
            transform:scaleY(1.03);
            transform-origin:bottom;
        }
        .bar-fill.blue { background:linear-gradient(180deg,#0E7A4E,#16a34a); }
        .bar-fill.green { background:linear-gradient(180deg,#16a34a,#22c55e); }
        .bar-fill.purple { background:linear-gradient(180deg,#8b5cf6,#a78bfa); }
        .bar-label {
            font-size:11px;
            color:#94a3b8;
            font-weight:600;
        }

        .action-buttons {
            display:flex;
            gap:12px;
            margin-top:8px;
            flex-wrap:wrap;
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
        .btn-outline {
            background:white;
            color:#0E7A4E;
            border:2px solid #0E7A4E;
        }
        .btn-outline:hover { background:#EAF8F1; }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
            .stats-grid { grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:768px) {
            .content-inner { padding:16px; }
            .topbar { padding:12px 16px; flex-wrap:wrap; }
            .stats-grid { grid-template-columns:1fr; }
            .bar-chart { gap:8px; height:160px; }
            .bar-fill { max-width:30px; }
            .topbar-search { max-width:none; }
            .chart-card { padding:18px; }
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
                <h1>Analytics & Reports</h1>
                <p>Monitor platform performance, revenue trends and user growth</p>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="icon blue"><i class="fas fa-dollar-sign"></i></span>
                    <div class="number">$<?php echo number_format($total_revenue_calc / 1000, 1); ?>K</div>
                    <div class="label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <span class="icon green"><i class="fas fa-home"></i></span>
                    <div class="number"><?php echo number_format($total_properties_sold); ?></div>
                    <div class="label">Properties Sold</div>
                </div>
                <div class="stat-card">
                    <span class="icon purple"><i class="fas fa-users"></i></span>
                    <div class="number"><?php echo number_format($stats['users']); ?></div>
                    <div class="label">Total Users</div>
                </div>
                <div class="stat-card">
                    <span class="icon amber"><i class="fas fa-chart-line"></i></span>
                    <div class="number">+24%</div>
                    <div class="label">Growth Rate</div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h2><i class="fas fa-chart-line"></i>Monthly Revenue (in $K)</h2>
                </div>
                <div class="bar-chart">
                    <?php $max_rev = max($revenue_data) ?: 1; ?>
                    <?php foreach ($revenue_data as $i => $val): ?>
                        <div class="bar-col">
                            <div class="bar-fill blue" style="height: <?php echo max(4, ($val / $max_rev) * 180); ?>px;" title="$<?php echo number_format($val, 1); ?>K"></div>
                            <span class="bar-label"><?php echo $months[$i]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Properties Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h2><i class="fas fa-building"></i>Properties Listed Per Month</h2>
                </div>
                <div class="bar-chart">
                    <?php $max_prop = max($property_data) ?: 1; ?>
                    <?php foreach ($property_data as $i => $val): ?>
                        <div class="bar-col">
                            <div class="bar-fill green" style="height: <?php echo max(4, ($val / $max_prop) * 180); ?>px;" title="<?php echo $val; ?> properties"></div>
                            <span class="bar-label"><?php echo $months[$i]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Users Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h2><i class="fas fa-user-plus"></i>New Users Per Month</h2>
                </div>
                <div class="bar-chart">
                    <?php $max_user = max($user_data) ?: 1; ?>
                    <?php foreach ($user_data as $i => $val): ?>
                        <div class="bar-col">
                            <div class="bar-fill purple" style="height: <?php echo max(4, ($val / $max_user) * 180); ?>px;" title="<?php echo $val; ?> users"></div>
                            <span class="bar-label"><?php echo $months[$i]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn btn-primary"><i class="fas fa-download"></i> Export Report</button>
                <button class="btn btn-outline"><i class="fas fa-print"></i> Print</button>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var bars = document.querySelectorAll('.bar-fill');
    var activeTooltip = null;

    bars.forEach(function(bar) {
        bar.addEventListener('mouseenter', function() {
            var tooltip = document.createElement('div');
            tooltip.textContent = this.getAttribute('title');
            tooltip.style.cssText = 'position:fixed;background:#0f172a;color:white;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;pointer-events:none;font-family:Inter,sans-serif;box-shadow:0 8px 25px rgba(0,0,0,0.3);';
            document.body.appendChild(tooltip);

            var rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
            tooltip.style.top = rect.top - 35 + 'px';

            activeTooltip = tooltip;
        });

        bar.addEventListener('mouseleave', function() {
            if (activeTooltip) {
                activeTooltip.remove();
                activeTooltip = null;
            }
        });
    });
});
</script>

</body>
</html>