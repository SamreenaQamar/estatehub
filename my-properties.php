<?php
$page_title = 'My Properties';
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['user_type'] != 'seller') {
    if ($_SESSION['user_type'] == 'user' || $_SESSION['user_type'] == 'buyer') {
        header("Location: buyer-dashboard.php");
    } elseif ($_SESSION['user_type'] == 'admin') {
        header("Location: admin/index.php");
    }
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Seller';

// ============================================================
// PROFILE PICTURE PATH
// ============================================================
$profile_pic_path = '';
$pic_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($pic_check) > 0;

$profile_query  = "SELECT u.*,
                    SUM(CASE WHEN m.is_read = 0 THEN 1 ELSE 0 END) as total_messages
                    FROM users u
                    LEFT JOIN messages m ON u.id = m.receiver_id AND m.is_read = 0
                    WHERE u.id = $user_id
                    GROUP BY u.id";
$profile_result = mysqli_query($conn, $profile_query);
$profile        = mysqli_fetch_assoc($profile_result);
$total_messages = (int)($profile['total_messages'] ?? 0);

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

// ---------- Handle Delete ----------
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM properties WHERE id = $del_id AND user_id = $user_id");
    header("Location: my-properties.php");
    exit();
}

// ---------- Filters ----------
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$type_filter   = isset($_GET['type']) ? trim($_GET['type']) : '';
$city_filter   = isset($_GET['city']) ? trim($_GET['city']) : '';
$price_filter  = isset($_GET['price']) ? (int)$_GET['price'] : 0;
$sort          = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

$where = "WHERE user_id = $user_id";

if ($search !== '') {
    $search_esc = mysqli_real_escape_string($conn, $search);
    $where .= " AND (title LIKE '%$search_esc%' OR location LIKE '%$search_esc%' OR city LIKE '%$search_esc%')";
}
if ($status_filter !== '' && $status_filter !== 'All Status') {
    $status_esc = mysqli_real_escape_string($conn, $status_filter);
    $where .= " AND status = '$status_esc'";
}
if ($type_filter !== '' && $type_filter !== 'All Type') {
    $type_esc = mysqli_real_escape_string($conn, $type_filter);
    $where .= " AND property_type = '$type_esc'";
}
if ($city_filter !== '' && $city_filter !== 'All Cities') {
    $city_esc = mysqli_real_escape_string($conn, $city_filter);
    $where .= " AND city = '$city_esc'";
}
if ($price_filter > 0) {
    $where .= " AND price <= $price_filter";
}

$order_by = "created_at DESC";
if ($sort === 'oldest')      $order_by = "created_at ASC";
elseif ($sort === 'price_high') $order_by = "price DESC";
elseif ($sort === 'price_low')  $order_by = "price ASC";
elseif ($sort === 'views')      $order_by = "views DESC";

// ---------- Pagination ----------
$per_page = 5;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

$count_result  = mysqli_query($conn, "SELECT COUNT(*) as c FROM properties $where");
$total_results = $count_result ? (int)mysqli_fetch_assoc($count_result)['c'] : 0;
$total_pages   = max(1, ceil($total_results / $per_page));

$properties = [];
$props_query = "SELECT * FROM properties $where ORDER BY $order_by LIMIT $per_page OFFSET $offset";
$props_result = mysqli_query($conn, $props_query);
if ($props_result) {
    while ($row = mysqli_fetch_assoc($props_result)) {
        $properties[] = $row;
    }
}

// ---------- Stats ----------
$total_properties    = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id"))['c'] ?? 0);
$active_properties   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id AND status='Active'"))['c'] ?? 0);
$inactive_properties = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id AND status!='Active'"))['c'] ?? 0);
$total_views          = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(views) as v FROM properties WHERE user_id=$user_id"))['v'] ?? 0);

$start_this_month = date('Y-m-01 00:00:00');

$props_before   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id AND created_at < '$start_this_month'"))['c'] ?? 0);
$properties_growth = $props_before > 0 ? round((($total_properties - $props_before) / $props_before) * 100) : ($total_properties > 0 ? 100 : 0);

$active_before  = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id AND status='Active' AND created_at < '$start_this_month'"))['c'] ?? 0);
$active_growth  = $active_before > 0 ? round((($active_properties - $active_before) / $active_before) * 100) : ($active_properties > 0 ? 100 : 0);

