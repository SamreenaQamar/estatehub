<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$messageType = '';

if (isset($_POST['add_property'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $bedrooms = (int)$_POST['bedrooms'];
    $bathrooms = (int)$_POST['bathrooms'];
    $area_size = mysqli_real_escape_string($conn, $_POST['area']);
    $user_id = (int)$_POST['user_id'];

    // Handle image upload
    $main_image = 'default-property.jpg';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $upload_dir = __DIR__ . '/../uploads/properties/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $main_image = time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $main_image);
        }
    }

    mysqli_query($conn,
        "INSERT INTO properties (title, description, price, property_type, status, location, bedrooms, bathrooms, area_size, image_main, user_id, created_at) 
         VALUES ('$title', '$description', $price, '$property_type', '$status', '$location', $bedrooms, $bathrooms, '$area_size', '$main_image', $user_id, NOW())"
    );

    $message = "Property added!";
    $messageType = "success";
}

if (isset($_POST['edit_property'])) {
    $id = (int)$_POST['property_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $price = (float)$_POST['price'];
    $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Handle image upload (only replace if a new file was chosen)
    $image_update = '';
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $upload_dir = __DIR__ . '/../uploads/properties/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $main_image = time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $main_image);
            $image_update = ", image_main = '$main_image'";
        }
    }

    mysqli_query($conn,
        "UPDATE properties 
         SET title = '$title', price = $price, property_type = '$property_type', status = '$status', location = '$location' $image_update
         WHERE id = $id"
    );

    $message = "Property updated!";
    $messageType = "success";
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM properties WHERE id = $id");
    header("Location: manage-properties.php");
    exit();
}

if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE properties SET status = 'Active' WHERE id = $id");
    header("Location: manage-properties.php");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT p.*, u.full_name as owner 
          FROM properties p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE 1 = 1";

if ($filter != 'all') {
    $query .= " AND p.status = '$filter'";
}

if ($search) {
    $query .= " AND (p.title LIKE '%$search%' OR p.location LIKE '%$search%')";
}

$query .= " ORDER BY p.created_at DESC";

$properties = mysqli_query($conn, $query);
$users_list = mysqli_query($conn, "SELECT id, full_name FROM users ORDER BY full_name");

// ===== REAL STATS =====
// Total Users
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$total_users = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

// Pending Properties
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties WHERE status = 'Pending'");
$pending_count = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

// Unread Messages
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
$unread_msgs = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result)['total'] : 0;

// Admin Profile Picture
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

