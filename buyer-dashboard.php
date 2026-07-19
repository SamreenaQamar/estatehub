<?php
$page_title = 'Buyer Dashboard';
include 'includes/config.php';
include 'includes/header.php';

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

$properties_query = "SELECT * FROM properties WHERE status = 'Active' ORDER BY created_at DESC";
$properties_result = mysqli_query($conn, $properties_query);
?>
<style>
.buyer-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 30px 20px;
}
.welcome-banner {
    background: linear-gradient(135deg, #123524 0%, #0A2318 100%);
    border-radius: 16px;
    padding: 25px 30px;
    color: white;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}
.welcome-banner h1 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.welcome-banner p {
    opacity: 0.8;
    font-size: 14px;
}
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}
.section-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
}
.section-header a {
    color: #0E7A4E;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.properties-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    margin-bottom: 40px;
}
.property-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    transition: transform 0.3s;
    position: relative;
}
.property-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.property-card img {
    width: 100%;
    height: 200px;
    object-fit: cover;
}
.card-tag {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #0E7A4E;
    color: white;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.card-tag.for-rent {
    background: #10B981;
}
.wishlist-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 34px;
    height: 34px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s;
}
.wishlist-btn svg {
    width: 18px;
    height: 18px;
    stroke: #6B7280;
    fill: none;
}
.wishlist-btn:hover svg {
    stroke: #DC2626;
    fill: #DC2626;
}
.wishlist-btn.active svg {
    stroke: #DC2626;
    fill: #DC2626;
}
.card-body {
    padding: 15px;
}
.card-body h3 {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 5px;
    color: #111827;
}
.card-location {
    font-size: 13px;
    color: #6B7280;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 5px;
}
.card-location svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}
.card-price {
    font-size: 18px;
    font-weight: 700;
    color: #0E7A4E;
    margin-bottom: 10px;
}
.card-features {
    display: flex;
    gap: 12px;
    font-size: 12px;
    color: #6B7280;
    padding-top: 10px;
    border-top: 1px solid #E5E7EB;
    flex-wrap: wrap;
}
.card-features span {
    display: flex;
    align-items: center;
    gap: 4px;
}
.card-features svg {
    width: 14px;
    height: 14px;
}
.view-btn {
    display: block;
    width: 100%;
    margin-top: 12px;
    padding: 8px;
    background: #0E7A4E;
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s;
}
.view-btn:hover {
    background: #0A5C3A;
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
@media (max-width: 900px) {
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

<div class="buyer-container">
    <div class="welcome-banner">
        <div>
            <h1>
                Welcome, <?php echo htmlspecialchars($user_name); ?>!
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
            </h1>
            <p>Find your dream home from our premium listings across Pakistan</p>
        </div>
    </div>

    <div class="section-header">
        <h2>Available Properties</h2>
        <a href="listings.php">
            View All 
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="5" y1="12" x2="19" y2="12"/>
                <polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>
    </div>

    <?php if ($properties_result && mysqli_num_rows($properties_result) > 0): ?>
    <div class="properties-grid">
        <?php while ($prop = mysqli_fetch_assoc($properties_result)): 
            $image_url = getPropertyImage($prop);
            
            $price_display = 'PKR ' . number_format($prop['price'] / 1000000, 1) . 'M';
            $is_rent = isset($prop['purpose']) && $prop['purpose'] == 'Rent';
        ?>
        <div class="property-card">
            <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($prop['title']); ?>">
            <span class="card-tag <?php echo $is_rent ? 'for-rent' : ''; ?>">
                <?php echo $is_rent ? 'For Rent' : 'For Sale'; ?>
            </span>
            <button class="wishlist-btn" onclick="toggleWishlist(<?php echo $prop['id']; ?>, this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
            </button>
            <div class="card-body">
                <h3><?php echo htmlspecialchars($prop['title']); ?></h3>
                <div class="card-location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <?php echo htmlspecialchars($prop['location']); ?>, <?php echo htmlspecialchars($prop['city']); ?>
                </div>
                <div class="card-price"><?php echo $price_display; ?></div>
                <div class="card-features">
                    <?php if (!empty($prop['bedrooms'])): ?>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        <?php echo $prop['bedrooms']; ?> Beds
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($prop['bathrooms'])): ?>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>
                        <?php echo $prop['bathrooms']; ?> Baths
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($prop['area_size'])): ?>
                    <span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                        <?php echo $prop['area_size']; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <a href="property-detail.php?id=<?php echo $prop['id']; ?>" class="view-btn">View Details</a>
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

<script>
function toggleWishlist(propertyId, btn) {
    fetch('toggle-wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'ajax=1&property_id=' + propertyId
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'added') {
            btn.classList.add('active');
        } else if (data.status === 'removed') {
            btn.classList.remove('active');
        } else if (data.status === 'login_required') {
            window.location.href = 'login.php';
        }
    })
    .catch(() => {});
}
</script>

<?php include 'includes/footer.php'; ?>