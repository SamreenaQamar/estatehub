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

// Recent properties (fetch up to 6 for a nice grid)
$recent_props = [];
$prop_result = mysqli_query($conn, "SELECT * FROM properties WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 6");
if($prop_result) {
    while($row = mysqli_fetch_assoc($prop_result)) {
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

// ============================================================
// getPropertyImages() - Uses Unsplash real estate images
// ============================================================
if (!function_exists('getPropertyImages')) {
    function getPropertyImages($type, $id) {
        $seed = $id;
        $imagePool = [
            'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600573472550-8090b5e0745e?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600566753086-00f18f6b0050?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600573472591-ee6b68d14c68?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1605276374104-dee2a0ed3cd6?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1605146769289-440113cc3d00?w=600&h=400&fit=crop',
            'https://images.unsplash.com/photo-1600585153490-76fb20a32601?w=600&h=400&fit=crop',
        ];
        $images = [];
        $start = ($seed % count($imagePool));
        for ($i = 0; $i < 4; $i++) {
            $index = ($start + $i) % count($imagePool);
            $images[] = $imagePool[$index];
        }
        return $images;
    }
}

// Helper to format price
function formatCardPrice($price, $purpose) {
    if ($price > 0) {
        $formatted = number_format($price / 1000000, 1);
        $html = 'PKR ' . $formatted . 'M';
        if ($purpose == 'Rent') {
            $html .= ' <span class="per-month">/ Month</span>';
        }
        return $html;
    }
    return '<span class="contact-price">Contact for Price</span>';
}
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

        /* ===== SIDEBAR - UNCHANGED ===== */
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

        /* ===== TOP BAR - UNCHANGED ===== */
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

        /* ===== PROFESSIONAL WELCOME BANNER ===== */
        .welcome-banner {
            position:relative;
            overflow:hidden;
            border-radius:20px;
            min-height:200px;
            padding:40px 45px;
            margin-bottom:28px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:30px;
            flex-wrap:wrap;
            background: linear-gradient(135deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.15) 100%),
                        url('https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            box-shadow:0 10px 40px rgba(0,0,0,0.12);
        }
        .welcome-banner::before {
            content:'';
            position:absolute;
            top:0;left:0;right:0;bottom:0;
            background:linear-gradient(135deg, rgba(14,122,78,0.2) 0%, transparent 50%);
            pointer-events:none;
        }
        .welcome-banner > * { position:relative; z-index:1; }
        .welcome-text h1 {
            color:#fff;
            font-size:28px;
            font-weight:700;
            margin-bottom:6px;
            letter-spacing:-0.5px;
            line-height:1.2;
        }
        .welcome-text h1 .wave { display:inline-block; animation:wave 2s infinite; transform-origin:70% 70%; }
        @keyframes wave { 0%,100%{transform:rotate(0deg);} 25%{transform:rotate(15deg);} 50%{transform:rotate(-5deg);} 75%{transform:rotate(10deg);} }
        .welcome-text p {
            color:rgba(255,255,255,0.9);
            font-size:15px;
            margin-bottom:14px;
            font-weight:400;
            line-height:1.5;
            max-width:400px;
        }
        .welcome-meta { display:flex; align-items:center; gap:20px; color:rgba(255,255,255,0.8); font-size:13px; font-weight:500; }
        .welcome-meta span { display:flex; align-items:center; gap:7px; }
        .welcome-meta i { font-size:14px; opacity:0.8; }

        .add-property-btn {
            display:inline-flex;
            align-items:center;
            gap:10px;
            background:#fff;
            color:#0E7A4E;
            padding:12px 28px;
            border-radius:12px;
            font-weight:700;
            font-size:14px;
            text-decoration:none;
            transition:all 0.3s ease;
            white-space:nowrap;
            box-shadow:0 8px 25px rgba(0,0,0,0.2);
            backdrop-filter:blur(10px);
        }
        .add-property-btn:hover { 
            background:#0E7A4E;
            color:#fff;
            transform:translateY(-2px); 
            box-shadow:0 12px 35px rgba(14,122,78,0.4);
        }

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
            color: #1e293b; /* Professional dark/black color for icons */
        }
        .stat-icon i {
            font-size: 22px;
            color: #1e293b; /* Ensure icons are dark */
        }
        .stat-icon.green { background:rgba(14,122,78,0.10); }
        .stat-icon.blue { background:rgba(59,130,246,0.10); }
        .stat-icon.orange { background:rgba(251,146,60,0.10); }
        .stat-icon.purple { background:rgba(139,92,246,0.10); }
        
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

        /* ===== SIDE-BY-SIDE CONTENT GRID ===== */
        .content-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
        }

        .content-card {
            background:#fff;
            border-radius:18px;
            border:1px solid #edf2f7;
            overflow:hidden;
            display:flex;
            flex-direction:column;
        }
        .card-header {
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:18px 22px;
            border-bottom:1px solid #f1f5f9;
        }
        .card-header h2 {
            font-size:16px;
            font-weight:700;
            color:#0f172a;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .card-header h2 i { color:#0E7A4E; font-size:14px; }
        
        /* ===== PROFESSIONAL VIEW ALL BUTTON ===== */
        .view-all-btn {
            font-size: 12.5px;
            font-weight: 600;
            color: #0E7A4E;
            text-decoration: none;
            padding: 6px 16px;
            border-radius: 8px;
            border: 1.5px solid #0E7A4E;
            background: transparent;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .view-all-btn:hover { 
            background: #0E7A4E; 
            color: #ffffff; 
            box-shadow: 0 4px 12px rgba(14, 122, 78, 0.3);
            border-color: #0E7A4E;
        }

        .card-body-scroll {
            flex:1;
            overflow-y:auto;
            max-height:420px;
            padding:12px 16px;
        }
        .card-body-scroll::-webkit-scrollbar { width:4px; }
        .card-body-scroll::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:10px; }

        /* Property List Items */
        .prop-list-item {
            display:flex;
            align-items:center;
            gap:14px;
            padding:12px 8px;
            border-radius:10px;
            transition:all 0.2s ease;
            cursor:pointer;
            border-bottom:1px solid #f8fafc;
        }
        .prop-list-item:last-child { border-bottom:none; }
        .prop-list-item:hover { background:#f8fafc; }
        .prop-thumb {
            width:65px;
            height:50px;
            border-radius:8px;
            object-fit:cover;
            flex-shrink:0;
            background:#f1f5f9;
        }
        .prop-info { flex:1; min-width:0; }
        .prop-info h4 {
            font-size:13.5px;
            font-weight:600;
            color:#0f172a;
            margin-bottom:3px;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .prop-info .prop-meta {
            font-size:11.5px;
            color:#94a3b8;
        }
        .prop-status {
            padding:4px 10px;
            border-radius:20px;
            font-size:10.5px;
            font-weight:600;
            flex-shrink:0;
        }
        .prop-status.active { background:#dcfce7; color:#16a34a; }
        .prop-status.pending { background:#fef3c7; color:#b45309; }
        .prop-status.sold { background:#f1f5f9; color:#64748b; }

        /* Message List Items */
        .msg-list-item {
            display:flex;
            align-items:center;
            gap:12px;
            padding:12px 8px;
            border-radius:10px;
            transition:all 0.2s ease;
            cursor:pointer;
            border-bottom:1px solid #f8fafc;
        }
        .msg-list-item:last-child { border-bottom:none; }
        .msg-list-item:hover { background:#f8fafc; }
        .msg-avatar {
            width:42px;
            height:42px;
            border-radius:50%;
            background:#dcfce7;
            color:#0E7A4E;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:15px;
            flex-shrink:0;
        }
        .msg-details { flex:1; min-width:0; }
        .msg-details .sender { font-size:13px; font-weight:600; color:#0f172a; margin-bottom:2px; }
        .msg-details .preview { font-size:12px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .msg-dot { width:7px; height:7px; border-radius:50%; background:#0E7A4E; flex-shrink:0; }
        .msg-time { font-size:11px; color:#94a3b8; white-space:nowrap; flex-shrink:0; }

        .empty-state { text-align:center; padding:50px 20px; }
        .empty-state i { font-size:36px; color:#cbd5e1; margin-bottom:10px; display:block; }
        .empty-state p { color:#94a3b8; font-size:13px; margin-bottom:8px; }
        .empty-state a { color:#0E7A4E; text-decoration:none; font-weight:600; font-size:13px; }

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
            .topbar { padding:12px 18px; }
            .stats-grid { grid-template-columns:1fr 1fr; gap:12px; }
            .welcome-banner { flex-direction:column; align-items:flex-start; padding:28px 22px; min-height:auto; }
            .welcome-text h1 { font-size:22px; }
            .user-info { display:none; }
        }
        @media (max-width:480px) { .stats-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- SIDEBAR - UNCHANGED -->
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
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
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

        <!-- TOP BAR - UNCHANGED -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('sellerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

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

            <!-- Professional Welcome Banner -->
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($user_name); ?> <span class="wave">👋</span></h1>
                    <p>Manage your property listings, track inquiries, and grow your real estate business from one powerful dashboard.</p>
                    <div class="welcome-meta">
                        <span><i class="far fa-calendar-alt"></i> <?php echo date('l, d F Y'); ?></span>
                        <span><i class="far fa-clock"></i> <?php echo date('h:i A'); ?></span>
                        <span><i class="fas fa-building"></i> <?php echo $active_properties; ?> Active Listings</span>
                    </div>
                </div>
                <a href="add-property.php" class="add-property-btn">
                    <i class="fas fa-plus-circle"></i> Add New Property
                </a>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-building"></i> <!-- Professional Icon -->
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
                        <i class="fas fa-check-circle"></i> <!-- Professional Icon -->
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
                        <i class="fas fa-envelope"></i> <!-- Professional Icon -->
                    </div>
                    <div class="stat-number"><?php echo $total_messages; ?></div>
                    <div class="stat-label">Unread Messages</div>
                    <div class="stat-growth neutral">Check your inbox</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-eye"></i> <!-- Professional Icon -->
                    </div>
                    <div class="stat-number"><?php echo number_format($total_views); ?></div>
                    <div class="stat-label">Total Views</div>
                    <div class="stat-growth neutral">Across all listings</div>
                </div>
            </div>

            <!-- Side-by-Side Content Grid -->
            <div class="content-grid">

                <!-- Recent Properties Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h2><i class="fas fa-clock"></i> Recent Properties</h2>
                        <a href="my-properties.php" class="view-all-btn">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="card-body-scroll">
                        <?php if(empty($recent_props)): ?>
                            <div class="empty-state">
                                <i class="fas fa-home"></i>
                                <p>No properties listed yet</p>
                                <a href="add-property.php">Add your first property →</a>
                            </div>
                        <?php else: ?>
                            <?php foreach($recent_props as $prop):
                                $images = getPropertyImages($prop['property_type'] ?? 'house', $prop['id']);
                                $status_class = strtolower($prop['status'] ?? 'active');
                            ?>
                                <div class="prop-list-item" onclick="window.location.href='property-detail.php?id=<?php echo $prop['id']; ?>'">
                                    <img class="prop-thumb" src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($prop['title']); ?>" onerror="this.src='https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=100&h=80&fit=crop';">
                                    <div class="prop-info">
                                        <h4><?php echo htmlspecialchars($prop['title']); ?></h4>
                                        <div class="prop-meta"><?php echo htmlspecialchars($prop['location'] ?? ''); ?> · PKR <?php echo number_format($prop['price'] / 1000000, 1); ?>M</div>
                                    </div>
                                    <span class="prop-status <?php echo $status_class; ?>"><?php echo ucfirst($prop['status'] ?? 'Active'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Messages Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h2><i class="fas fa-comments"></i> Recent Messages</h2>
                        <a href="messages.php" class="view-all-btn">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="card-body-scroll">
                        <?php if(empty($recent_msgs)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No messages yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($recent_msgs as $m): ?>
                                <div class="msg-list-item" onclick="window.location.href='messages.php?sender=<?php echo $m['sender_id']; ?>&property=<?php echo $m['property_id']; ?>'">
                                    <div class="msg-avatar"><?php echo strtoupper(substr($m['sender_name'], 0, 1)); ?></div>
                                    <div class="msg-details">
                                        <div class="sender"><?php echo htmlspecialchars($m['sender_name']); ?></div>
                                        <div class="preview"><?php echo htmlspecialchars(substr($m['message'], 0, 45)); ?></div>
                                    </div>
                                    <?php if(!$m['is_read']): ?><div class="msg-dot"></div><?php endif; ?>
                                    <div class="msg-time"><?php echo time_ago($m['created_at']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script>
function showWishlistToast(message, isError) {
    let toast = document.getElementById('wishlistToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'wishlistToast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:14px 22px;border-radius:10px;color:#fff;font-weight:600;font-size:14px;z-index:9999;box-shadow:0 10px 25px rgba(0,0,0,0.15);transition:opacity .3s,transform .3s;opacity:0;transform:translateY(10px);';
        document.body.appendChild(toast);
    }
    toast.style.background = isError ? '#DC2626' : '#16A34A';
    toast.textContent = message;
    toast.style.opacity = '1';
    toast.style.transform = 'translateY(0)';
    clearTimeout(toast._hideTimer);
    toast._hideTimer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
    }, 2500);
}

function toggleWishlistSeller(el) {
    const propertyId = el.getAttribute('data-id');
    fetch('toggle-wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'ajax=1&property_id=' + encodeURIComponent(propertyId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'login_required') {
            showWishlistToast('Please login to use wishlist', true);
        } else if (data.status === 'added') {
            el.classList.add('active');
            showWishlistToast('Added to wishlist ❤️', false);
        } else if (data.status === 'removed') {
            el.classList.remove('active');
            showWishlistToast('Removed from wishlist', false);
        }
    })
    .catch(() => showWishlistToast('Network error', true));
}
</script>

</body>
</html>