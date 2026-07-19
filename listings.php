<?php
$page_title = 'All Properties';
require_once __DIR__ . '/includes/config.php';
include 'includes/header.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$purpose = isset($_GET['purpose']) ? trim($_GET['purpose']) : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$bedrooms = isset($_GET['bedrooms']) ? (int)$_GET['bedrooms'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;

$where = ["p.status = 'Active'"];

if (!empty($search)) {
    $s = mysqli_real_escape_string($conn, $search);
    $where[] = "(p.title LIKE '%$s%' OR p.location LIKE '%$s%' OR p.city LIKE '%$s%')";
}

if (!empty($city)) {
    $c = mysqli_real_escape_string($conn, $city);
    $where[] = "(p.city = '$c' OR p.location LIKE '%$c%')";
}

if (!empty($type)) {
    $t = mysqli_real_escape_string($conn, $type);
    $where[] = "p.property_type = '$t'";
}

if (!empty($purpose)) {
    $pur = mysqli_real_escape_string($conn, $purpose);
    $where[] = "p.purpose = '$pur'";
}

if ($min_price > 0) {
    $where[] = "p.price >= $min_price";
}

if ($max_price > 0) {
    $where[] = "p.price <= $max_price";
}

if ($bedrooms > 0) {
    $where[] = "p.bedrooms >= $bedrooms";
}

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
          JOIN users u ON p.user_id = u.id 
          WHERE $where_clause 
          ORDER BY $order 
          LIMIT $offset, $per_page";

$result = mysqli_query($conn, $query);

