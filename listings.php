<?php
$page_title = 'All Properties';
require_once __DIR__ . '/includes/config.php';
include 'includes/header.php';

// ===== WISHLIST IDS (ID-based — unique, no duplicate-title mismatch) =====
$wishlisted_ids = [];
if (isset($_SESSION['user_id'])) {
    $wl_user_id = (int) $_SESSION['user_id'];
    $wl_query = mysqli_query($conn, "SELECT property_id FROM wishlist WHERE user_id = $wl_user_id");
    if ($wl_query) {
        while ($wl_row = mysqli_fetch_assoc($wl_query)) {
            $wishlisted_ids[] = (int) $wl_row['property_id'];
        }
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$purpose = isset($_GET['purpose']) ? trim($_GET['purpose']) : '';
$price = isset($_GET['price']) ? (int)$_GET['price'] : 0;
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

$where = ["p.status = 'Active'"];  // درست (matches actual DB value)

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(p.title LIKE '%$s%' OR p.location LIKE '%$s%' OR p.city LIKE '%$s%')";
}
if (!empty($city)) {
    $c = mysqli_real_escape_string($conn, $city);
    $where[] = "(p.city = '$c' OR p.location LIKE '%$c%')";
}
if (!empty($location)) {
    $loc = mysqli_real_escape_string($conn, $location);
    $where[] = "(p.city = '$loc' OR p.location LIKE '%$loc%')";
}
if (!empty($type)) {
    $t = mysqli_real_escape_string($conn, $type);
    $where[] = "p.property_type = '$t'";
}
if (!empty($purpose)) {
    $pur = mysqli_real_escape_string($conn, $purpose);
    $where[] = "p.purpose = '$pur'";
}
if ($price > 0) {
    $where[] = "p.price <= $price";
}
if ($min_price > 0) $where[] = "p.price >= $min_price";
if ($max_price > 0) $where[] = "p.price <= $max_price";
if ($bedrooms > 0) $where[] = "p.bedrooms >= $bedrooms";

$order = "p.created_at DESC";
if ($sort == 'price_low') $order = "p.price ASC";
if ($sort == 'price_high') $order = "p.price DESC";

$where_clause = implode(" AND ", $where);

$count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties p WHERE $where_clause");
$total_properties = mysqli_fetch_assoc($count_result)['total'] ?? 0;
$total_pages = ceil($total_properties / $per_page);
$offset = ($page - 1) * $per_page;

$query = "SELECT p.*, u.full_name as owner_name 
          FROM properties p 
          LEFT JOIN users u ON p.user_id = u.id 
          WHERE $where_clause 
          ORDER BY $order 
          LIMIT $offset, $per_page";
$result = mysqli_query($conn, $query);

// All cities for dropdown
$all_cities = [
    'Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad',
    'Multan', 'Peshawar', 'Quetta', 'Gujranwala', 'Sialkot',
    'Bahawalpur', 'Sargodha', 'Hyderabad', 'Sukkur', 'Larkana',
    'Abbottabad', 'Mardan', 'Swat', 'Gwadar', 'Muzaffarabad',
    'Sahiwal', 'Rahim Yar Khan', 'Dera Ghazi Khan', 'Nawabshah',
    'Mirpur Khas', 'Kohat', 'Dera Ismail Khan', 'Turbat', 'Khuzdar'
];

$db_cities = [];
$cities_result = mysqli_query($conn, "SELECT DISTINCT city FROM properties WHERE status = 'Active' ORDER BY city");
if ($cities_result) {
    while ($r = mysqli_fetch_assoc($cities_result)) {
        $db_cities[] = $r['city'];
    }
}
$cities = array_unique(array_merge($all_cities, $db_cities));
sort($cities);

$db_types = [];
$types_result = mysqli_query($conn, "SELECT DISTINCT property_type FROM properties WHERE status = 'Active' ORDER BY property_type");
if ($types_result) {
    while ($r = mysqli_fetch_assoc($types_result)) {
        $db_types[] = $r['property_type'];
    }
}
$property_types = $db_types;
sort($property_types);

// ============================================
// UNIQUE IMAGES PER PROPERTY
// ============================================
function getPropertyImages($type, $id)
{
    switch (strtolower(trim($type))) {

        case 'house':
            $folder = 'house';
            break;

        case 'apartment':
            $folder = 'apartment';
            break;

        case 'plot':
            $folder = 'plot';
            break;

        case 'commercial':
            $folder = 'commercial';
            break;

        case 'farm house':
            $folder = 'farmhouse';
            break;

        case 'villa':
            $folder = 'villa';
            break;

        case 'penthouse':
            $folder = 'penthouse';
            break;

        case 'portion':
            $folder = 'portion';
            break;

        default:
            $folder = 'house';
            break;
    }

    $images = [];

    $start = ($id % 10) + 1;

    for ($i = 0; $i < 4; $i++) {
        $num = (($start + $i - 1) % 10) + 1;
        $images[] = "assets/images/$folder/$num.jpg";
    }

    return $images;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Listings - EstateHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }

        /* LISTINGS HERO */
        .listings-hero {
            background: linear-gradient(135deg, #123524 0%, #0A2318 100%);
            padding: 45px 20px 35px;
            text-align: center;
            color: white;
        }
        .listings-hero h1 {
            font-size: 34px;
            font-weight: 800;
            margin-bottom: 6px;
        }
        .listings-hero p {
            opacity: 0.8;
            font-size: 15px;
        }

        /* LAYOUT */
        .listings-layout {
            display: flex;
            gap: 25px;
            padding: 30px 20px 50px;
            max-width: 1320px;
            margin: 0 auto;
        }

        /* FIND YOUR PROPERTY WIDGET - UNCHANGED */
        .filter-widget {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 8px 25px rgba(0,0,0,.08);
            position: sticky;
            top: 100px;
            height: fit-content;
            align-self: flex-start;
            overflow: hidden;
            transition: all .3s ease;
        }
        .filter-widget:hover {
            box-shadow: 0 14px 35px rgba(0,0,0,.12);
            transform: translateY(-2px);
        }
        .filter-widget h3 {
            font-size: 22px;
            font-weight: 800;
            color: #0b1a2e;
            margin-bottom: 18px;
            letter-spacing: -0.5px;
            position: relative;
        }
        .filter-widget h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #0E7A4E, #16a34a);
            border-radius: 10px;
        }
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .filter-group label {
            font-size: 13px;
            font-weight: 700;
            color: #0b1a2e;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-group label svg {
            stroke: #334155;
            transition: stroke 0.2s;
        }
        .filter-group label:hover svg {
            stroke: #0E7A4E;
        }
        .filter-group select {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 500;
            color: #0b1a2e;
            background: white;
            cursor: pointer;
            transition: 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 14px;
        }
        .filter-group select:focus {
            outline: none;
            border-color: #0E7A4E;
            box-shadow: 0 0 0 4px rgba(14, 122, 78, 0.08);
        }
        .filter-group select:hover {
            border-color: #0E7A4E;
        }
        .filter-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 6px;
        }
        .search-btn {
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(14, 122, 78, 0.25);
            position: relative;
            overflow: hidden;
        }
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(14, 122, 78, 0.35);
        }
        .search-btn:active {
            transform: scale(0.98);
        }
        .search-btn .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: scale(0);
            animation: rippleAnim 0.6s linear;
            pointer-events: none;
        }
        @keyframes rippleAnim {
            to { transform: scale(4); opacity: 0; }
        }
        .search-btn svg {
            stroke: white;
        }
        .reset-btn {
            background: transparent;
            border: 2px solid #d1d5db;
            color: #475569;
            padding: 12px 20px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .reset-btn svg {
            stroke: currentColor;
            transition: 0.3s;
        }
        .reset-btn:hover {
            border-color: #0E7A4E;
            color: #0E7A4E;
            background: rgba(14, 122, 78, 0.04);
            transform: translateY(-1px);
        }
        .reset-btn:active {
            transform: scale(0.97);
        }
        .reset-btn:hover svg {
            stroke: #0E7A4E;
        }
        @media (max-width: 768px) {
            .filter-widget {
                width: 100%;
                position: static;
                max-height: none;
                min-width: auto;
                max-width: none;
            }
        }

        /* PROPERTIES AREA */
        .properties-area {
            flex: 1;
            min-width: 0;
        }
        .results-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .results-count {
            font-size: 14px;
            color: #374151;
        }
        .results-count strong {
            color: #111827;
            font-size: 17px;
        }
        .sort-select {
            padding: 8px 14px;
            border: 1.5px solid #E5E7EB;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            background: white;
            cursor: pointer;
            transition: 0.2s;
            color: #1e293b;
        }
        .sort-select:hover {
            border-color: #0E7A4E;
        }
        .sort-select:focus {
            outline: none;
            border-color: #0E7A4E;
            box-shadow: 0 0 0 3px rgba(14,122,78,0.1);
        }

        /* PROPERTY CARDS - IMPROVED */
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .property-card {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
        }
        .property-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.12);
        }

        .card-image-slider {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: #f3f4f6;
        }
        .card-image-slider .slider-container {
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        .card-image-slider .slider-track {
            display: flex;
            height: 100%;
            transition: transform 0.5s ease;
        }
        .card-image-slider .slider-track img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            flex-shrink: 0;
        }
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
        }
        .property-card:hover .slider-btn {
            opacity: 1;
            pointer-events: auto;
        }
        .slider-btn:hover {
            background: white;
        }
        .slider-prev { left: 8px; }
        .slider-next { right: 8px; }
        .slider-dots {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 5px;
        }
        .slider-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: 0.2s;
        }
        .slider-dot.active {
            background: white;
            width: 16px;
            border-radius: 4px;
        }

        .card-tag {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: white;
            z-index: 2;
        }
        .card-tag.sale { background: #0E7A4E; }
        .card-tag.rent { background: #10B981; }

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
            z-index: 2;
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
    z-index: 2;
}

.wishlist-icon:hover {
    background: rgba(0,0,0,0.6);
}

.wishlist-icon svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: #ffffff;
    transition: all 0.3s ease;
}

