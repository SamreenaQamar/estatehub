<?php
$page_title = 'My Wishlist';
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? '';

// Redirect to appropriate wishlist page
if ($user_type == 'user' || $user_type == 'buyer') {
    header("Location: buyer-wishlist.php");
    exit();
} elseif ($user_type == 'admin') {
    header("Location: admin/wishlist.php");
    exit();
} elseif ($user_type != 'seller') {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'Seller';

// ============================================================
// PROFILE PICTURE PATH (same as seller dashboard)
// ============================================================
$profile_pic_path = '';
$pic_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($pic_check) > 0;

if ($has_pic_column) {
    $user_query = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = $user_id");
    if ($user_query && mysqli_num_rows($user_query) > 0) {
        $user_data = mysqli_fetch_assoc($user_query);
        if (!empty($user_data['profile_pic'])) {
            $pic_file = "uploads/profiles/" . $user_data['profile_pic'];
            if (file_exists($pic_file)) {
                $profile_pic_path = $pic_file . '?t=' . time();
            }
        }
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

// ============================================================
// UNREAD MESSAGES COUNT (for sidebar badge)
// ============================================================
$unread_msgs = 0;
$msg_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE receiver_id = $user_id AND is_read = 0");
if ($msg_query) {
    $unread_msgs = (int) mysqli_fetch_assoc($msg_query)['total'];
}

// ============================================================
// FETCH WISHLIST PROPERTIES
// ============================================================
$query = "SELECT 
            w.id as wishlist_id,
            w.created_at as wishlist_date,
            p.id as property_id,
            p.title,
            p.description,
            p.property_type,
            p.purpose,
            p.price,
            p.location,
            p.city,
            p.area_size,
            p.bedrooms,
            p.bathrooms,
            p.image_main,
            p.status,
            p.featured
          FROM wishlist w 
          INNER JOIN properties p ON w.property_id = p.id 
          WHERE w.user_id = $user_id 
          ORDER BY w.created_at DESC";
$result = mysqli_query($conn, $query);
$wishlist_count = mysqli_num_rows($result);

// ============================================================
// getPropertyImages() – same as home page
// ============================================================
if (!function_exists('getPropertyImages')) {
    function getPropertyImages($type, $id) {
        switch (strtolower(trim($type))) {
            case 'house':        $folder = 'house'; break;
            case 'apartment':    $folder = 'apartment'; break;
            case 'plot':         $folder = 'plot'; break;
            case 'commercial':   $folder = 'commercial'; break;
            case 'farm house':   $folder = 'farmhouse'; break;
            case 'villa':        $folder = 'villa'; break;
            case 'penthouse':    $folder = 'penthouse'; break;
            case 'portion':      $folder = 'portion'; break;
            default:             $folder = 'house'; break;
        }
        $images = [];
        $start = ($id % 10) + 1;
        for ($i = 0; $i < 4; $i++) {
            $num = (($start + $i - 1) % 10) + 1;
            $images[] = "assets/images/$folder/$num.jpg";
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

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== RESET & BASE ===== */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        /* ===== SIDEBAR (seller version, identical to seller dashboard) ===== */
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

        /* ===== WISHLIST SPECIFIC ===== */
        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .wishlist-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: #0b1a2e;
        }
        .wishlist-header h1 i { color: #ef4444; margin-right: 10px; }
        .wishlist-count-badge {
            background: #ecfdf5;
            color: #0E7A4E;
            padding: 6px 18px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 14px;
        }

        /* ===== GRID - 4 COLUMNS ===== */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        @media (max-width: 992px) { .wishlist-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .wishlist-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 480px) { .wishlist-grid { grid-template-columns: 1fr; } }

        /* ============================================ */
        /* PREMIUM PROPERTY CARD - IDENTICAL TO HOME     */
        /* ============================================ */
        .premium-card {
            background: #fff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .premium-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
        }
        .premium-card .card-image-slider {
            border-radius: 16px 16px 0 0;
            overflow: hidden;
            position: relative;
            height: 180px;
            background: #f3f4f6;
        }
        .card-image-slider .slider-container { width: 100%; height: 100%; overflow: hidden; }
        .card-image-slider .slider-track { display: flex; height: 100%; transition: transform 0.5s ease; }
        .card-image-slider .slider-track img { width: 100%; height: 100%; object-fit: cover; flex-shrink: 0; }
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.7);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 18px;
            cursor: pointer;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
            opacity: 0;
            pointer-events: none;
            z-index: 3;
        }
        .premium-card:hover .slider-btn { opacity: 1; pointer-events: auto; }
        .slider-btn:hover { background: white; }
        .slider-prev { left: 8px; }
        .slider-next { right: 8px; }
        .slider-dots {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 5px;
            z-index: 3;
        }
        .slider-dot { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,0.5); cursor: pointer; transition: 0.2s; }
        .slider-dot.active { background: white; width: 16px; border-radius: 4px; }

        .card-tag {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: white;
            z-index: 4;
        }
        .card-tag.for-sale { background: #0E7A4E; }
        .card-tag.for-rent { background: #10B981; }

        .featured-badge {
            position: absolute;
            top: 12px;
            left: 90px;
            background: #F59E0B;
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            z-index: 4;
        }

        .wishlist-icon {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0,0,0,0.4);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            z-index: 4;
        }
        .wishlist-icon:hover { background: rgba(0,0,0,0.6); }
        .wishlist-icon svg { width: 18px; height: 18px; stroke: white; fill: none; }
        .wishlist-icon.active svg { fill: #cc0000; stroke: #b80000; }

        .premium-card .card-body {
            padding: 16px 18px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .card-body h3 {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        .card-location {
            font-size: 12px;
            color: #6B7280;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .card-location svg { width: 14px; height: 14px; stroke: currentColor; flex-shrink: 0; }

        .card-price {
            font-size: 20px;
            font-weight: 800;
            color: #0E7A4E;
            margin: 6px 0 10px;
        }
        .per-month { font-size: 13px; font-weight: 600; color: #64748b; }
        .contact-price { font-size: 15px; font-weight: 700; color: #ef4444; }

        .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 12px;
            margin-bottom: 12px;
            border-top: 1px solid #eef2f7;
            padding-top: 10px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
        }
        .meta-item i { font-size: 13px; color: #1e293b; width: 16px; text-align: center; }

        .view-detail-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px 0;
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: #fff;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: 0.3s;
            margin-top: auto;
        }
        .view-detail-btn:hover {
            background: #0a5c3a;
            transform: scale(1.01);
            box-shadow: 0 8px 25px rgba(14,122,78,0.3);
            color: #fff;
        }

        .premium-card.removing {
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.35s ease, transform 0.35s ease;
        }

        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
            border: 1px solid #edf2f7;
        }
        .empty-state .empty-icon { font-size: 72px; margin-bottom: 20px; }
        .empty-state h3 { font-size: 24px; font-weight: 800; color: #0b1a2e; margin: 20px 0 10px; }
        .empty-state p { color: #64748b; margin-bottom: 24px; font-size: 15px; }
        .browse-btn {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: white;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(14,122,78,0.2);
        }
        .browse-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(14,122,78,0.35); color: white; }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
        }
        @media (max-width:768px) {
            .content-inner { padding:18px; }
            .topbar { padding:12px 18px; flex-wrap:wrap; }
            .topbar-search { max-width:100%; order:3; flex-basis:100%; margin-top:8px; }
            .user-info { display:none; }
            .wishlist-header { flex-direction:column; align-items:flex-start; gap:12px; }
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR (seller version) ===== -->
    <aside class="sidebar" id="sellerSidebar">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-home logo-icon"></i>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>
        <div class="sidebar-label">Main Menu</div>
        <ul class="sidebar-menu">
            <li><a href="seller-dashboard.php">
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
                <?php if ($unread_msgs > 0): ?><span class="badge"><?php echo $unread_msgs; ?></span><?php endif; ?>
            </a></li>
            <li><a href="wishlist.php" class="active">
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

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">

        <!-- TOP BAR (seller version) -->
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
            <!-- Wishlist Header -->
            <div class="wishlist-header">
                <h1><i class="fas fa-heart"></i> My Wishlist</h1>
                <span class="wishlist-count-badge" id="wishlistCountBadge"><?php echo $wishlist_count; ?> <?php echo $wishlist_count == 1 ? 'property' : 'properties'; ?></span>
            </div>

            <?php if ($wishlist_count > 0): ?>
                <div class="wishlist-grid" id="wishlistGrid">
                    <?php while ($prop = mysqli_fetch_assoc($result)):
                        $images = getPropertyImages($prop['property_type'], $prop['property_id']);
                        $price_display = formatCardPrice($prop['price'], $prop['purpose']);
                    ?>
                        <div class="premium-card" data-property-id="<?php echo $prop['property_id']; ?>">
                            <div class="card-image-slider" id="slider_<?php echo $prop['property_id']; ?>">
                                <div class="slider-container">
                                    <div class="slider-track">
                                        <?php foreach ($images as $img): ?>
                                            <img src="<?php echo htmlspecialchars($img); ?>"
                                                 alt="<?php echo htmlspecialchars($prop['title']); ?>"
                                                 loading="lazy"
                                                 onerror="this.src='assets/images/house/1.jpg';">
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button class="slider-btn slider-prev" onclick="prevSlide('slider_<?php echo $prop['property_id']; ?>')">‹</button>
                                <button class="slider-btn slider-next" onclick="nextSlide('slider_<?php echo $prop['property_id']; ?>')">›</button>
                                <div class="slider-dots" id="dots_<?php echo $prop['property_id']; ?>"></div>
                            </div>

                            <div class="card-tag <?php echo $prop['purpose'] == 'Rent' ? 'for-rent' : 'for-sale'; ?>">
                                <?php echo $prop['purpose'] == 'Rent' ? 'For Rent' : 'For Sale'; ?>
                            </div>
                            <?php if (!empty($prop['featured'])): ?>
                                <div class="featured-badge">Featured</div>
                            <?php endif; ?>

                            <a href="javascript:void(0)" class="wishlist-icon active" data-id="<?php echo (int) $prop['property_id']; ?>" onclick="toggleWishlist(this)" title="Remove from wishlist">
                                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                                </svg>
                            </a>

                            <div class="card-body">
                                <h3><?php echo htmlspecialchars($prop['title']); ?></h3>
                                <div class="card-location">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?php echo htmlspecialchars($prop['location']); ?>, <?php echo htmlspecialchars($prop['city']); ?>
                                </div>
                                <div class="card-price">
                                    <?php echo $price_display; ?>
                                </div>
                                <div class="card-meta">
                                    <?php if ($prop['bedrooms']): ?>
                                        <span class="meta-item"><i class="fas fa-bed"></i> <?php echo $prop['bedrooms']; ?> Beds</span>
                                    <?php endif; ?>
                                    <?php if ($prop['bathrooms']): ?>
                                        <span class="meta-item"><i class="fas fa-bath"></i> <?php echo $prop['bathrooms']; ?> Baths</span>
                                    <?php endif; ?>
                                    <?php if (!empty($prop['area_size'])): ?>
                                        <span class="meta-item"><i class="fas fa-vector-square"></i> <?php echo htmlspecialchars($prop['area_size']); ?></span>
                                    <?php endif; ?>
                                    <span class="meta-item"><i class="fas fa-building"></i> <?php echo htmlspecialchars($prop['property_type']); ?></span>
                                </div>
                                <a href="property-detail.php?id=<?php echo $prop['property_id']; ?>" class="view-detail-btn">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state" id="emptyWishlist">
                    <div class="empty-icon">💔</div>
                    <h3>Your wishlist is empty</h3>
                    <p>Start adding properties to your wishlist by clicking the heart icon on any property.</p>
                    <a href="listings.php" class="browse-btn">Browse Properties</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// ============================================ //
// SLIDER FUNCTIONS
// ============================================ //
let slideIndexes = {};

function initSlider(sliderId, imageCount) {
    slideIndexes[sliderId] = 0;
    updateDots(sliderId, imageCount);
}

function updateDots(sliderId, imageCount) {
    const dotsContainer = document.getElementById(sliderId.replace('slider_', 'dots_'));
    if (!dotsContainer) return;
    dotsContainer.innerHTML = '';
    for (let i = 0; i < imageCount; i++) {
        const dot = document.createElement('div');
        dot.className = 'slider-dot' + (i === slideIndexes[sliderId] ? ' active' : '');
        dot.onclick = () => goToSlide(sliderId, i);
        dotsContainer.appendChild(dot);
    }
}

function goToSlide(sliderId, index) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const images = track.querySelectorAll('img');
    if (index < 0) index = 0;
    if (index >= images.length) index = images.length - 1;
    slideIndexes[sliderId] = index;
    track.style.transform = `translateX(-${index * 100}%)`;
    updateDots(sliderId, images.length);
}

function prevSlide(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const images = track.querySelectorAll('img');
    let currentIndex = slideIndexes[sliderId] || 0;
    currentIndex--;
    if (currentIndex < 0) currentIndex = images.length - 1;
    goToSlide(sliderId, currentIndex);
}

function nextSlide(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const images = track.querySelectorAll('img');
    let currentIndex = slideIndexes[sliderId] || 0;
    currentIndex++;
    if (currentIndex >= images.length) currentIndex = 0;
    goToSlide(sliderId, currentIndex);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.card-image-slider').forEach(function(slider) {
        const track = slider.querySelector('.slider-track');
        if (track) {
            const images = track.querySelectorAll('img');
            initSlider(slider.id, images.length);
        }
    });
});

// ============================================ //
// WISHLIST TOGGLE (for seller)
// ============================================ //
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

function updateWishlistCountBadge(delta) {
    const badge = document.getElementById('wishlistCountBadge');
    if (!badge) return;
    const current = parseInt(badge.textContent) || 0;
    const next = Math.max(0, current + delta);
    badge.textContent = next + ' ' + (next === 1 ? 'property' : 'properties');
}

function toggleWishlist(el) {
    const propertyId = el.getAttribute('data-id');
    const card = el.closest('.premium-card');

    fetch('toggle-wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'ajax=1&property_id=' + encodeURIComponent(propertyId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'login_required') {
            showWishlistToast('Please login to use wishlist', true);
            setTimeout(() => { window.location.href = 'login.php'; }, 1200);
        } else if (data.status === 'added') {
            el.classList.add('active');
            showWishlistToast('Added to wishlist ❤️', false);
        } else if (data.status === 'removed') {
            showWishlistToast('Removed from wishlist', false);
            updateWishlistCountBadge(-1);
            if (card) {
                card.classList.add('removing');
                setTimeout(() => {
                    card.remove();
                    const grid = document.getElementById('wishlistGrid');
                    if (grid && grid.children.length === 0) {
                        location.reload();
                    }
                }, 350);
            }
        } else {
            showWishlistToast(data.message || 'Something went wrong', true);
        }
    })
    .catch(() => showWishlistToast('Network error, please try again', true));
}
</script>

</body>
</html>