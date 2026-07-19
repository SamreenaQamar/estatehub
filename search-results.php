<?php
$page_title = 'Search Results';
include 'includes/config.php';
include 'includes/header.php';

// Get values from URL
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$property_type = isset($_GET['property_type']) ? trim($_GET['property_type']) : '';
$min_price = isset($_GET['min_price']) ? (int)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (int)$_GET['max_price'] : 0;
$purpose = isset($_GET['purpose']) ? trim($_GET['purpose']) : '';

// Build query
$where = [];
$where[] = "status = 'Active'";

if(!empty($location)) {
    $loc = mysqli_real_escape_string($conn, $location);
    $where[] = "(city = '$loc' OR location LIKE '%$loc%')";
}

if(!empty($property_type)) {
    $type = mysqli_real_escape_string($conn, $property_type);
    $where[] = "property_type LIKE '%$type%'";
}

if(!empty($purpose)) {
    $purp = mysqli_real_escape_string($conn, $purpose);
    if($purp == 'buy') {
        $where[] = "purpose = 'Sale'";
    } elseif($purp == 'rent') {
        $where[] = "purpose = 'Rent'";
    }
}

if($min_price > 0) $where[] = "price >= $min_price";
if($max_price > 0) $where[] = "price <= $max_price";

$query = "SELECT * FROM properties WHERE " . implode(" AND ", $where) . " ORDER BY created_at DESC LIMIT 50";
$result = mysqli_query($conn, $query);
$total_results = $result ? mysqli_num_rows($result) : 0;

