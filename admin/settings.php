<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$settings_message = '';

if (isset($_POST['save_settings'])) {
    $site_name = mysqli_real_escape_string($conn, $_POST['site_name'] ?? 'EstateHub');
    $admin_email = mysqli_real_escape_string($conn, $_POST['admin_email'] ?? '');
    $currency = mysqli_real_escape_string($conn, $_POST['currency'] ?? 'USD ($)');
    $timezone = mysqli_real_escape_string($conn, $_POST['timezone'] ?? 'UTC-5 (Eastern)');
    $phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $address = mysqli_real_escape_string($conn, $_POST['address'] ?? '');
    $footer_text = mysqli_real_escape_string($conn, $_POST['footer_text'] ?? '');

    $settings_data = [
        'site_name' => $site_name,
        'admin_email' => $admin_email,
        'currency' => $currency,
        'timezone' => $timezone,
        'phone' => $phone,
        'address' => $address,
        'footer_text' => $footer_text
    ];

    $result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");

    if (mysqli_num_rows($result) > 0) {
        foreach ($settings_data as $key => $value) {
            mysqli_query($conn,
                "INSERT INTO settings (setting_key, setting_value) 
                 VALUES ('$key', '$value') 
                 ON DUPLICATE KEY UPDATE setting_value = '$value'"
            );
        }
        $settings_message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Settings saved successfully!</div>';
    } else {
        $_SESSION['site_settings'] = $settings_data;
        $settings_message = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Settings saved to session! Create the settings table for permanent storage.</div>';
    }
}

$site_name = 'EstateHub';
$admin_email = 'admin@estatehub.com';
$currency = 'USD ($)';
$timezone = 'UTC-5 (Eastern)';
$phone = '';
$address = '';
$footer_text = '';

$result = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");

if (mysqli_num_rows($result) > 0) {
    $result = mysqli_query($conn, "SELECT * FROM settings");
    if ($result) {
        $settings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $site_name = $settings['site_name'] ?? $site_name;
        $admin_email = $settings['admin_email'] ?? $admin_email;
        $currency = $settings['currency'] ?? $currency;
        $timezone = $settings['timezone'] ?? $timezone;
        $phone = $settings['phone'] ?? $phone;
        $address = $settings['address'] ?? $address;
        $footer_text = $settings['footer_text'] ?? $footer_text;
    }
} elseif (isset($_SESSION['site_settings'])) {
    $site_name = $_SESSION['site_settings']['site_name'] ?? $site_name;
    $admin_email = $_SESSION['site_settings']['admin_email'] ?? $admin_email;
    $currency = $_SESSION['site_settings']['currency'] ?? $currency;
    $timezone = $_SESSION['site_settings']['timezone'] ?? $timezone;
    $phone = $_SESSION['site_settings']['phone'] ?? $phone;
    $address = $_SESSION['site_settings']['address'] ?? $address;
    $footer_text = $_SESSION['site_settings']['footer_text'] ?? $footer_text;
}

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

$page_title = 'Settings';
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

        .settings-card {
            background:#fff;
            border-radius:18px;
            padding:28px 32px;
            border:1px solid #edf2f7;
            margin-bottom:24px;
        }
        .settings-card .card-header {
            display:flex;
            align-items:center;
            gap:12px;
            padding-bottom:16px;
            margin-bottom:24px;
            border-bottom:1px solid #edf2f7;
        }
        .settings-card .card-header .icon {
            width:36px;
            height:36px;
            border-radius:10px;
            background:#dcfce7;
            color:#0E7A4E;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:16px;
        }
        .settings-card .card-header h2 {
            font-size:18px;
            font-weight:700;
            color:#0b1a2e;
        }
        .settings-card .desc {
            font-size:13px;
            color:#64748b;
            margin-bottom:24px;
        }

        .form-group {
            margin-bottom:20px;
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
            padding:12px 16px;
            border:2px solid #e9ecef;
            border-radius:12px;
            font-size:14px;
            transition:.3s;
            font-family:'Inter',sans-serif;
            background:#fafafa;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline:none;
            border-color:#0E7A4E;
            background:#fff;
            box-shadow:0 0 0 4px rgba(14,122,78,.10);
        }
        .form-row {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:18px;
        }

        .btn {
            padding:12px 28px;
            border-radius:30px;
            font-size:14px;
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

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
        }
        @media (max-width:768px) {
            .content-inner { padding:16px; }
            .topbar { padding:12px 16px; flex-wrap:wrap; }
            .form-row { grid-template-columns:1fr; }
            .settings-card { padding:20px; }
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
                <h1>System Settings</h1>
                <p>Configure your website basic information and preferences</p>
            </div>

            <?php echo $settings_message; ?>

            <!-- Settings Form -->
            <form method="POST">
                <div class="settings-card">
                    <div class="card-header">
                        <span class="icon"><i class="fas fa-sliders-h"></i></span>
                        <h2>General Settings</h2>
                    </div>
                    <p class="desc">Configure your website basic information.</p>

                    <div class="form-group">
                        <label>Site Name</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" name="admin_email" value="<?php echo htmlspecialchars($admin_email); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency">
                                <option value="USD ($)" <?php echo $currency == 'USD ($)' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="EUR (€)" <?php echo $currency == 'EUR (€)' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP (£)" <?php echo $currency == 'GBP (£)' ? 'selected' : ''; ?>>GBP (£)</option>
                                <option value="PKR (₨)" <?php echo $currency == 'PKR (₨)' ? 'selected' : ''; ?>>PKR (₨)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Timezone</label>
                            <select name="timezone">
                                <option value="UTC-5 (Eastern)" <?php echo $timezone == 'UTC-5 (Eastern)' ? 'selected' : ''; ?>>UTC-5 (Eastern)</option>
                                <option value="UTC-8 (Pacific)" <?php echo $timezone == 'UTC-8 (Pacific)' ? 'selected' : ''; ?>>UTC-8 (Pacific)</option>
                                <option value="UTC+0 (London)" <?php echo $timezone == 'UTC+0 (London)' ? 'selected' : ''; ?>>UTC+0 (London)</option>
                                <option value="UTC+5 (Pakistan)" <?php echo $timezone == 'UTC+5 (Pakistan)' ? 'selected' : ''; ?>>UTC+5 (Pakistan)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($address); ?>">
                    </div>

                    <div class="form-group">
                        <label>Footer Text</label>
                        <input type="text" name="footer_text" value="<?php echo htmlspecialchars($footer_text); ?>">
                    </div>
                </div>

                <button type="submit" name="save_settings" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        }, 3000);
    });
});
</script>

</body>
</html>