<?php
$page_title = 'Seller Dashboard';
require_once __DIR__ . '/includes/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if($_SESSION['user_type'] != 'seller') {
    if($_SESSION['user_type'] == 'user' || $_SESSION['user_type'] == 'buyer') {
        header("Location: buyer-dashboard.php");
    } elseif($_SESSION['user_type'] == 'admin') {
        header("Location: admin/index.php");
    }
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Seller';

// Check for profile_pic column
$pic_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($pic_check) > 0;

// Fetch seller profile data
$profile_query = "SELECT u.*, 
                  COUNT(DISTINCT p.id) as total_properties,
                  SUM(CASE WHEN p.status='Active' THEN 1 ELSE 0 END) as active_properties,
                  SUM(p.views) as total_views,
                  COUNT(DISTINCT m.id) as total_messages
                  FROM users u 
                  LEFT JOIN properties p ON u.id = p.user_id 
                  LEFT JOIN messages m ON u.id = m.receiver_id AND m.is_read = 0
                  WHERE u.id = $user_id 
                  GROUP BY u.id";
$profile_result = mysqli_query($conn, $profile_query);
$profile = mysqli_fetch_assoc($profile_result);

// Stats
$total_properties = (int)($profile['total_properties'] ?? 0);
$active_properties = (int)($profile['active_properties'] ?? 0);
$total_views = (int)($profile['total_views'] ?? 0);
$total_messages = (int)($profile['total_messages'] ?? 0);

// ============================================================
// PROFILE PICTURE PATH
// ============================================================
$profile_pic_path = '';

if ($has_pic_column && !empty($profile['profile_pic'])) {
    $pic_file = "uploads/profiles/" . $profile['profile_pic'];
    if (file_exists($pic_file)) {
        $profile_pic_path = $pic_file . '?t=' . time();
    }
}

if (empty($profile_pic_path) && isset($_SESSION['profile_pic'])) {
    $pic_file = "uploads/profiles/" . $_SESSION['profile_pic'];
    if (file_exists($pic_file)) {
        $profile_pic_path = $pic_file . '?t=' . time();
    }
}

if (empty($profile_pic_path)) {
    $found_files = glob("uploads/profiles/seller_" . $user_id . "_*");
    if (!empty($found_files)) {
        $profile_pic_path = $found_files[0] . '?t=' . time();
        $_SESSION['profile_pic'] = basename($found_files[0]);
        if ($has_pic_column) {
            $safe_filename = mysqli_real_escape_string($conn, basename($found_files[0]));
            mysqli_query($conn, "UPDATE users SET profile_pic = '$safe_filename' WHERE id = $user_id");
        }
    }
}

// Month-over-month growth
$start_this_month = date('Y-m-01 00:00:00');
$props_before = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id AND created_at < '$start_this_month'"))['c'] ?? 0;
$properties_growth = $props_before > 0 ? round((($total_properties - $props_before) / $props_before) * 100) : ($total_properties > 0 ? 100 : 0);

$active_before = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id AND status='Active' AND created_at < '$start_this_month'"))['c'] ?? 0;
$active_growth = $active_before > 0 ? round((($active_properties - $active_before) / $active_before) * 100) : ($active_properties > 0 ? 100 : 0);

// Recent properties (4) with images
$recent_props = [];
$prop_result = mysqli_query($conn, "SELECT * FROM properties WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 4");
if($prop_result) {
    while($row = mysqli_fetch_assoc($prop_result)) {
        $first_img = '';
        if(!empty($row['image_main']) && file_exists($row['image_main'])) {
            $first_img = $row['image_main'];
        } elseif(!empty($row['images'])) {
            $imgs = explode(',', $row['images']);
            $first_img = trim($imgs[0]);
        }
        if(empty($first_img) || !file_exists($first_img)) {
            $types = ['house', 'apartment', 'villa', 'plot', 'commercial'];
            $t = strtolower($row['property_type'] ?? 'house');
            $t = in_array($t, $types) ? $t : 'house';
            $first_img = "https://source.unsplash.com/800x600/?" . $t . ",building&sig=" . $row['id'];
        }
        $row['thumbnail'] = $first_img;
        $recent_props[] = $row;
    }
}