// Property Counts
$property_counts = [
    'total' => 0,
    'active' => 0,
    'pending' => 0,
    'sold' => 0
];

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM properties");
if ($result && mysqli_num_rows($result) > 0) {
    $property_counts['total'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE status = 'Active'");
if ($result && mysqli_num_rows($result) > 0) {
    $property_counts['active'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE status = 'Pending'");
if ($result && mysqli_num_rows($result) > 0) {
    $property_counts['pending'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as c FROM properties WHERE status = 'Sold'");
if ($result && mysqli_num_rows($result) > 0) {
    $property_counts['sold'] = mysqli_fetch_assoc($result)['c'] ?? 0;
}

$page_title = 'Manage Properties';
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

        /* ===== SIDEBAR - SAME AS DASHBOARD ===== */
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

        /* ===== NO BADGES - REMOVED ALL ===== */

        /* ===== LOGOUT LINK ===== */
        .logout-link {
            color:#b8c2cc !important;
        }
        .logout-link:hover {
            background:linear-gradient(135deg,#dc2626,#ef4444) !important;
            color:#fff !important;
            box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
        }

        /* ===== TOP BAR ===== */
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

        /* Page Header */
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

        /* Stats Grid */
        .stats-grid {
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:16px;
            margin-bottom:24px;
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

        /* Filter Tabs */
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

        /* Actions Bar */
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

        /* Table */
        .table-card {
            background:white;
            border-radius:18px;
            border:1px solid #edf2f7;
            overflow:hidden;
        }
        .table-header {
            display:grid;
            grid-template-columns:2fr 1fr 1fr 1fr 1fr 1.5fr;
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
            grid-template-columns:2fr 1fr 1fr 1fr 1fr 1.5fr;
            padding:16px 24px;
            border-bottom:1px solid #f1f5f9;
            align-items:center;
            transition:.3s;
        }
        .table-row:hover { background:#f8fafc; }
        .table-row:last-child { border-bottom:none; }

        .prop-info h4 {
            font-size:14px;
            font-weight:700;
            color:#0b1a2e;
        }
        .prop-info span {
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
        .status-badge.Active { background:#dcfce7; color:#15803d; }
        .status-badge.Pending { background:#fef3c7; color:#b45309; }
        .status-badge.Sold { background:#dbeafe; color:#2563eb; }

        /* Modal */
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
            max-width:600px;
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
        .form-group select,
        .form-group textarea {
            width:100%;
            padding:11px 14px;
            border:2px solid #e9ecef;
            border-radius:12px;
            font-size:14px;
            transition:.3s;
            font-family:'Inter',sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
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
            .table-header { display:none; }
            .table-row { grid-template-columns:1fr; gap:6px; padding:16px; }
            .table-row > div { padding:4px 0; }
            .table-row > div:before { 
                content:attr(data-label);
                font-size:10px;
                font-weight:700;
                color:#94a3b8;
                display:block;
                text-transform:uppercase;
                letter-spacing:0.3px;
            }
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
            <!-- ===== WISHLIST LINK (added) ===== -->
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

        <!-- TOP BAR - NO SEARCH BAR, NO BADGE ON BELL -->
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

        

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='manage-properties.php?filter=all'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div class="stat-number"><?php echo $property_counts['total']; ?></div>
                    <div class="stat-label">Total Properties</div>
                </div>
                <div class="stat-card" onclick="window.location.href='manage-properties.php?filter=Active'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="stat-number"><?php echo $property_counts['active']; ?></div>
                    <div class="stat-label">Active Listings</div>
                </div>
                <div class="stat-card" onclick="window.location.href='manage-properties.php?filter=Pending'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="stat-number"><?php echo $property_counts['pending']; ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
                <div class="stat-card" onclick="window.location.href='manage-properties.php?filter=Sold'">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="stat-number"><?php echo $property_counts['sold']; ?></div>
                    <div class="stat-label">Sold Properties</div>
                </div>
            </div>

            <!-- Filter and Actions -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=Active" class="filter-tab <?php echo $filter == 'Active' ? 'active' : ''; ?>">Active</a>
                <a href="?filter=Pending" class="filter-tab <?php echo $filter == 'Pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?filter=Sold" class="filter-tab <?php echo $filter == 'Sold' ? 'active' : ''; ?>">Sold</a>
            </div>

            <div class="actions-bar">
                <form method="GET" style="display:flex; gap:10px;">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search properties..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                </form>
                <button class="btn btn-primary" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Add Property
                </button>
            </div>

            <!-- Table -->
            <div class="table-card">
                <div class="table-header">
                    <div>Property</div>
                    <div>Price</div>
                    <div>Type</div>
                    <div>Status</div>
                    <div>Owner</div>
                    <div>Actions</div>
                </div>

                <?php if ($properties && mysqli_num_rows($properties) > 0): ?>
                    <?php while ($prop = mysqli_fetch_assoc($properties)): ?>
                        <div class="table-row">
                            <div class="prop-info" data-label="Property">
                                <h4><?php echo htmlspecialchars($prop['title']); ?></h4>
                                <span><?php echo htmlspecialchars($prop['location']); ?></span>
                            </div>
                            <div data-label="Price" style="font-weight:700;">PKR <?php echo number_format($prop['price'] / 1000000, 1); ?>M</div>
                            <div data-label="Type"><?php echo htmlspecialchars($prop['property_type']); ?></div>
                            <div data-label="Status">
                                <span class="status-badge <?php echo $prop['status']; ?>">
                                    <?php echo $prop['status']; ?>
                                </span>
                            </div>
                            <div data-label="Owner" style="font-size:13px;"><?php echo htmlspecialchars($prop['owner']); ?></div>
                            <div data-label="Actions" style="display:flex; gap:6px; flex-wrap:wrap;">
                                <?php if ($prop['status'] == 'Pending'): ?>
                                    <a href="?approve=<?php echo $prop['id']; ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-outline btn-sm" onclick="editProperty(
                                    <?php echo $prop['id']; ?>,
                                    '<?php echo addslashes($prop['title']); ?>',
                                    <?php echo $prop['price']; ?>,
                                    '<?php echo addslashes($prop['property_type']); ?>',
                                    '<?php echo $prop['status']; ?>',
                                    '<?php echo addslashes($prop['location']); ?>'
                                )">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $prop['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this property?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="padding:40px; text-align:center; color:#94a3b8;">No properties found</div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h2>Add Property</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select name="property_type">
                        <option>House</option>
                        <option>Apartment</option>
                        <option>Commercial</option>
                        <option>Plot</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Sold">Sold</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Bedrooms</label>
                    <input type="number" name="bedrooms" value="3">
                </div>
                <div class="form-group">
                    <label>Bathrooms</label>
                    <input type="number" name="bathrooms" value="2">
                </div>
            </div>
            <div class="form-group">
                <label>Area (sqft)</label>
                <input type="number" name="area" value="1500">
            </div>
            <div class="form-group">
                <label>Owner</label>
                <select name="user_id">
                    <?php 
                    // Reset the users_list pointer
                    $users_list = mysqli_query($conn, "SELECT id, full_name FROM users ORDER BY full_name");
                    while ($u = mysqli_fetch_assoc($users_list)): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Property Photo</label>
                <input type="file" name="main_image" accept=".jpg,.jpeg,.png,.gif,.webp">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" name="add_property" class="btn btn-primary">Add Property</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h2>Edit Property</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="property_id" id="edit_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" id="edit_price" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select name="property_type" id="edit_type">
                        <option>House</option>
                        <option>Apartment</option>
                        <option>Commercial</option>
                        <option>Plot</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="Active">Active</option>
                        <option value="Pending">Pending</option>
                        <option value="Sold">Sold</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" id="edit_location" required>
            </div>
            <div class="form-group">
                <label>Property Photo</label>
                <input type="file" name="main_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                <p style="font-size:12px; color:#6B7280; margin-top:6px;">Leave empty to keep the current photo.</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" name="edit_property" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}

function editProperty(id, title, price, type, status, location) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_status').value = status;
    document.getElementById('edit_location').value = location;
    openModal('editModal');
}

document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});
</script>

</body>
</html>