function buildFilterUrl($removeKey) {
    $params = $_GET;
    unset($params[$removeKey]);
    return 'search-results.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - EstateHub</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        .search-header { background:linear-gradient(135deg,#123524 0%,#0A2318 100%); padding:45px 0 35px; text-align:center; color:white; }
        .search-header h1 { font-size:34px; font-weight:800; margin-bottom:6px; }
        .search-header p { font-size:15px; color:#CBD5E1; }
        .container { max-width:1280px; margin:0 auto; padding:35px 20px; }
        
        .filter-bar { background:white; padding:24px; border-radius:16px; margin-bottom:28px; border:1px solid #E5E7EB; box-shadow:0 1px 3px rgba(0,0,0,0.04); }
        .filter-bar h3 { font-size:16px; font-weight:700; color:#111827; margin-bottom:16px; }
        .filter-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; align-items:end; }
        .filter-field label { display:block; font-size:11px; font-weight:700; color:#374151; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px; }
        .filter-field select, .filter-field input { width:100%; padding:10px 12px; border:1px solid #D1D5DB; border-radius:8px; font-size:13px; background:#F9FAFB; transition:all 0.2s; font-family:'Inter',sans-serif; }
        .filter-field select:focus, .filter-field input:focus { outline:none; border-color:#0E7A4E; box-shadow:0 0 0 3px rgba(37,99,235,0.06); background:white; }
        .filter-btns { display:flex; gap:8px; align-items:end; }
        .btn-search { padding:10px 22px; background:#0E7A4E; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer; font-size:13px; transition:all 0.2s; }
        .btn-search:hover { background:#0A5C3A; }
        .btn-clear { padding:10px 22px; background:white; color:#374151; border:1px solid #D1D5DB; border-radius:8px; font-weight:600; text-decoration:none; font-size:13px; transition:all 0.2s; }
        .btn-clear:hover { background:#F9FAFB; }
        
        .results-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
        .results-count { font-size:15px; color:#374151; }
        .results-count strong { font-size:20px; color:#111827; }
        .filters-row { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .filter-tag { background:#E7F5EC; color:#1E40AF; padding:6px 12px; border-radius:20px; font-size:12px; font-weight:500; display:flex; align-items:center; gap:4px; }
        .filter-tag a { color:#1E40AF; text-decoration:none; font-weight:700; font-size:14px; }
        .filter-tag a:hover { color:#DC2626; }
        .clear-link { color:#DC2626; font-size:13px; font-weight:600; text-decoration:none; margin-left:4px; }
        
        .properties-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
        .property-card { background:white; border:1px solid #E5E7EB; border-radius:14px; overflow:hidden; transition:all 0.3s; display:flex; flex-direction:column; }
        .property-card:hover { transform:translateY(-4px); box-shadow:0 12px 28px rgba(0,0,0,0.1); }
        .card-img { position:relative; height:185px; overflow:hidden; background:#f3f4f6; }
        .card-img img { width:100%; height:100%; object-fit:cover; transition:transform 0.4s; }
        .property-card:hover .card-img img { transform:scale(1.04); }
        .card-tag { position:absolute; top:10px; left:10px; padding:5px 12px; border-radius:6px; font-size:11px; font-weight:700; color:white; z-index:2; }
        .card-tag.sale { background:#0E7A4E; }
        .card-tag.rent { background:#10B981; }
        .card-body { padding:14px; flex:1; display:flex; flex-direction:column; }
        .card-body h3 { font-size:15px; font-weight:700; color:#111827; margin-bottom:4px; }
        .card-loc { font-size:12px; color:#6B7280; margin-bottom:6px; display:flex; align-items:center; gap:4px; }
        .card-loc svg { width:13px; height:13px; flex-shrink:0; }
        .card-price { font-size:17px; font-weight:700; color:#0E7A4E; margin-bottom:8px; }
        .card-features { display:flex; gap:8px; font-size:11px; color:#6B7280; padding-top:8px; border-top:1px solid #F3F4F6; margin-bottom:12px; flex-wrap:wrap; }
        .card-features span { display:flex; align-items:center; gap:3px; }
        .card-features svg { width:12px; height:12px; }
        .view-btn { display:block; text-align:center; padding:8px; background:#0E7A4E; color:white; text-decoration:none; border-radius:7px; font-size:12px; font-weight:600; margin-top:auto; }
        .view-btn:hover { background:#0A5C3A; }
        
        .empty-state { text-align:center; padding:80px 40px; background:white; border-radius:16px; }
        .empty-state svg { width:70px; height:70px; margin-bottom:18px; color:#D1D5DB; }
        .empty-state h3 { font-size:18px; color:#111827; }
        .empty-state p { color:#6B7280; margin-bottom:20px; }
        .btn-back { display:inline-block; padding:11px 24px; background:#0E7A4E; color:white; text-decoration:none; border-radius:8px; font-weight:600; margin:0 5px; }
        .btn-outline { display:inline-block; padding:11px 24px; border:1px solid #D1D5DB; color:#374151; text-decoration:none; border-radius:8px; font-weight:600; }
        @media(max-width:1000px){.properties-grid{grid-template-columns:repeat(2,1fr);}}
        @media(max-width:600px){.properties-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<section class="search-header">
    <h1>Search Results</h1>
    <p>Find your perfect property across Pakistan</p>
</section>

<div class="container">
    
    <!-- SEARCH FILTER BAR -->
    <div class="filter-bar">
        <h3>Refine Your Search</h3>
        <form method="GET" action="search-results.php">
            <div class="filter-grid">
                
                <!-- LOCATION - free text -->
                <div class="filter-field">
                    <label>City</label>
                    <input type="text" name="location" placeholder="e.g. Lahore" value="<?php echo htmlspecialchars($location); ?>">
                </div>
                
                <!-- PROPERTY TYPE - free text -->
                <div class="filter-field">
                    <label>Property Type</label>
                    <input type="text" name="property_type" placeholder="e.g. House" value="<?php echo htmlspecialchars($property_type); ?>">
                </div>
                
                <div class="filter-btns">
                    <button type="submit" class="btn-search">Search</button>
                    <a href="search-results.php" class="btn-clear">Clear All</a>
                </div>
                
            </div>
        </form>
    </div>
    
    <!-- RESULTS -->
    <div class="results-bar">
        <div class="results-count"><strong><?php echo $total_results; ?></strong> properties found<?php if(!empty($location)) echo ' in <strong>'.htmlspecialchars($location).'</strong>'; ?></div>
        <?php if(!empty($location)||!empty($property_type)): ?>
        <div class="filters-row">
            <?php if(!empty($location)): ?><span class="filter-tag"><?php echo htmlspecialchars($location); ?> <a href="<?php echo buildFilterUrl('location'); ?>">&times;</a></span><?php endif; ?>
            <?php if(!empty($property_type)): ?><span class="filter-tag"><?php echo htmlspecialchars($property_type); ?> <a href="<?php echo buildFilterUrl('property_type'); ?>">&times;</a></span><?php endif; ?>
            <a href="search-results.php" class="clear-link">Clear All</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if($total_results > 0): ?>
    <div class="properties-grid">
        <?php while($p = mysqli_fetch_assoc($result)): 
            $img = getPropertyImage($p);
        ?>
        <div class="property-card">
            <div class="card-img">
                <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
                <span class="card-tag <?php echo $p['purpose']=='Rent'?'rent':'sale'; ?>"><?php echo $p['purpose']=='Rent'?'For Rent':'For Sale'; ?></span>
            </div>
            <div class="card-body">
                <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                <div class="card-loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><?php echo htmlspecialchars($p['location']); ?>, <?php echo htmlspecialchars($p['city']); ?></div>
                <div class="card-price">PKR <?php echo number_format($p['price']/1000000,1); ?>M<?php if($p['purpose']=='Rent') echo '<small style="font-size:11px;color:#6B7280;">/Mo</small>'; ?></div>
                <div class="card-features">
                    <?php if($p['bedrooms']): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg><?php echo $p['bedrooms']; ?> Beds</span><?php endif; ?>
                    <?php if($p['bathrooms']): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg><?php echo $p['bathrooms']; ?> Baths</span><?php endif; ?>
                    <?php if($p['area_size']): ?><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg><?php echo $p['area_size']; ?></span><?php endif; ?>
                </div>
                <a href="property-detail.php?id=<?php echo $p['id']; ?>" class="view-btn">View Details</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <h3>No properties found</h3>
        <p>Try adjusting your filters</p>
        <a href="index.php" class="btn-back">Back to Home</a>
        <a href="listings.php" class="btn-outline">View All</a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>