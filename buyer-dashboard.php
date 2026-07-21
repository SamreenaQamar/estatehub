<?php
$page_title = 'Buyer Dashboard';
$hide_navbar = true; // Hide top navigation bar (Home | Listings | About | Contact)
require_once __DIR__ . '/includes/config.php';
include 'includes/header.php';

// ============================================================
// DEFINE getPropertyImages() IF NOT ALREADY DEFINED
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

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['user_type'] != 'user' && $_SESSION['user_type'] != 'buyer') {
    if ($_SESSION['user_type'] == 'seller') {
        header("Location: seller-dashboard.php");
    } elseif ($_SESSION['user_type'] == 'admin') {
        header("Location: admin/index.php");
    }
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Unread messages count
$unread_msgs = 0;
$msg_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE receiver_id = $user_id AND is_read = 0");
if ($msg_query) {
    $unread_msgs = (int) mysqli_fetch_assoc($msg_query)['total'];
}

// Fetch properties (6 latest)
$properties_query = "SELECT * FROM properties WHERE status = 'Active' ORDER BY featured DESC, created_at DESC LIMIT 6";
$properties_result = mysqli_query($conn, $properties_query);
?>
<style>
    /* ===== BUYER DASHBOARD LAYOUT ===== */
    .buyer-dashboard-wrapper {
        display: flex;
        min-height: 100vh;
        background: #f4f6f5;
    }
    .buyer-sidebar {
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
    .buyer-sidebar::-webkit-scrollbar { width:4px; }
    .buyer-sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12); border-radius:10px; }

    .buyer-sidebar .logo {
        padding:0 4px 20px;
        margin-bottom:20px;
        border-bottom:1px solid rgba(255,255,255,0.06);
    }
    .buyer-sidebar .logo a {
        display:flex;
        align-items:center;
        gap:10px;
        text-decoration:none;
    }
    .buyer-sidebar .logo-icon {
        font-size:30px;
        color:#0E7A4E;
        filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3));
    }
    .buyer-sidebar .logo-text {
        font-size:29px;
        font-weight:800;
        letter-spacing:-0.5px;
        color:#ffffff;
        position:relative;
    }
    .buyer-sidebar .logo-text::after {
        content:'';
        position:absolute;
        left:0;
        bottom:-10px;
        width:45px;
        height:3px;
        background:#0E7A4E;
        border-radius:10px;
    }

    .buyer-sidebar .sidebar-label {
        font-size: 11px;
        font-weight: 600;
        color: rgba(255,255,255,0.3);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 8px 12px;
        margin-bottom: 6px;
    }
    .buyer-sidebar .sidebar-menu {
        list-style:none;
        padding:0;
        margin-bottom:12px;
    }
    .buyer-sidebar .sidebar-menu li {
        margin-bottom:6px;
    }
    .buyer-sidebar .sidebar-menu a {
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
    .buyer-sidebar .sidebar-menu a:hover {
        background:linear-gradient(135deg,#0E7A4E,#16a34a);
        color:#fff;
        transform:translateX(6px);
        box-shadow:0 8px 20px rgba(14,122,78,.35);
    }
    .buyer-sidebar .sidebar-menu a svg {
        width:20px;
        height:20px;
        transition:.3s;
    }
    .buyer-sidebar .sidebar-menu a:hover svg {
        transform:scale(1.15);
    }
    .buyer-sidebar .sidebar-menu a.active {
        background:linear-gradient(135deg,#0E7A4E,#16a34a);
        color:#fff;
        box-shadow:0 8px 20px rgba(14,122,78,.35);
    }
    .buyer-sidebar .sidebar-menu a.active svg {
        transform:scale(1.1);
    }
    .buyer-sidebar .badge {
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
    .logout-link {
        color:#b8c2cc !important;
    }
    .logout-link:hover {
        background:linear-gradient(135deg,#dc2626,#ef4444) !important;
        color:#fff !important;
        box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
    }

    .buyer-main-content {
        flex:1;
        overflow-y:auto;
    }
    .content-inner {
        padding:28px 32px 40px;
    }

    /* ===== BUYER DASHBOARD CARDS ===== */
    .buyer-container {
        max-width: 1280px;
        margin: 0 auto;
    }

    /* ===== SEARCH WIDGET (compact, like home page filter) ===== */
    .search-widget {
        background: #fff;
        border-radius: 16px;
        padding: 20px 24px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid #edf2f7;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 16px;
    }
    .search-widget .filter-group {
        flex: 1;
        min-width: 160px;
    }
    .search-widget .filter-group label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 4px;
    }
    .search-widget .filter-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        color: #0b1a2e;
        background: white;
        cursor: pointer;
        transition: 0.2s;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 14px;
    }
    .search-widget .filter-group select:focus {
        outline: none;
        border-color: #0E7A4E;
        box-shadow: 0 0 0 4px rgba(14,122,78,0.08);
    }
    .search-widget .search-btn {
        background: linear-gradient(135deg, #0E7A4E, #16a34a);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(14, 122, 78, 0.2);
        white-space: nowrap;
        height: 44px;
    }
    .search-widget .search-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(14, 122, 78, 0.35);
    }
    .search-widget .search-btn svg {
        stroke: white;
    }
    @media (max-width: 768px) {
        .search-widget {
            flex-direction: column;
            align-items: stretch;
        }
        .search-widget .filter-group {
            min-width: 100%;
        }
    }

    .section-title-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .section-title-wrapper h2 {
        font-size: 24px;
        font-weight: 700;
        color: #111827;
        font-family: 'Fraunces', serif;
    }
    .view-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 24px;
        background: linear-gradient(135deg, #0E7A4E, #16a34a);
        color: #fff;
        border-radius: 30px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(14, 122, 78, 0.2);
        border: none;
        cursor: pointer;
    }
    .view-all-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(14, 122, 78, 0.35);
        color: #fff;
    }
    .view-all-btn i {
        font-size: 14px;
        transition: transform 0.3s ease;
    }
    .view-all-btn:hover i {
        transform: translateX(4px);
    }

    /* ===== PREMIUM PROPERTY CARD (exactly like home page) ===== */
    .properties-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }
    .premium-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        background: #fff;
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
        height: 200px;
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
        transition: all 0.3s ease;
        z-index: 4;
    }
    .wishlist-icon:hover { background: rgba(0,0,0,0.6); }
    .wishlist-icon svg { width: 18px; height: 18px; fill: none; stroke: #ffffff; transition: all 0.3s ease; }
    .wishlist-icon.active { background: #ef4444; }
    .wishlist-icon.active svg { fill: #ffffff; stroke: #ffffff; }

    .premium-card .card-body {
        padding: 16px 18px 18px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .premium-card .card-body h3 {
        font-size: 16px;
        font-weight: 700;
        color: #0b1a2e;
        margin-bottom: 4px;
        line-height: 1.3;
    }
    .card-location {
        font-size: 12px;
        color: #6B7280;
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .card-location svg {
        width: 14px;
        height: 14px;
        stroke: currentColor;
        flex-shrink: 0;
    }
    .card-price {
        font-size: 24px;
        font-weight: 800;
        color: #0E7A4E;
        margin: 8px 0 12px;
    }
    .card-price .per-month {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
    }
    .contact-price {
        font-size: 16px;
        font-weight: 700;
        color: #ef4444;
    }
    .card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 6px 14px;
        margin-bottom: 14px;
        border-top: 1px solid #eef2f7;
        padding-top: 12px;
    }
    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        font-weight: 500;
        color: #1e293b;
    }
    .meta-item i {
        font-size: 14px;
        color: #1e293b;
        width: 16px;
        text-align: center;
    }

    .view-detail-btn {
        display: block;
        width: 100%;
        text-align: center;
        padding: 12px 0;
        background: linear-gradient(135deg, #0E7A4E, #16a34a);
        color: #fff;
        border-radius: 12px;
        font-weight: 700;
        font-size: 15px;
        text-decoration: none;
        transition: 0.3s;
        border: none;
        margin-top: auto;
    }
    .view-detail-btn:hover {
        background: #0a5c3a;
        transform: scale(1.01);
        box-shadow: 0 8px 25px rgba(14,122,78,0.3);
        color: #fff;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        color: #9CA3AF;
    }
    .empty-state svg {
        margin-bottom: 16px;
    }

    @media (max-width: 992px) {
        .properties-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 600px) {
        .properties-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="buyer-dashboard-wrapper">
    <!-- ===== BUYER SIDEBAR ===== -->
    <aside class="buyer-sidebar">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-home logo-icon"></i>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>

        <div class="sidebar-label">Main Menu</div>
        <ul class="sidebar-menu">
            <li><a href="buyer-dashboard.php" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                Dashboard
            </a></li>
            <li><a href="buyer-messages.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Messages
                <?php if ($unread_msgs > 0): ?><span class="badge"><?php echo $unread_msgs; ?></span><?php endif; ?>
            </a></li>
            <li><a href="buyer-wishlist.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                Wishlist
            </a></li>
        </ul>

        <div class="sidebar-label">Account</div>
        <ul class="sidebar-menu">
            <li><a href="buyer-profile.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg>
                Profile Settings
            </a></li>
            <li><a href="buyer-settings.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
            </a></li>
            <li><a href="logout.php" class="logout-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a></li>
        </ul>
        <!-- FOOTER PROFILE REMOVED -->
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="buyer-main-content">
        <div class="content-inner">
            <div class="buyer-container">

                <!-- ===== SEARCH WIDGET (replaces welcome banner) ===== -->
                <div class="search-widget">
                    <div class="filter-group">
                        <label><i class="fas fa-map-pin"></i> Location</label>
                        <select name="location" id="searchLocation">
                            <option value="">All Cities</option>
                            <?php
                            $city_list = ['Lahore','Karachi','Islamabad','Rawalpindi','Faisalabad','Multan','Peshawar','Quetta','Gujranwala','Sialkot','Bahawalpur','Sargodha','Hyderabad','Sukkur','Larkana','Abbottabad','Mardan','Swat','Gwadar','Muzaffarabad','Sahiwal','Rahim Yar Khan','Dera Ghazi Khan','Nawabshah','Mirpur Khas','Kohat','Dera Ismail Khan','Turbat','Khuzdar'];
                            foreach ($city_list as $city) {
                                echo '<option value="' . $city . '">' . $city . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-home"></i> Property Type</label>
                        <select name="type" id="searchType">
                            <option value="">All Types</option>
                            <option value="House">House</option>
                            <option value="Apartment">Apartment</option>
                            <option value="Plot">Plot</option>
                            <option value="Commercial">Commercial</option>
                            <option value="Farm House">Farm House</option>
                            <option value="Villa">Villa</option>
                            <option value="Penthouse">Penthouse</option>
                            <option value="Portion">Portion</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-tag"></i> Price</label>
                        <select name="price" id="searchPrice">
                            <option value="">Any Price</option>
                            <option value="1000000">PKR 1M</option>
                            <option value="2000000">PKR 2M</option>
                            <option value="3000000">PKR 3M</option>
                            <option value="4000000">PKR 4M</option>
                            <option value="5000000">PKR 5M</option>
                            <option value="6000000">PKR 6M</option>
                            <option value="7000000">PKR 7M</option>
                            <option value="8000000">PKR 8M</option>
                            <option value="9000000">PKR 9M</option>
                            <option value="10000000">PKR 10M</option>
                        </select>
                    </div>
                    <button class="search-btn" onclick="performSearch()">
                        <span>Search</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </button>
                </div>

                <div class="section-title-wrapper">
                    <h2>Featured Properties</h2>
                    <a href="listings.php" class="view-all-btn">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <?php if ($properties_result && mysqli_num_rows($properties_result) > 0): ?>
                <div class="properties-grid" id="featuredGrid">
                    <?php while ($prop = mysqli_fetch_assoc($properties_result)):
                        $images = getPropertyImages($prop['property_type'], $prop['id']);
                        $price_formatted = number_format($prop['price'] / 1000000, 1);
                        $price_display = $prop['price'] > 0 ? 'PKR ' . $price_formatted . 'M' : '<span class="contact-price">Contact for Price</span>';
                        $price_suffix = ($prop['purpose'] == 'Rent' && $prop['price'] > 0) ? ' <span class="per-month">/ Month</span>' : '';
                    ?>
                    <div class="premium-card" data-property-id="<?php echo $prop['id']; ?>">
                        <div class="card-image-slider" id="slider_<?php echo $prop['id']; ?>">
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
                            <button class="slider-btn slider-prev" onclick="prevSlide('slider_<?php echo $prop['id']; ?>')">‹</button>
                            <button class="slider-btn slider-next" onclick="nextSlide('slider_<?php echo $prop['id']; ?>')">›</button>
                            <div class="slider-dots" id="dots_<?php echo $prop['id']; ?>"></div>
                        </div>

                        <div class="card-tag <?php echo $prop['purpose'] == 'Rent' ? 'for-rent' : 'for-sale'; ?>">
                            <?php echo $prop['purpose'] == 'Rent' ? 'For Rent' : 'For Sale'; ?>
                        </div>
                        <?php if (!empty($prop['featured'])): ?>
                            <div class="featured-badge">Featured</div>
                        <?php endif; ?>

                        <a href="javascript:void(0)" class="wishlist-icon" data-id="<?php echo (int) $prop['id']; ?>" onclick="toggleWishlistBuyer(this)" title="Add to wishlist">
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
                                <?php echo $price_suffix; ?>
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
                            <a href="property-detail.php?id=<?php echo $prop['id']; ?>" class="view-detail-btn">View Details</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="1.5">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    <p style="font-size:16px; color:#6B7280;">No properties available at the moment.</p>
                    <p style="font-size:14px;">Check back soon for new listings!</p>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
// ============================================ //
// SLIDER FUNCTIONS (same as home page)
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

// ============================================ //
// WISHLIST TOGGLE (Buyer)
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

function toggleWishlistBuyer(el) {
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
            setTimeout(() => { window.location.href = 'login.php'; }, 1200);
        } else if (data.status === 'added') {
            el.classList.add('active');
            showWishlistToast('Added to wishlist ❤️', false);
        } else if (data.status === 'removed') {
            el.classList.remove('active');
            showWishlistToast('Removed from wishlist', false);
        } else {
            showWishlistToast(data.message || 'Something went wrong', true);
        }
    })
    .catch(() => showWishlistToast('Network error, please try again', true));
}

// ============================================ //
// SEARCH FUNCTION
// ============================================ //
function performSearch() {
    const location = document.getElementById('searchLocation').value;
    const type = document.getElementById('searchType').value;
    const price = document.getElementById('searchPrice').value;
    let url = 'listings.php?';
    if (location) url += 'location=' + encodeURIComponent(location) + '&';
    if (type) url += 'type=' + encodeURIComponent(type) + '&';
    if (price) url += 'price=' + encodeURIComponent(price) + '&';
    window.location.href = url;
}

// ============================================ //
// INIT SLIDERS
// ============================================ //
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#featuredGrid .card-image-slider').forEach(function(slider) {
        const track = slider.querySelector('.slider-track');
        if (track) {
            const images = track.querySelectorAll('img');
            initSlider(slider.id, images.length);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>