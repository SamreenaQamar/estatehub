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
    ['sql' => "SELECT COUNT(*) as total FROM users", 'var' => &$total_users],
    ['sql' => "SELECT COUNT(*) as total FROM properties", 'var' => &$total_properties],
    ['sql' => "SELECT COUNT(*) as total FROM messages", 'var' => &$total_messages],
    ['sql' => "SELECT COALESCE(SUM(price), 0) as total FROM properties WHERE status = 'Sold'", 'var' => &$total_revenue],
    ['sql' => "SELECT COUNT(*) as total FROM properties WHERE status = 'Pending'", 'var' => &$pending_count],
    ['sql' => "SELECT COUNT(*) as total FROM users WHERE status = 'active'", 'var' => &$active_users],
    ['sql' => "SELECT COUNT(*) as total FROM messages WHERE is_read = 0", 'var' => &$unread_msgs]
];

foreach ($queries as $q) {
    $result = mysqli_query($conn, $q['sql']);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $q['var'] = $row['total'] ?? 0;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EstateHub | Premium Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #f1f5f9;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --card-bg: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
            --blue: #16A366;
            --blue-light: #eff6ff;
            --green: #10b981;
            --green-light: #ecfdf5;
            --amber: #f59e0b;
            --amber-light: #fffbeb;
            --purple: #8b5cf6;
            --purple-light: #f5f3ff;
            --pink: #ec4899;
            --red: #ef4444;
            --cyan: #06b6d4;
            --radius-2xl: 24px;
            --radius-lg: 16px;
            --radius-md: 12px;
            --radius-sm: 10px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 60px rgba(0,0,0,0.12);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --font-xl: 18px;
            --font-lg: 16px;
            --font-md: 15px;
            --font-sm: 14px;
            --font-xs: 13px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            display: flex;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .sidebar {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            background: linear-gradient(180deg, #0f172a 0%, var(--sidebar-hover) 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            color: white;
            z-index: 100;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 40px rgba(0,0,0,0.15);
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 32px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            flex-shrink: 0;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            min-width: 48px;
            min-height: 48px;
            background: linear-gradient(135deg, var(--blue) 0%, var(--purple) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            box-shadow: 0 8px 25px rgba(59,130,246,0.4);
            flex-shrink: 0;
            transition: var(--transition);
        }

        .brand-icon:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 35px rgba(59,130,246,0.6);
        }

        .brand-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            white-space: nowrap;
            background: linear-gradient(135deg, #6EE7B7, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-nav {
            flex: 1;
            padding: 24px 16px;
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            padding: 8px 16px;
            margin: 20px 0 8px;
        }

        .nav-menu {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .nav-menu li a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            color: #94a3b8;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-size: var(--font-sm);
            font-weight: 500;
            transition: var(--transition);
            position: relative;
            white-space: nowrap;
        }

        .nav-menu li a:hover {
            background: var(--sidebar-hover);
            color: #e2e8f0;
        }

        .nav-menu li a.active {
            background: linear-gradient(135deg, var(--blue), #0e7a4e);
            color: white;
            font-weight: 600;
            box-shadow: 0 8px 25px rgba(59,130,246,0.35);
        }

        .nav-menu li a svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--red);
            color: white;
            border-radius: 50px;
            padding: 3px 10px;
            font-size: 10px;
            font-weight: 700;
            min-width: 24px;
            text-align: center;
        }

        .nav-badge.blue {
            background: var(--blue);
        }

        .nav-badge.green {
            background: var(--green);
        }

        .sidebar-footer {
            padding: 20px 16px;
            border-top: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
        }

        .admin-profile-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 14px;
            cursor: pointer;
            transition: var(--transition);
        }

        .admin-profile-mini:hover {
            background: rgba(255,255,255,0.1);
        }

        .admin-avatar-mini {
            width: 44px;
            height: 44px;
            min-width: 44px;
            min-height: 44px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--amber), var(--red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
            overflow: hidden;
        }

        .admin-avatar-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-info-mini h4 {
            font-size: var(--font-sm);
            font-weight: 600;
            color: #e2e8f0;
        }

        .admin-info-mini span {
            font-size: 11px;
            color: #64748b;
        }

        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
        }

        .topbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 20px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }

        .topbar-left p {
            font-size: var(--font-sm);
            color: var(--text-muted);
            margin-top: 4px;
            font-weight: 500;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .search-wrapper {
            position: relative;
        }

        .search-input {
            padding: 12px 20px 12px 46px;
            border: 2px solid var(--border);
            border-radius: 50px;
            font-size: var(--font-sm);
            width: 280px;
            background: #f8fafc;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--blue);
            background: white;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
            width: 320px;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 16px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .icon-btn {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            border: none;
            background: #f1f5f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            position: relative;
            font-size: 20px;
            color: var(--text-secondary);
        }

        .icon-btn:hover {
            background: #e2e8f0;
            color: var(--text-primary);
        }

        .icon-btn .dot {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 10px;
            height: 10px;
            background: var(--red);
            border-radius: 50%;
            border: 2px solid white;
        }

        .profile-btn-top {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 16px 6px 6px;
            background: #f8fafc;
            border-radius: 50px;
            border: 2px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
        }

        .profile-btn-top:hover {
            border-color: var(--blue);
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .profile-avatar-top {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--blue), var(--purple));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 16px;
            overflow: hidden;
        }

        .profile-avatar-top img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name-top {
            font-size: var(--font-sm);
            font-weight: 600;
            color: var(--text-primary);
        }

        .content-area {
            padding: 36px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            padding: 28px;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            border-radius: 0 0 20px 20px;
        }

        .stat-card.blue::before {
            background: linear-gradient(90deg, var(--blue), #6EE7B7);
        }

        .stat-card.green::before {
            background: linear-gradient(90deg, var(--green), #34d399);
        }

        .stat-card.purple::before {
            background: linear-gradient(90deg, var(--purple), #a78bfa);
        }

        .stat-card.amber::before {
            background: linear-gradient(90deg, var(--amber), #fbbf24);
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .stat-icon-circle {
            width: 56px;
            height: 56px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }

        .stat-icon-circle.blue-bg {
            background: var(--blue-light);
            color: var(--blue);
        }

        .stat-icon-circle.green-bg {
            background: var(--green-light);
            color: var(--green);
        }

        .stat-icon-circle.purple-bg {
            background: var(--purple-light);
            color: var(--purple);
        }

        .stat-icon-circle.amber-bg {
            background: var(--amber-light);
            color: var(--amber);
        }

        .stat-trend {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
        }

        .stat-trend.up {
            background: var(--green-light);
            color: #059669;
        }

        .stat-trend.down {
            background: #fef2f2;
            color: #dc2626;
        }

        .stat-value {
            font-size: 42px;
            font-weight: 900;
            color: var(--text-primary);
            line-height: 1;
            letter-spacing: -1px;
        }

        .stat-label {
            font-size: var(--font-sm);
            color: var(--text-secondary);
            margin-top: 8px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .chart-card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            padding: 32px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .chart-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .chart-card-header h2 {
            font-size: var(--font-xl);
            font-weight: 700;
            color: var(--text-primary);
        }

        .chart-period-select {
            padding: 8px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: var(--font-xs);
            font-weight: 600;
            color: var(--text-secondary);
            background: white;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
        }

        .bar-chart-container {
            position: relative;
            padding-left: 50px;
            padding-bottom: 35px;
        }

        .y-axis-labels {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 35px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .y-axis-labels span {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
            text-align: right;
            width: 42px;
        }

        .bars-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 20px;
            height: 220px;
            border-bottom: 2px solid var(--border);
            border-left: 2px solid var(--border);
            padding-left: 16px;
        }

        .bar-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
            gap: 8px;
        }

        .bar {
            width: 100%;
            max-width: 50px;
            background: linear-gradient(180deg, var(--blue) 0%, #0a5c3a 100%);
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            transition: var(--transition);
            min-height: 6px;
            position: relative;
        }

        .bar:hover {
            filter: brightness(1.2);
            transform: scaleY(1.04);
            transform-origin: bottom;
            box-shadow: 0 4px 20px rgba(59,130,246,0.4);
        }

        .bar-label {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .donut-container {
            display: flex;
            align-items: center;
            gap: 40px;
        }

        .donut-chart {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .donut-legend {
            display: flex;
            flex-direction: column;
            gap: 18px;
            flex: 1;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .legend-dot {
            width: 16px;
            height: 16px;
            border-radius: 6px;
            flex-shrink: 0;
        }

        .legend-label {
            font-size: var(--font-sm);
            color: var(--text-secondary);
            flex: 1;
            font-weight: 500;
        }

        .legend-value {
            font-weight: 700;
            font-size: var(--font-md);
            color: var(--text-primary);
        }

        .bottom-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .data-card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            padding: 28px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .data-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .data-card-header h2 {
            font-size: var(--font-lg);
            font-weight: 700;
            color: var(--text-primary);
        }

        .view-all-btn {
            font-size: 12px;
            font-weight: 600;
            color: var(--blue);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .view-all-btn:hover {
            background: var(--blue-light);
        }

        .data-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .data-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            transition: var(--transition);
            cursor: pointer;
        }

        .data-list-item:hover {
            background: #f8fafc;
        }

        .data-user-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .data-user-avatar {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
            color: white;
        }

        .avatar-blue {
            background: linear-gradient(135deg, var(--blue), #6EE7B7);
        }

        .avatar-green {
            background: linear-gradient(135deg, var(--green), #34d399);
        }

        .avatar-purple {
            background: linear-gradient(135deg, var(--purple), #a78bfa);
        }

        .avatar-pink {
            background: linear-gradient(135deg, var(--pink), #f472b6);
        }

        .avatar-amber {
            background: linear-gradient(135deg, var(--amber), #fbbf24);
        }

        .avatar-cyan {
            background: linear-gradient(135deg, var(--cyan), #22d3ee);
        }

        .data-user-details h4 {
            font-size: var(--font-sm);
            font-weight: 600;
            color: var(--text-primary);
        }

        .data-user-details span {
            font-size: 12px;
            color: var(--text-muted);
            display: block;
            margin-top: 2px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-active {
            background: var(--green);
            box-shadow: 0 0 8px rgba(16,185,129,0.4);
        }

        .status-pending {
            background: var(--amber);
            box-shadow: 0 0 8px rgba(245,158,11,0.4);
        }

        .status-blocked {
            background: var(--red);
            box-shadow: 0 0 8px rgba(239,68,68,0.4);
        }

        .message-preview {
            font-size: 12px;
            color: var(--text-muted);
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--blue);
            flex-shrink: 0;
        }

        @media (max-width: 1600px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .bottom-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 1200px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .stats-grid,
            .bottom-row {
                grid-template-columns: 1fr;
            }
            .content-area {
                padding: 20px;
            }
            .topbar {
                padding: 16px 20px;
            }
            .search-input {
                width: 180px;
            }
            .search-input:focus {
                width: 220px;
            }
            .stat-value {
                font-size: 32px;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-in {
            animation: fadeInUp 0.5s ease-out forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #334155;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="../index.php" class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-home"></i>
        </div>
        <span class="brand-name">EstateHub</span>
    </a>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Menu</div>
        <ul class="nav-menu">
            <li>
                <a href="index.php" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="3"/>
                        <path d="M3 9h18M9 21V9"/>
                    </svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="manage-users.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="5"/>
                        <path d="M20 21a8 8 0 1 0-16 0"/>
                    </svg>
                    Users
                    <span class="nav-badge"><?php echo $total_users; ?></span>
                </a>
            </li>
            <li>
                <a href="manage-properties.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Properties
                    <?php if ($pending_count > 0): ?>
                        <span class="nav-badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="manage-messages.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Messages
                    <?php if ($unread_msgs > 0): ?>
                        <span class="nav-badge blue"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="nav-section-title">Analytics</div>
        <ul class="nav-menu">
            <li>
                <a href="reports.php">
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
                <a href="settings.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Settings
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-profile-mini" onclick="window.location.href='profile.php'">
            <div class="admin-avatar-mini">
                <?php if (!empty($profile_pic_path)): ?>
                    <img src="<?php echo $profile_pic_path; ?>" alt="Admin" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <i class="fas fa-crown" style="display:none;"></i>
                <?php else: ?>
                    <i class="fas fa-crown"></i>
                <?php endif; ?>
            </div>
            <div class="admin-info-mini">
                <h4><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h4>
                <span>Super Admin</span>
            </div>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" style="margin-left:auto;">
                <path d="M9 18l6-6-6-6"/>
            </svg>
        </div>
    </div>
</aside>

<main class="main-content">
    <header class="topbar">
        <div class="topbar-left">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>! Here's your real estate insights.</p>
        </div>
        <div class="topbar-right">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search anything...">
            </div>
            <div class="topbar-actions">
                <button class="icon-btn" title="Notifications">
                    <i class="far fa-bell"></i>
                    <span class="dot"></span>
                </button>
                <button class="icon-btn" title="Messages">
                    <i class="far fa-envelope"></i>
                    <?php if ($unread_msgs > 0): ?>
                        <span class="dot"></span>
                    <?php endif; ?>
                </button>
                <div class="profile-btn-top" onclick="window.location.href='profile.php'">
                    <div class="profile-avatar-top">
                        <?php if (!empty($profile_pic_path)): ?>
                            <img src="<?php echo $profile_pic_path; ?>" alt="Admin" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <i class="fas fa-user-shield" style="display:none;"></i>
                        <?php else: ?>
                            <i class="fas fa-user-shield"></i>
                        <?php endif; ?>
                    </div>
                    <span class="profile-name-top"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                    <i class="fas fa-chevron-down" style="font-size:10px; color:#94a3b8;"></i>
                </div>
            </div>
        </div>
    </header>

    <div class="content-area">
        <div class="stats-grid">
            <div class="stat-card blue animate-in" onclick="window.location.href='manage-users.php'">
                <div class="stat-card-header">
                    <div class="stat-icon-circle blue-bg"><i class="fas fa-users"></i></div>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 12.5%</span>
                </div>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card green animate-in" onclick="window.location.href='manage-properties.php'">
                <div class="stat-card-header">
                    <div class="stat-icon-circle green-bg"><i class="fas fa-building"></i></div>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 8.3%</span>
                </div>
                <div class="stat-value"><?php echo number_format($total_properties); ?></div>
                <div class="stat-label">Total Properties</div>
            </div>

            <div class="stat-card purple animate-in" onclick="window.location.href='manage-messages.php'">
                <div class="stat-card-header">
                    <div class="stat-icon-circle purple-bg"><i class="fas fa-comments"></i></div>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 24.1%</span>
                </div>
                <div class="stat-value"><?php echo number_format($total_messages); ?></div>
                <div class="stat-label">Total Messages</div>
            </div>

            <div class="stat-card amber animate-in" onclick="window.location.href='reports.php'">
                <div class="stat-card-header">
                    <div class="stat-icon-circle amber-bg"><i class="fas fa-dollar-sign"></i></div>
                    <span class="stat-trend up"><i class="fas fa-arrow-up"></i> 15.7%</span>
                </div>
                <div class="stat-value">$<?php echo number_format($total_revenue, 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card animate-in">
                <div class="chart-card-header">
                    <h2><i class="fas fa-chart-bar" style="color: var(--blue); margin-right:10px;"></i>Property Views (Last 7 Days)</h2>
                    <select class="chart-period-select">
                        <option>Weekly</option>
                        <option>Monthly</option>
                        <option>Yearly</option>
                    </select>
                </div>
                <div class="bar-chart-container">
                    <div class="y-axis-labels">
                        <span>500</span><span>400</span><span>300</span><span>200</span><span>100</span><span>0</span>
                    </div>
                    <div class="bars-wrapper">
                        <?php foreach ($chart_views as $i => $value): ?>
                            <div class="bar-item">
                                <div class="bar" style="height: <?php echo max(4, ($value / 500) * 220); ?>px;" title="<?php echo $value; ?> views"></div>
                                <span class="bar-label"><?php echo $chart_labels[$i]; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="chart-card animate-in">
                <div class="chart-card-header">
                    <h2><i class="fas fa-chart-pie" style="color: var(--purple); margin-right:10px;"></i>Property Types</h2>
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

        <div class="bottom-row">
            <div class="data-card animate-in">
                <div class="data-card-header">
                    <h2><i class="fas fa-user-friends" style="color: var(--blue); margin-right:8px;"></i>Recent Users</h2>
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

            <div class="data-card animate-in">
                <div class="data-card-header">
                    <h2><i class="fas fa-home" style="color: var(--green); margin-right:8px;"></i>Latest Properties</h2>
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

            <div class="data-card animate-in">
                <div class="data-card-header">
                    <h2><i class="fas fa-envelope" style="color: var(--purple); margin-right:8px;"></i>Unread Messages</h2>
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
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var bars = document.querySelectorAll('.bar');
    var activeTooltip = null;

    bars.forEach(function(bar) {
        bar.addEventListener('mouseenter', function() {
            var tooltip = document.createElement('div');
            tooltip.textContent = this.getAttribute('title');
            tooltip.style.cssText = 'position:fixed;background:#0f172a;color:white;padding:8px 14px;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;pointer-events:none;font-family:Inter,sans-serif;box-shadow:0 8px 25px rgba(0,0,0,0.3);';
            document.body.appendChild(tooltip);

            var rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
            tooltip.style.top = rect.top - 40 + 'px';

            activeTooltip = tooltip;
        });

        bar.addEventListener('mouseleave', function() {
            if (activeTooltip) {
                activeTooltip.remove();
                activeTooltip = null;
            }
        });
    });

    var searchInput = document.querySelector('.search-input');
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            var query = this.value.trim();
            if (query) {
                window.location.href = 'search.php?q=' + encodeURIComponent(query);
            }
        }
    });
});
</script>

</body>
</html>