// Recent messages (4)
$recent_msgs = [];
$msg_result = mysqli_query($conn, "SELECT m.*, u.full_name as sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.receiver_id=$user_id ORDER BY m.created_at DESC LIMIT 4");
if($msg_result) {
    while($row = mysqli_fetch_assoc($msg_result)) {
        $recent_msgs[] = $row;
    }
}

// Helper: time ago
function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if($diff < 60) return 'Just now';
    if($diff < 3600) return floor($diff / 60) . ' min ago';
    if($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if($diff < 172800) return 'Yesterday';
    return floor($diff / 86400) . ' days ago';
}

$hour = (int)date('G');
if($hour < 12) $greeting = 'Good Morning';
elseif($hour < 17) $greeting = 'Good Afternoon';
else $greeting = 'Good Evening';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        /* ===== SIDEBAR ===== */
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

        .logo { padding:0 4px 20px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .logo a { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo-icon { font-size:30px; color:#0E7A4E; filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3)); }
        .logo-text { font-size:29px; font-weight:800; letter-spacing:-0.5px; color:#ffffff; position:relative; }
        .logo-text::after { content:''; position:absolute; left:0; bottom:-10px; width:45px; height:3px; background:#0E7A4E; border-radius:10px; }

        .sidebar-label { font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; padding: 8px 12px; margin-bottom: 6px; }
        .sidebar-menu { list-style:none; padding:0; margin-bottom:12px; }
        .sidebar-menu li { margin-bottom:6px; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:12px 16px; width:fit-content; min-width:165px; color:#b8c2cc; text-decoration:none; border-radius:12px; font-size:14px; font-weight:600; transition:all .3s ease; }
        .sidebar-menu a:hover { background:linear-gradient(135deg,#0E7A4E,#16a34a); color:#fff; transform:translateX(6px); box-shadow:0 8px 20px rgba(14,122,78,.35); }
        .sidebar-menu a svg { width:20px; height:20px; transition:.3s; }
        .sidebar-menu a:hover svg { transform:scale(1.15); }
        .sidebar-menu a.active { background:linear-gradient(135deg,#0E7A4E,#16a34a); color:#fff; box-shadow:0 8px 20px rgba(14,122,78,.35); }
        .sidebar-menu a.active svg { transform:scale(1.1); }
        .badge { margin-left: auto; background: #ef4444; color: white; border-radius: 50px; padding: 2px 10px; font-size: 10px; font-weight: 700; min-width: 22px; text-align: center; }
        .logout-link { color:#b8c2cc !important; }
        .logout-link:hover { background:linear-gradient(135deg,#dc2626,#ef4444) !important; color:#fff !important; box-shadow:0 8px 20px rgba(220,38,38,.35) !important; }

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
        .topbar-search input { width:100%; padding:9px 40px 9px 16px; border-radius:10px; border:1px solid #e9ecef; background:#f8fafc; font-size:14px; outline:none; transition:0.2s; }
        .topbar-search input:focus { border-color:#0E7A4E; background:#fff; box-shadow:0 0 0 3px rgba(14,122,78,0.1); }
        .topbar-search svg { position:absolute; right:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#adb5bd; }

        .topbar-actions { display:flex; align-items:center; gap:10px; }
        .user-chip { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .user-avatar { width:36px; height:36px; border-radius:10px; background:#0E7A4E; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; overflow:hidden; flex-shrink:0; }
        .user-avatar img { width:100%; height:100%; object-fit:cover; }
        .user-info { display:flex; flex-direction:column; line-height:1.2; }
        .user-name { font-size:14px; font-weight:700; color:#0b1a2e; }
        .user-role { font-size:12px; color:#6b7a8f; }

        /* ===== MAIN CONTENT ===== */
        .main-content { flex:1; overflow-y:auto; }
        .content-inner { padding:28px 32px 40px; }

        /* ===== WELCOME BANNER (more professional) ===== */
        .welcome-banner {
            position:relative;
            overflow:hidden;
            border-radius:24px;
            min-height:180px;
            padding:35px 40px;
            margin-bottom:28px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            flex-wrap:wrap;
            background:
                linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 60%, rgba(0,0,0,0.05) 100%),
                url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            box-shadow:0 8px 30px rgba(0,0,0,0.08);
        }
        .welcome-banner::after {
            content:'';
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:linear-gradient(90deg, rgba(14,122,78,0.15) 0%, transparent 60%);
            pointer-events:none;
        }
        .welcome-banner > * { position:relative; z-index:1; }
        .welcome-banner h1 { color:#fff; font-size:24px; font-weight:700; margin-bottom:4px; letter-spacing:-0.3px; }
        .welcome-banner p { color:#fff; font-size:16px; opacity:0.9; margin-bottom:8px; font-weight:400; }
        .welcome-meta { display:flex; align-items:center; gap:20px; color:#fff; font-size:14px; opacity:0.85; }
        .welcome-meta span { display:flex; align-items:center; gap:6px; }
        .welcome-meta svg { width:16px; height:16px; }
        .add-property-btn {
            display:inline-flex;
            align-items:center;
            gap:8px;
            background:#0E7A4E;
            color:#fff;
            padding:10px 24px;
            border-radius:12px;
            font-weight:700;
            font-size:14px;
            text-decoration:none;
            transition:0.25s;
            white-space:nowrap;
            box-shadow:0 4px 12px rgba(14,122,78,0.3);
        }
        .add-property-btn:hover { background:#0a5c3a; transform:translateY(-2px); box-shadow:0 8px 25px rgba(14,122,78,0.4); }

        /* ===== STATS GRID ===== */
        .stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:26px; }
        .stat-card {
            background:#fff;
            border-radius:16px;
            padding:20px 18px;
            border:1px solid #edf2f7;
            transition:all .25s ease;
            position:relative;
            overflow:hidden;
        }
        .stat-card::before {
            content:'';
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:4px;
            background:linear-gradient(90deg, #0E7A4E, #16a34a);
            opacity:0.8;
        }
        .stat-card:hover {
            transform:translateY(-6px);
            border-color:#0E7A4E;
            box-shadow:0 16px 30px rgba(14,122,78,0.10);
        }
        .stat-icon {
            width:44px;
            height:44px;
            border-radius:12px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-bottom:12px;
            font-size:20px;
        }
        .stat-icon.green { background:rgba(14,122,78,0.10); color:#0E7A4E; }
        .stat-icon.blue { background:rgba(59,130,246,0.10); color:#3b82f6; }
        .stat-icon.orange { background:rgba(251,146,60,0.10); color:#f97316; }
        .stat-icon.purple { background:rgba(139,92,246,0.10); color:#8b5cf6; }
        .stat-icon svg { width:24px; height:24px; stroke:currentColor; fill:none; stroke-width:2; }
        .stat-number { font-size:28px; font-weight:800; color:#0b1a2e; line-height:1; margin-bottom:6px; letter-spacing:-0.5px; }
        .stat-label { font-size:14px; color:#64748b; margin-bottom:6px; font-weight:500; }
        .stat-growth {
            font-size:12px;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:4px;
            background: #f1f5f9;
            padding:2px 10px;
            border-radius:20px;
            display:inline-flex;
        }
        .stat-growth.up { color:#0E7A4E; background:#e6f7ed; }
        .stat-growth.down { color:#ef4444; background:#fee2e2; }
        .stat-growth.neutral { color:#94a3b8; background:#f1f5f9; }

        /* ===== CONTENT GRID ===== */
        .content-grid { display:grid; grid-template-columns:1.5fr 1fr; gap:20px; }
        .content-card {
            background:#fff;
            border-radius:18px;
            padding:20px 22px;
            border:1px solid #edf2f7;
            box-shadow:0 4px 12px rgba(15,23,42,0.02);
            transition:box-shadow .3s;
        }
        .content-card:hover { box-shadow:0 12px 30px rgba(15,23,42,0.06); }
        .card-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:16px;
            padding-bottom:12px;
            border-bottom:1px solid #edf2f7;
        }
        .card-header h3 { font-size:18px; font-weight:700; color:#0b1a2e; }
        .card-header a {
            color:#0E7A4E;
            text-decoration:none;
            font-size:13px;
            font-weight:700;
            transition:color .2s;
        }
        .card-header a:hover { color:#0a5c3a; text-decoration:underline; }

        /* ===== PROPERTY ITEMS ===== */
        .property-item {
            display:flex;
            gap:14px;
            padding:10px 12px;
            border-radius:12px;
            transition:.2s;
            margin-bottom:8px;
            align-items:center;
            background:#fafcfc;
        }
        .property-item:hover { background:#f0f6f2; }
        .property-item .thumb {
            width:64px;
            height:64px;
            border-radius:12px;
            overflow:hidden;
            flex-shrink:0;
            background:#e5e7eb;
            box-shadow:0 2px 6px rgba(0,0,0,0.04);
        }
        .property-item .thumb img {
            width:100%;
            height:100%;
            object-fit:cover;
            transition:transform .3s;
        }
        .property-item:hover .thumb img { transform:scale(1.03); }
        .property-item .info { flex:1; min-width:0; }
        .property-item .info h4 {
            font-size:15px;
            font-weight:700;
            color:#0b1a2e;
            margin-bottom:4px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .property-item .meta {
            font-size:12px;
            color:#64748b;
            display:flex;
            align-items:center;
            gap:6px;
            flex-wrap:wrap;
        }
        .status-badge {
            padding:2px 12px;
            border-radius:30px;
            font-size:10px;
            font-weight:700;
            text-transform:capitalize;
        }
        .status-active { background:#dcfce7; color:#15803d; }
        .status-pending { background:#fef3c7; color:#b45309; }
        .status-sold { background:#e5e7eb; color:#374151; }
        .property-item .price {
            color:#0E7A4E;
            font-weight:800;
            font-size:15px;
            white-space:nowrap;
            background:#e6f7ed;
            padding:2px 12px;
            border-radius:20px;
        }

        /* ===== MESSAGE ITEMS ===== */
        .message-item {
            display:flex;
            align-items:flex-start;
            gap:14px;
            padding:12px 0;
            border-bottom:1px solid #f1f5f9;
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
            box-shadow:0 2px 8px rgba(14,122,78,0.15);
        }
        .msg-body { flex:1; min-width:0; }
        .msg-name { font-size:14px; font-weight:700; color:#0b1a2e; margin-bottom:2px; }
        .msg-preview {
            font-size:13px;
            color:#64748b;
            overflow:hidden;
            text-overflow:ellipsis;
            white-space:nowrap;
            max-width:100%;
        }
        .msg-time { font-size:11px; color:#94a3b8; white-space:nowrap; margin-top:2px; }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align:center;
            padding:35px 20px;
            color:#94a3b8;
        }
        .empty-state svg {
            width:56px;
            height:56px;
            color:#d1d9e6;
            margin-bottom:12px;
            opacity:0.7;
        }
        .empty-state p { color:#64748b; font-size:14px; margin-bottom:8px; }
        .empty-state a { color:#0E7A4E; text-decoration:none; font-weight:700; }
        .empty-state a:hover { text-decoration:underline; }

        /* ===== RESPONSIVE ===== */
        @media (max-width:1200px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
            .content-grid { grid-template-columns:1fr; }
        }
        @media (max-width:768px) {
            .content-inner { padding:18px; }
            .topbar { padding:12px 18px; flex-wrap:wrap; }
            .topbar-search { max-width:100%; order:3; flex-basis:100%; margin-top:8px; }
            .stats-grid { grid-template-columns:1fr 1fr; gap:12px; }
            .welcome-banner { flex-direction:column; align-items:flex-start; padding:25px 20px; }
            .welcome-banner h1 { font-size:22px; }
            .user-info { display:none; }
            .property-item { flex-wrap:wrap; }
            .property-item .price { margin-left:auto; }
        }
        @media (max-width:480px) { .stats-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sellerSidebar">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-home logo-icon"></i>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>
        <div class="sidebar-label">Main Menu</div>
        <ul class="sidebar-menu">
            <li><a href="seller-dashboard.php" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                Dashboard
            </a></li>
            <li><a href="my-properties.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                My Properties
            </a></li>
            <li><a href="add-property.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Add Property
            </a></li>
            <li><a href="messages.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Messages
                <?php if($total_messages > 0): ?><span class="badge"><?php echo $total_messages; ?></span><?php endif; ?>
            </a></li>
            <li><a href="wishlist.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                Wishlist
            </a></li>
        </ul>

        <div class="sidebar-label">Account</div>
        <ul class="sidebar-menu">
            <li><a href="profile-settings.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg>
                Profile Settings
            </a></li>
            <li><a href="settings.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
            </a></li>
            <li><a href="logout.php" class="logout-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a></li>
        </ul>
    </aside>

    <!-- MAIN -->
    <div class="main-content">

        <!-- TOP BAR -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('sellerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-search">
                <input type="text" placeholder="Search anything...">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            <div class="topbar-actions">
                <a href="profile-settings.php" class="user-chip">
                    <div class="user-avatar">
                        <?php if(!empty($profile_pic_path)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="<?php echo htmlspecialchars($user_name); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Seller</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="content-inner">

            <!-- Welcome Banner (professional) -->
            <div class="welcome-banner">
                <div>
                    <h1>👋 <?php echo $greeting; ?>, <?php echo htmlspecialchars($user_name); ?>!</h1>
                    <p>Here's what's happening with your properties today.</p>
                    <div class="welcome-meta">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?php echo date('l, d F Y'); ?>
                        </span>
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <?php echo date('h:i A'); ?>
                        </span>
                    </div>
                </div>
                <a href="add-property.php" class="add-property-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" width="18" height="18"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Property
                </a>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div class="stat-number"><?php echo $total_properties; ?></div>
                    <div class="stat-label">Total Properties</div>
                    <div class="stat-growth <?php echo $properties_growth > 0 ? 'up' : ($properties_growth < 0 ? 'down' : 'neutral'); ?>">
                        <?php echo $properties_growth > 0 ? '↗' : ($properties_growth < 0 ? '↘' : '–'); ?>
                        <?php echo abs($properties_growth); ?>% from last month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="stat-number"><?php echo $active_properties; ?></div>
                    <div class="stat-label">Active Listings</div>
                    <div class="stat-growth <?php echo $active_growth > 0 ? 'up' : ($active_growth < 0 ? 'down' : 'neutral'); ?>">
                        <?php echo $active_growth > 0 ? '↗' : ($active_growth < 0 ? '↘' : '–'); ?>
                        <?php echo abs($active_growth); ?>% from last month
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div class="stat-number"><?php echo $total_messages; ?></div>
                    <div class="stat-label">Unread Messages</div>
                    <div class="stat-growth neutral">Check your inbox</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </div>
                    <div class="stat-number"><?php echo number_format($total_views); ?></div>
                    <div class="stat-label">Total Views</div>
                    <div class="stat-growth neutral">Across all listings</div>
                </div>
            </div>

            <!-- Recent Properties + Messages -->
            <div class="content-grid">

                <div class="content-card">
                    <div class="card-header">
                        <h3>Recent Properties</h3>
                        <a href="my-properties.php">View All →</a>
                    </div>
                    <?php if(empty($recent_props)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            <p>No properties listed yet</p>
                            <a href="add-property.php">Add your first property →</a>
                        </div>
                    <?php else: foreach($recent_props as $p):
                        $status = strtolower($p['status'] ?? 'active');
                        $loc = $p['location'] ?? ($p['city'] ?? '');
                    ?>
                        <div class="property-item">
                            <div class="thumb">
                                <img src="<?php echo htmlspecialchars($p['thumbnail']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy">
                            </div>
                            <div class="info">
                                <h4><?php echo htmlspecialchars($p['title']); ?></h4>
                                <div class="meta">
                                    <span><?php echo htmlspecialchars($loc); ?></span>
                                    <span>•</span>
                                    <span><?php echo $p['bedrooms'] ?? 0; ?> Beds</span>
                                    <span>•</span>
                                    <span><?php echo $p['bathrooms'] ?? 0; ?> Baths</span>
                                    <span class="status-badge status-<?php echo $status; ?>"><?php echo htmlspecialchars($p['status']); ?></span>
                                </div>
                            </div>
                            <div class="price">PKR <?php echo number_format($p['price']); ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3>Recent Messages</h3>
                        <a href="messages.php">View All →</a>
                    </div>
                    <?php if(empty($recent_msgs)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            <p>No messages yet</p>
                        </div>
                    <?php else: foreach($recent_msgs as $m): ?>
                        <div class="message-item">
                            <div class="msg-avatar"><?php echo strtoupper(substr($m['sender_name'], 0, 1)); ?></div>
                            <div class="msg-body">
                                <div class="msg-name"><?php echo htmlspecialchars($m['sender_name']); ?></div>
                                <div class="msg-preview"><?php echo htmlspecialchars(substr($m['message'], 0, 60)); ?></div>
                            </div>
                            <div class="msg-time"><?php echo time_ago($m['created_at']); ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

            </div>

        </div>
    </div>
</div>
</body>
</html>