$inactive_before = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE user_id=$user_id AND status!='Active' AND created_at < '$start_this_month'"))['c'] ?? 0);
$inactive_growth = $inactive_before > 0 ? round((($inactive_properties - $inactive_before) / $inactive_before) * 100) : ($inactive_properties > 0 ? 100 : 0);

$views_before   = (int)(mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(views) as v FROM properties WHERE user_id=$user_id AND created_at < '$start_this_month'"))['v'] ?? 0);
$views_growth   = $views_before > 0 ? round((($total_views - $views_before) / $views_before) * 100) : ($total_views > 0 ? 100 : 0);

$cities = [];
$city_result = mysqli_query($conn, "SELECT DISTINCT city FROM properties WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
if ($city_result) { while ($row = mysqli_fetch_assoc($city_result)) { $cities[] = $row['city']; } }

function format_price_pk($price) {
    $price = (float)$price;
    if ($price >= 10000000) {
        return 'PKR ' . number_format($price / 10000000, 2) . ' Crore';
    } elseif ($price >= 100000) {
        return 'PKR ' . number_format($price / 100000, 2) . ' Lakh';
    }
    return 'PKR ' . number_format($price);
}

$current_page = basename($_SERVER['PHP_SELF']);

function qs_with($params) {
    $current = $_GET;
    foreach ($params as $k => $v) { $current[$k] = $v; }
    return 'my-properties.php?' . http_build_query($current);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        .sidebar {
            width: 250px; min-width: 250px;
            background: #000000; padding: 24px 16px;
            position: sticky; top: 0; height: 100vh;
            overflow-y: auto; z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar::-webkit-scrollbar { width:4px; }
        .sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12); border-radius:10px; }
        .logo { padding:0 4px 20px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .logo a { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo-icon { font-size:30px; color:#0E7A4E; filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3)); }
        .logo-text { font-size:29px; font-weight:800; letter-spacing:-0.5px; color:#ffffff; position:relative; }
        .logo-text::after { content:''; position:absolute; left:0; bottom:-10px; width:45px; height:3px; background:#0E7A4E; border-radius:10px; }
        .sidebar-label { font-size:11px; font-weight:600; color:rgba(255,255,255,0.3); text-transform:uppercase; letter-spacing:1.5px; padding:8px 12px; margin-bottom:6px; }
        .sidebar-menu { list-style:none; padding:0; margin-bottom:12px; }
        .sidebar-menu li { margin-bottom:6px; }
        .sidebar-menu a { display:flex; align-items:center; gap:12px; padding:12px 16px; width:fit-content; min-width:165px; color:#b8c2cc; text-decoration:none; border-radius:12px; font-size:14px; font-weight:600; transition:all .3s ease; }
        .sidebar-menu a:hover { background:linear-gradient(135deg,#0E7A4E,#16a34a); color:#fff; transform:translateX(6px); box-shadow:0 8px 20px rgba(14,122,78,.35); }
        .sidebar-menu a svg { width:20px; height:20px; transition:.3s; flex-shrink:0; }
        .sidebar-menu a:hover svg { transform:scale(1.15); }
        .sidebar-menu a.active { background:linear-gradient(135deg,#0E7A4E,#16a34a); color:#fff; box-shadow:0 8px 20px rgba(14,122,78,.35); }
        .sidebar-menu a.active svg { transform:scale(1.1); }
        .badge { margin-left:auto; background:#ef4444; color:#fff; border-radius:50px; padding:2px 10px; font-size:10px; font-weight:700; min-width:22px; text-align:center; }
        .logout-link { color:#b8c2cc !important; }
        .logout-link:hover { background:linear-gradient(135deg,#dc2626,#ef4444) !important; color:#fff !important; box-shadow:0 8px 20px rgba(220,38,38,.35) !important; }

        /* ===== TOP BAR - FIXED ===== */
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

        .main-content { flex:1; overflow-y:auto; }
        .content-inner { padding:28px 32px 40px; }

        .page-header{ display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:24px; }
        .page-header h1{ font-size:26px; font-weight:800; color:#0b1a2e; margin-bottom:4px; }
        .page-header p{ font-size:14px; color:#64748b; }
        .page-header-actions{ display:flex; gap:10px; }
        .btn-export{ display:inline-flex; align-items:center; gap:8px; background:#fff; color:#0b1a2e; border:1px solid #e2e8f0; padding:11px 20px; border-radius:10px; font-weight:700; font-size:14px; text-decoration:none; cursor:pointer; }
        .btn-export:hover{ background:#f8fafc; }
        .btn-add{ display:inline-flex; align-items:center; gap:8px; background:#0E7A4E; color:#fff; border:none; padding:11px 20px; border-radius:10px; font-weight:700; font-size:14px; text-decoration:none; transition:0.25s; }
        .btn-add:hover{ background:#0a5c3a; transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,0.15); }

        .stats-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
        .stat-card{ background:#fff; border-radius:16px; padding:18px; border:1px solid #edf2f7; position:relative; overflow:hidden; transition:.3s ease; }
        .stat-card::before{ content:''; position:absolute; top:0; left:0; width:100%; height:3px; }
        .stat-card.c-green::before{ background:#0E7A4E; }
        .stat-card.c-orange::before{ background:#F97316; }
        .stat-card.c-purple::before{ background:#8B5CF6; }
        .stat-card:hover{ transform:translateY(-4px); box-shadow:0 12px 25px rgba(15,23,42,.08); }
        .stat-top{ display:flex; align-items:flex-start; justify-content:space-between; }
        .stat-icon{ width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; }
        .stat-icon.icon-green{ background:rgba(14,122,78,.10); color:#0E7A4E; }
        .stat-icon.icon-orange{ background:rgba(249,115,22,.10); color:#F97316; }
        .stat-icon.icon-purple{ background:rgba(139,92,246,.10); color:#8B5CF6; }
        .stat-icon svg{ width:24px; height:24px; stroke:currentColor; fill:none; stroke-width:2; }
        .stat-label{ font-size:14px; color:#64748b; font-weight:500; margin-top:14px; margin-bottom:4px; }
        .stat-number{ font-size:28px; font-weight:800; color:#0b1a2e; line-height:1; margin-bottom:10px; }
        .stat-growth{ font-size:12px; font-weight:600; display:flex; align-items:center; gap:4px; }
        .stat-growth.up{ color:#0E7A4E; }
        .stat-growth.down{ color:#ef4444; }
        .stat-growth.neutral{ color:#94a3b8; }

        .filters-card{ background:#fff; border-radius:16px; border:1px solid #edf2f7; padding:16px; margin-bottom:20px; display:flex; flex-wrap:wrap; gap:12px; align-items:center; }
        .filters-form{ display:flex; flex-wrap:wrap; gap:12px; align-items:center; width:100%; }
        .search-box{ flex:1; min-width:180px; position:relative; }
        .search-box input{ width:100%; padding:10px 40px 10px 16px; border-radius:10px; border:1px solid #e2e8f0; background:#f8fafc; font-size:14px; outline:none; }
        .search-box input:focus{ border-color:#0E7A4E; background:#fff; }
        .search-box svg{ position:absolute; right:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#adb5bd; pointer-events:none; }
        .filters-form select{ padding:10px 14px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; font-size:14px; color:#334155; outline:none; cursor:pointer; min-width:130px; }
        .filters-form select:focus{ border-color:#0E7A4E; }
        .btn-filters{ display:inline-flex; align-items:center; gap:8px; background:#fff; color:#0b1a2e; border:1px solid #e2e8f0; padding:10px 18px; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; }
        .btn-filters:hover{ background:#f8fafc; }
        .btn-filters svg{ width:16px; height:16px; }

        .table-card{ background:#fff; border-radius:18px; border:1px solid #edf2f7; overflow:hidden; }
        table{ width:100%; border-collapse:collapse; }
        thead th{ text-align:left; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:0.6px; color:#94a3b8; padding:14px 20px; border-bottom:1px solid #edf2f7; background:#fafbfc; white-space:nowrap; }
        tbody td{ padding:16px 20px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:14px; }
        tbody tr:last-child td{ border-bottom:none; }
        tbody tr:hover{ background:#fafbfc; }
        .prop-cell{ display:flex; align-items:center; gap:14px; min-width:260px; }
        .prop-thumb{ width:64px; height:56px; border-radius:10px; object-fit:cover; flex-shrink:0; background:#e2e8f0; }
        .prop-info h4{ font-size:14px; font-weight:700; color:#0b1a2e; margin-bottom:3px; }
        .prop-info .meta{ font-size:12px; color:#64748b; margin-bottom:2px; }
        .prop-info .prop-id{ font-size:11px; color:#94a3b8; }
        .type-cell{ display:flex; align-items:center; gap:8px; color:#334155; font-weight:500; }
        .type-cell svg{ width:16px; height:16px; color:#64748b; flex-shrink:0; }
        .loc-cell{ display:flex; align-items:center; gap:6px; color:#334155; }
        .loc-cell svg{ width:14px; height:14px; color:#0E7A4E; flex-shrink:0; }
        .loc-cell div .city{ font-size:12px; color:#94a3b8; }
        .price-cell{ font-weight:700; color:#0b1a2e; }
        .status-badge{ display:inline-block; padding:5px 12px; border-radius:30px; font-size:11px; font-weight:700; }
        .status-active{ background:#dcfce7; color:#15803d; }
        .status-inactive{ background:#fef3c7; color:#b45309; }
        .status-pending{ background:#fef3c7; color:#b45309; }
        .status-sold{ background:#e5e7eb; color:#374151; }
        .views-cell{ display:flex; align-items:center; gap:6px; color:#334155; }
        .views-cell svg{ width:15px; height:15px; color:#94a3b8; }
        .actions-cell{ display:flex; gap:8px; }
        .action-btn{ width:32px; height:32px; border-radius:8px; border:none; display:flex; align-items:center; justify-content:center; cursor:pointer; text-decoration:none; transition:0.2s; }
        .action-btn svg{ width:15px; height:15px; }
        .action-edit{ background:#eff6ff; color:#2563eb; }
        .action-edit:hover{ background:#dbeafe; }
        .action-stats{ background:#f0fdf4; color:#0E7A4E; }
        .action-stats:hover{ background:#dcfce7; }
        .action-delete{ background:#fef2f2; color:#ef4444; }
        .action-delete:hover{ background:#fee2e2; }
        .empty-state{ text-align:center; padding:60px 20px; }
        .empty-state svg{ width:56px; height:56px; color:#cbd5e1; margin-bottom:14px; }
        .empty-state p{ color:#64748b; font-size:14px; margin-bottom:10px; }
        .empty-state a{ color:#0E7A4E; text-decoration:none; font-weight:700; }
        .pagination-bar{ display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:18px 20px; border-top:1px solid #edf2f7; }
        .pagination-bar .showing{ font-size:13px; color:#64748b; }
        .pagination{ display:flex; align-items:center; gap:6px; list-style:none; }
        .pagination a, .pagination span{ display:flex; align-items:center; justify-content:center; width:34px; height:34px; border-radius:8px; font-size:13px; font-weight:600; color:#475569; text-decoration:none; border:1px solid #e2e8f0; }
        .pagination a:hover{ background:#f8fafc; }
        .pagination .active{ background:#0E7A4E; color:#fff; border-color:#0E7A4E; }
        .pagination .disabled{ opacity:0.4; pointer-events:none; }

        @media (max-width:1200px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
        @media (max-width:1100px) { .sidebar { position:fixed; left:-280px; transition:left 0.3s; } .sidebar.open { left:0; } .topbar-menu-btn { display:flex; } .table-card{ overflow-x:auto; } table{ min-width:900px; } }
        @media (max-width:768px) { .content-inner { padding:18px; } .topbar { padding:12px 18px; } .stats-grid { grid-template-columns:1fr 1fr; gap:12px; } .user-info { display:none; } .page-header{ flex-direction:column; } }
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
            <li><a href="seller-dashboard.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg> Dashboard</a></li>
            <li><a href="my-properties.php" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> My Properties</a></li>
            <li><a href="add-property.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Add Property</a></li>
            <li><a href="messages.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Messages <?php if($total_messages>0): ?><span class="badge"><?php echo $total_messages; ?></span><?php endif; ?></a></li>
            <li><a href="wishlist.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> Wishlist</a></li>
        </ul>
        <div class="sidebar-label">Account</div>
        <ul class="sidebar-menu">
            <li><a href="profile-settings.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg> Profile Settings</a></li>
            <li><a href="settings.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> Settings</a></li>
            <li><a href="logout.php" class="logout-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Logout</a></li>
        </ul>
    </aside>

    <!-- MAIN -->
    <div class="main-content">

        <!-- TOP BAR - FIXED -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('sellerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-actions">
                <a href="profile-settings.php" class="user-chip">
                    <div class="user-avatar">
                        <?php if (!empty($profile_pic_path)): ?>
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

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1>My Properties</h1>
                    <p>Manage all your listed properties in one place.</p>
                </div>
                <div class="page-header-actions">
                    <a href="#" class="btn-export" onclick="window.print(); return false;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export
                    </a>
                    <a href="add-property.php" class="btn-add">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add New Property
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card c-green">
                    <div class="stat-top"><div class="stat-icon icon-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></div></div>
                    <div class="stat-label">Total Properties</div>
                    <div class="stat-number"><?php echo $total_properties; ?></div>
                    <div class="stat-growth <?php echo $properties_growth > 0 ? 'up' : ($properties_growth < 0 ? 'down' : 'neutral'); ?>"><?php echo $properties_growth > 0 ? '↗' : ($properties_growth < 0 ? '↘' : '–'); ?> <?php echo abs($properties_growth); ?>% from last month</div>
                </div>
                <div class="stat-card c-green">
                    <div class="stat-top"><div class="stat-icon icon-green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg></div></div>
                    <div class="stat-label">Active Listings</div>
                    <div class="stat-number"><?php echo $active_properties; ?></div>
                    <div class="stat-growth <?php echo $active_growth > 0 ? 'up' : ($active_growth < 0 ? 'down' : 'neutral'); ?>"><?php echo $active_growth > 0 ? '↗' : ($active_growth < 0 ? '↘' : '–'); ?> <?php echo abs($active_growth); ?>% from last month</div>
                </div>
                <div class="stat-card c-orange">
                    <div class="stat-top"><div class="stat-icon icon-orange"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="10" y1="8" x2="10" y2="16"/><line x1="14" y1="8" x2="14" y2="16"/></svg></div></div>
                    <div class="stat-label">Inactive Listings</div>
                    <div class="stat-number"><?php echo $inactive_properties; ?></div>
                    <div class="stat-growth <?php echo $inactive_growth > 0 ? 'down' : ($inactive_growth < 0 ? 'up' : 'neutral'); ?>"><?php echo $inactive_growth > 0 ? '↘' : ($inactive_growth < 0 ? '↗' : '–'); ?> <?php echo abs($inactive_growth); ?>% from last month</div>
                </div>
                <div class="stat-card c-purple">
                    <div class="stat-top"><div class="stat-icon icon-purple"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div></div>
                    <div class="stat-label">Total Views</div>
                    <div class="stat-number"><?php echo $total_views >= 1000 ? number_format($total_views / 1000, 1) . 'K' : number_format($total_views); ?></div>
                    <div class="stat-growth <?php echo $views_growth > 0 ? 'up' : ($views_growth < 0 ? 'down' : 'neutral'); ?>"><?php echo $views_growth > 0 ? '↗' : ($views_growth < 0 ? '↘' : '–'); ?> <?php echo abs($views_growth); ?>% from last month</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-card">
                <form class="filters-form" method="GET" action="my-properties.php">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search by title, location..." value="<?php echo htmlspecialchars($search); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status_filter=='Active'?'selected':''; ?>>Active</option>
                        <option value="Pending" <?php echo $status_filter=='Pending'?'selected':''; ?>>Pending</option>
                        <option value="Inactive" <?php echo $status_filter=='Inactive'?'selected':''; ?>>Inactive</option>
                        <option value="Sold" <?php echo $status_filter=='Sold'?'selected':''; ?>>Sold</option>
                    </select>
                    <select name="type" onchange="this.form.submit()">
                        <option value="">All Type</option>
                        <option value="House" <?php echo $type_filter=='House'?'selected':''; ?>>House</option>
                        <option value="Apartment" <?php echo $type_filter=='Apartment'?'selected':''; ?>>Apartment</option>
                        <option value="Commercial" <?php echo $type_filter=='Commercial'?'selected':''; ?>>Commercial</option>
                        <option value="Plot" <?php echo $type_filter=='Plot'?'selected':''; ?>>Plot</option>
                        <option value="Farm House" <?php echo $type_filter=='Farm House'?'selected':''; ?>>Farm House</option>
                        <option value="Villa" <?php echo $type_filter=='Villa'?'selected':''; ?>>Villa</option>
                        <option value="Penthouse" <?php echo $type_filter=='Penthouse'?'selected':''; ?>>Penthouse</option>
                        <option value="Portion" <?php echo $type_filter=='Portion'?'selected':''; ?>>Portion</option>
                    </select>
                    <select name="city" onchange="this.form.submit()">
                        <option value="">All Cities</option>
                        <?php foreach($cities as $c): ?>
                            <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $city_filter==$c?'selected':''; ?>><?php echo htmlspecialchars($c); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="price" onchange="this.form.submit()">
                        <option value="0">Any Price</option>
                        <option value="1000000" <?php echo $price_filter==1000000?'selected':''; ?>>PKR 1M</option>
                        <option value="2000000" <?php echo $price_filter==2000000?'selected':''; ?>>PKR 2M</option>
                        <option value="3000000" <?php echo $price_filter==3000000?'selected':''; ?>>PKR 3M</option>
                        <option value="4000000" <?php echo $price_filter==4000000?'selected':''; ?>>PKR 4M</option>
                        <option value="5000000" <?php echo $price_filter==5000000?'selected':''; ?>>PKR 5M</option>
                        <option value="10000000" <?php echo $price_filter==10000000?'selected':''; ?>>PKR 10M</option>
                    </select>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort=='newest'?'selected':''; ?>>Newest</option>
                        <option value="oldest" <?php echo $sort=='oldest'?'selected':''; ?>>Oldest</option>
                        <option value="price_high" <?php echo $sort=='price_high'?'selected':''; ?>>Price: High</option>
                        <option value="price_low" <?php echo $sort=='price_low'?'selected':''; ?>>Price: Low</option>
                        <option value="views" <?php echo $sort=='views'?'selected':''; ?>>Most Views</option>
                    </select>
                    <button type="submit" class="btn-filters"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg> Filters</button>
                </form>
            </div>

            <!-- Table -->
            <div class="table-card">
                <?php if(empty($properties)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <p>No properties found.</p>
                        <a href="add-property.php">Add your first property →</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead><tr><th>Property</th><th>Type</th><th>Location</th><th>Price</th><th>Status</th><th>Views</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($properties as $p):
                                $status=$p['status']??'Active';$status_cls='status-'.strtolower($status);
                                $loc=$p['location']??'';$city=$p['city']??'';
                                $img=$p['featured_image']??($p['image']??'');
                                $prop_type=$p['property_type']??'—';
                                $house_id='EH-'.(10000+(int)$p['id']);
                            ?>
                                <tr>
                                    <td><div class="prop-cell"><img class="prop-thumb" src="<?php echo !empty($img)?htmlspecialchars($img):'assets/images/property-placeholder.jpg'; ?>" onerror="this.src='https://via.placeholder.com/64x56?text=No+Image'"><div class="prop-info"><h4><?php echo htmlspecialchars($p['title']); ?></h4><div class="prop-id">ID: #<?php echo $house_id; ?></div></div></div></td>
                                    <td><div class="type-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg><?php echo htmlspecialchars($prop_type); ?></div></td>
                                    <td><div class="loc-cell"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg><div><?php echo htmlspecialchars($loc); ?><?php if($city): ?><div class="city"><?php echo htmlspecialchars($city); ?></div><?php endif; ?></div></div></td>
                                    <td class="price-cell"><?php echo format_price_pk($p['price']); ?></td>
                                    <td><span class="status-badge <?php echo $status_cls; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                    <td><div class="views-cell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><?php echo number_format((int)($p['views']??0)); ?></div></td>
                                    <td><div class="actions-cell">
                                        <a href="edit-property.php?id=<?php echo $p['id']; ?>" class="action-btn action-edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg></a>
                                        <a href="my-properties.php?delete=<?php echo $p['id']; ?>" class="action-btn action-delete" onclick="return confirm('Delete?')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg></a>
                                    </div></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="pagination-bar">
                        <div class="showing">Showing <b><?php echo $offset+1; ?></b> to <b><?php echo min($offset+$per_page,$total_results); ?></b> of <b><?php echo $total_results; ?></b></div>
                        <ul class="pagination">
                            <li><a class="<?php echo $page<=1?'disabled':''; ?>" href="<?php echo qs_with(['page'=>max(1,$page-1)]); ?>">‹</a></li>
                            <?php for($i=1;$i<=$total_pages;$i++): ?>
                                <li><a class="<?php echo $i==$page?'active':''; ?>" href="<?php echo qs_with(['page'=>$i]); ?>"><?php echo $i; ?></a></li>
                            <?php endfor; ?>
                            <li><a class="<?php echo $page>=$total_pages?'disabled':''; ?>" href="<?php echo qs_with(['page'=>min($total_pages,$page+1)]); ?>">›</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
</body>
</html>