$db_cities = [];
$cities_result = mysqli_query($conn, "SELECT DISTINCT city FROM properties WHERE status = 'Active' ORDER BY city");
if ($cities_result) {
    while ($r = mysqli_fetch_assoc($cities_result)) {
        $db_cities[] = $r['city'];
    }
}
$cities = $db_cities;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Listings - EstateHub</title>
    <style>
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

        .listings-layout {
            display: flex;
            gap: 25px;
            padding: 30px 0 50px;
            max-width: 1320px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        .filters-sidebar {
            width: 265px;
            flex-shrink: 0;
            background: white;
            border-radius: 16px;
            padding: 22px;
            border: 1px solid #E5E7EB;
            height: fit-content;
            position: sticky;
            top: 90px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .filters-sidebar h3 {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0E7A4E;
        }

        .filter-group {
            margin-bottom: 13px;
        }

        .filter-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #6B7280;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 9px 11px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-size: 13px;
            background: #F9FAFB;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #0E7A4E;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.06);
            background: white;
        }

        .price-inputs {
            display: flex;
            gap: 6px;
        }

        .price-inputs input {
            width: 50%;
        }

        .filter-btn {
            width: 100%;
            padding: 11px;
            background: #0E7A4E;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 6px;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            background: #0A5C3A;
        }

        .reset-btn {
            display: block;
            text-align: center;
            padding: 9px;
            color: #6B7280;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            margin-top: 6px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .reset-btn:hover {
            color: #DC2626;
            background: #FEF2F2;
        }

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
            padding: 8px 12px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            font-size: 12px;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
        }

        .properties-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .property-card {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 14px;
            overflow: hidden;
            transition: all 0.25s;
            display: flex;
            flex-direction: column;
        }

        .property-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .card-img {
            position: relative;
            height: 185px;
            overflow: hidden;
            background: #f3f4f6;
        }

        .card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s;
        }

        .property-card:hover .card-img img {
            transform: scale(1.03);
        }

        .card-tag {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 4px 11px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
            color: white;
            z-index: 2;
        }

        .card-tag.sale {
            background: #0E7A4E;
        }

        .card-tag.rent {
            background: #10B981;
        }

        .featured-star {
            position: absolute;
            top: 10px;
            left: 85px;
            background: #F59E0B;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            z-index: 2;
        }

        .card-body {
            padding: 13px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .card-body h3 {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 3px;
            line-height: 1.3;
        }

        .card-loc {
            font-size: 11px;
            color: #6B7280;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .card-loc svg {
            width: 12px;
            height: 12px;
            flex-shrink: 0;
        }

        .card-price {
            font-size: 16px;
            font-weight: 700;
            color: #0E7A4E;
            margin-bottom: 6px;
        }

        .card-price small {
            font-size: 10px;
            font-weight: 400;
            color: #6B7280;
        }

        .card-features {
            display: flex;
            gap: 8px;
            font-size: 10px;
            color: #6B7280;
            padding-top: 7px;
            border-top: 1px solid #F3F4F6;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .card-features span {
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .card-features svg {
            width: 12px;
            height: 12px;
        }

        .view-btn {
            display: block;
            text-align: center;
            padding: 8px;
            background: #0E7A4E;
            color: white;
            text-decoration: none;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            margin-top: auto;
            transition: all 0.2s;
        }

        .view-btn:hover {
            background: #0A5C3A;
        }

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

        @media (max-width: 1100px) {
            .properties-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 850px) {
            .listings-layout {
                flex-direction: column;
            }
            .filters-sidebar {
                width: 100%;
                position: static;
                max-height: none;
            }
        }

        @media (max-width: 550px) {
            .properties-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<section class="listings-hero">
    <h1>All Properties</h1>
    <p>Browse <?php echo $total_properties; ?>+ properties across Pakistan</p>
</section>

<div class="listings-layout">

    <aside class="filters-sidebar">
        <h3>Filter Properties</h3>
        <form method="GET" action="listings.php">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" name="search" placeholder="Search property..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label>City (<?php echo count($cities); ?> Cities)</label>
                <select name="city">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?php echo $c; ?>" <?php echo $city == $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Property Type</label>
                <select name="type">
                    <option value="">All Types</option>
                    <?php foreach ($property_types as $pt): ?>
                        <option value="<?php echo $pt; ?>" <?php echo $type == $pt ? 'selected' : ''; ?>><?php echo $pt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Purpose</label>
                <select name="purpose">
                    <option value="">All</option>
                    <option value="Sale" <?php echo $purpose == 'Sale' ? 'selected' : ''; ?>>For Sale</option>
                    <option value="Rent" <?php echo $purpose == 'Rent' ? 'selected' : ''; ?>>For Rent</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Price Range (PKR)</label>
                <div class="price-inputs">
                    <input type="number" name="min_price" placeholder="Min" value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                    <input type="number" name="max_price" placeholder="Max" value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                </div>
            </div>
            <div class="filter-group">
                <label>Min Bedrooms</label>
                <select name="bedrooms">
                    <option value="">Any</option>
                    <option value="1" <?php echo $bedrooms == 1 ? 'selected' : ''; ?>>1+ Bed</option>
                    <option value="2" <?php echo $bedrooms == 2 ? 'selected' : ''; ?>>2+ Beds</option>
                    <option value="3" <?php echo $bedrooms == 3 ? 'selected' : ''; ?>>3+ Beds</option>
                    <option value="4" <?php echo $bedrooms == 4 ? 'selected' : ''; ?>>4+ Beds</option>
                    <option value="5" <?php echo $bedrooms == 5 ? 'selected' : ''; ?>>5+ Beds</option>
                </select>
            </div>
            <button type="submit" class="filter-btn">Apply Filters</button>
            <a href="listings.php" class="reset-btn">Reset All Filters</a>
        </form>
    </aside>

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
                <?php while ($p = mysqli_fetch_assoc($result)):
                    $img = getPropertyImage($p);
                ?>
                    <div class="property-card">
                        <div class="card-img">
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
                            <span class="card-tag <?php echo $p['purpose'] == 'Rent' ? 'rent' : 'sale'; ?>">
                                <?php echo $p['purpose'] == 'Rent' ? 'For Rent' : 'For Sale'; ?>
                            </span>
                            <?php if ($p['featured']): ?>
                                <span class="featured-star">Featured</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                            <div class="card-loc">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                <?php echo htmlspecialchars($p['location']); ?>, <?php echo htmlspecialchars($p['city']); ?>
                            </div>
                            <div class="card-price">PKR <?php echo number_format($p['price'] / 1000000, 1); ?>M<?php if ($p['purpose'] == 'Rent') echo '<small>/Mo</small>'; ?></div>
                            <div class="card-features">
                                <?php if ($p['bedrooms']): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg><?php echo $p['bedrooms']; ?> Beds</span><?php endif; ?>
                                <?php if ($p['bathrooms']): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg><?php echo $p['bathrooms']; ?> Baths</span><?php endif; ?>
                                <?php if ($p['area_size']): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg><?php echo $p['area_size']; ?></span><?php endif; ?>
                            </div>
                            <a href="property-detail.php?id=<?php echo $p['id']; ?>" class="view-btn">View Details</a>
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

<?php include 'includes/footer.php'; ?>
</body>
</html>