/* Wishlist Added */
.wishlist-icon.active {
    background: #ef4444;
}

.wishlist-icon.active svg {
    fill: #ffffff;
    stroke: #ffffff;
}
        .card-body {
            padding: 16px 18px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .card-body h3 {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
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
            font-size: 20px;
            font-weight: 800;
            color: #0E7A4E;
            margin-bottom: 8px;
        }
        .card-price .per-month {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }
        .card-price .contact-price {
            font-size: 16px;
            font-weight: 700;
            color: #ef4444;
        }

        .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 14px;
            margin-bottom: 12px;
            border-top: 1px solid #eef2f7;
            padding-top: 12px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
        }
        .meta-item i {
            font-size: 13px;
            color: #1e293b;
            width: 16px;
            text-align: center;
        }

        .view-detail-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px 0;
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: #fff;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            margin-top: auto;
        }
        .view-detail-btn:hover {
            background: #0a5c3a;
            transform: scale(1.01);
            box-shadow: 0 8px 25px rgba(14,122,78,0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 70px 20px;
            background: white;
            border-radius: 14px;
        }
        .empty-state svg {
            width: 60px;
            height: 60px;
            margin-bottom: 14px;
            color: #D1D5DB;
        }
        .empty-state h3 {
            font-size: 17px;
            color: #111827;
            margin-bottom: 4px;
        }
        .empty-state p {
            color: #6B7280;
            font-size: 13px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            padding: 7px 12px;
            border: 1px solid #E5E7EB;
            border-radius: 7px;
            text-decoration: none;
            color: #111827;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .pagination a:hover {
            background: #0E7A4E;
            color: white;
            border-color: #0E7A4E;
        }
        .pagination .active {
            background: #0E7A4E;
            color: white;
            border-color: #0E7A4E;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 1100px) {
            .properties-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 850px) {
            .listings-layout { flex-direction: column; }
            .filter-widget {
                width: 100%;
                position: static;
                max-height: none;
                min-width: auto;
                max-width: none;
            }
        }
        @media (max-width: 550px) {
            .properties-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- LISTINGS HERO -->
<section class="listings-hero">
    <h1>All Properties</h1>
    <p>Browse <?php echo $total_properties; ?>+ properties across Pakistan</p>
</section>

<!-- MAIN LAYOUT -->
<div class="listings-layout">

    <!-- FIND YOUR PROPERTY WIDGET - UNCHANGED -->
    <div class="filter-widget">
        <h3>Find Your Property</h3>
        <form action="search-results.php" method="GET" class="filter-form" id="filterForm">
            <div class="filter-group">
                <label>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Location
                </label>
                <select name="location">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo (($city == $c || $location == $c) ? 'selected' : ''); ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Property Type
                </label>
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach ($property_types as $pt): ?>
                        <option value="<?php echo $pt; ?>" <?php echo $type == $pt ? 'selected' : ''; ?>><?php echo $pt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="6" x2="12" y2="18"/>
                        <line x1="6" y1="12" x2="18" y2="12"/>
                    </svg>
                    Price
                </label>
                <select name="price">
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
            <div class="filter-actions">
                <button type="submit" class="search-btn" id="searchBtn">
                    <span>Search Property</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>
                <button type="reset" class="reset-btn" id="resetBtn">Reset Filters</button>
            </div>
        </form>
    </div>

    <!-- Properties Area -->
    <div class="properties-area">

        <div class="results-bar">
            <div class="results-count"><strong><?php echo $total_properties; ?></strong> properties found</div>
            <select class="sort-select" onchange="window.location.href = '?<?php $p = $_GET; unset($p['sort']); echo http_build_query($p); ?>&sort=' + this.value;">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
            </select>
        </div>

        <?php if ($total_properties > 0): ?>
            <div class="properties-grid">
                <?php while ($prop = mysqli_fetch_assoc($result)):
                    $images = getPropertyImages($prop['property_type'], $prop['id']);
                    $is_wishlisted = in_array((int) $prop['id'], $wishlisted_ids);
                    // Format price
                    $price_formatted = number_format($prop['price'] / 1000000, 1);
                    $price_display = $prop['price'] > 0 ? 'PKR ' . $price_formatted . 'M' : '<span class="contact-price">Contact for Price</span>';
                    $price_suffix = ($prop['purpose'] == 'Rent' && $prop['price'] > 0) ? ' <span class="per-month">/ Month</span>' : '';
                ?>
                    <div class="property-card">
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
                        <div class="card-tag <?php echo $prop['purpose'] == 'Rent' ? 'rent' : 'sale'; ?>">
                            <?php echo $prop['purpose'] == 'Rent' ? 'For Rent' : 'For Sale'; ?>
                        </div>
                        <?php if ($prop['featured']): ?>
                            <div class="featured-badge">Featured</div>
                        <?php endif; ?>
                        <a href="javascript:void(0)" class="wishlist-icon <?php echo $is_wishlisted ? 'active' : ''; ?>" data-id="<?php echo (int) $prop['id']; ?>" onclick="toggleWishlistHome(this)" title="Add to wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </a>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($prop['title']); ?></h3>
                            <div class="card-location">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
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
                                <?php if ($prop['area_size']): ?>
                                    <span class="meta-item"><i class="fas fa-vector-square"></i> <?php echo $prop['area_size']; ?></span>
                                <?php endif; ?>
                                <span class="meta-item"><i class="fas fa-building"></i> <?php echo $prop['property_type']; ?></span>
                            </div>
                            <a href="property-detail.php?id=<?php echo $prop['id']; ?>" class="view-detail-btn">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php $params = $_GET;
                    if ($page > 1):
                        $params['page'] = $page - 1; ?>
                        <a href="?<?php echo http_build_query($params); ?>">Previous</a>
                    <?php endif;
                    for ($i = 1; $i <= $total_pages; $i++):
                        $params['page'] = $i; ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query($params); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor;
                    if ($page < $total_pages):
                        $params['page'] = $page + 1; ?>
                        <a href="?<?php echo http_build_query($params); ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <h3>No properties found</h3>
                <p>Try adjusting your filters</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ============================================ //
// SLIDER FUNCTIONS                            //
// ============================================ //
let slideIndexes = {};

function initSlider(sliderId, imageCount) {
    slideIndexes[sliderId] = 0;
    updateDots(sliderId, imageCount);
}

function updateDots(sliderId, imageCount) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const dotsContainer = document.getElementById(sliderId.replace('slider', 'dots'));
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

window.prevSlide = function(sliderId) {
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

window.nextSlide = function(sliderId) {
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
// WISHLIST TOGGLE (ID-based)                  //
// ============================================ //
const wishlistedIds = <?php echo json_encode($wishlisted_ids ?? []); ?>;

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

function toggleWishlistHome(el) {
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
// RIPPLE EFFECT & RESET BUTTON                //
// ============================================ //
document.addEventListener('DOMContentLoaded', function() {
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size/2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size/2) + 'px';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    }

    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('filterForm').reset();
        });
    }
});

// ============================================ //
// INIT SLIDERS ON PAGE LOAD                   //
// ============================================ //
document.addEventListener('DOMContentLoaded', function() {
    <?php
    if ($result) {
        mysqli_data_seek($result, 0);
        $ids = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ids[] = $row['id'];
        }
        mysqli_data_seek($result, 0);
    }
    ?>
    const propertyIds = <?php echo json_encode($ids ?? []); ?>;
    propertyIds.forEach(id => {
        const sliderId = 'slider_' + id;
        const slider = document.getElementById(sliderId);
        if (slider) {
            const track = slider.querySelector('.slider-track');
            if (track) {
                const images = track.querySelectorAll('img');
                initSlider(sliderId, images.length);
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>