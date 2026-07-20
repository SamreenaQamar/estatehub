<?php
$page_title = 'My Wishlist';
require_once __DIR__ . '/includes/config.php';
include 'includes/header.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// Query to get user's wishlist properties
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

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$wishlist_count = mysqli_num_rows($result);

// ============================================
// UNIQUE IMAGES PER PROPERTY (same logic as listings.php)
// ============================================
function getPropertyImages($type, $id)
{
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Wishlist - EstateHub</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Fraunces:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
body { background: #F8FAFC; }
.container { max-width: 1280px; margin: 0 auto; padding: 0 20px; }

/* ===== HERO ===== */
.wishlist-hero {
    background: linear-gradient(135deg, #0b1a2e 0%, #0E7A4E 100%);
    padding: 60px 0 50px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
}
.wishlist-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}
.wishlist-hero h1 {
    font-family: 'Fraunces', serif;
    font-size: 42px;
    font-weight: 600;
    margin-bottom: 12px;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 1;
}
.wishlist-hero h1 i { color: #ef4444; margin-right: 10px; }
.wishlist-hero p { font-size: 16px; opacity: 0.85; position: relative; z-index: 1; }
.wishlist-count-badge {
    display: inline-block;
    background: rgba(255,255,255,0.15);
    padding: 4px 16px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    margin-top: 10px;
    position: relative;
    z-index: 1;
}

.wishlist-section { padding: 40px 0 60px; background: #F8FAFC; min-height: 400px; }

.wishlist-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
@media (max-width: 992px) { .wishlist-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .wishlist-grid { grid-template-columns: 1fr; } }

/* ============================================ */
/* PREMIUM PROPERTY CARD - IDENTICAL TO HOME     */
/* ============================================ */
.premium-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    background: #fff;
    position: relative;
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

/* Heart wishlist icon - identical to home */
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
.wishlist-icon.active svg { fill: #ef4444; stroke: #ef4444; }

.premium-card .card-body { padding: 20px 20px 18px; }
.card-body h3 { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 4px; line-height: 1.3; }
.card-location {
    font-size: 12px;
    color: #6B7280;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.card-location svg { width: 14px; height: 14px; stroke: currentColor; flex-shrink: 0; }

.premium-card .card-price { font-size: 24px; font-weight: 800; color: #0E7A4E; margin: 8px 0 12px; }
.premium-card .per-month { font-size: 14px; font-weight: 600; color: #64748b; }
.contact-price { font-size: 16px; font-weight: 700; color: #ef4444; }

.premium-card .card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 14px;
    margin-bottom: 14px;
    border-top: 1px solid #eef2f7;
    padding-top: 12px;
}
.premium-card .meta-item { display: flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 500; color: #1e293b; }
.premium-card .meta-item i { font-size: 14px; color: #1e293b; width: 16px; text-align: center; }

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
    margin-top: 4px;
}
.view-detail-btn:hover {
    background: #0a5c3a;
    transform: scale(1.01);
    box-shadow: 0 8px 25px rgba(14,122,78,0.3);
    color: #fff;
}

/* fade-out when removed */
.premium-card.removing {
    opacity: 0;
    transform: scale(0.9);
    transition: opacity 0.35s ease, transform 0.35s ease;
}

/* ===== EMPTY STATE ===== */
.empty-wishlist {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    border: 1px solid #edf2f7;
}
.empty-wishlist .empty-icon { font-size: 72px; margin-bottom: 20px; }
.empty-wishlist h3 { font-size: 24px; font-weight: 800; color: #0b1a2e; margin: 20px 0 10px; }
.empty-wishlist p { color: #64748b; margin-bottom: 24px; font-size: 15px; }
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

@media (max-width: 768px) {
    .wishlist-hero { padding: 40px 0 30px; }
    .wishlist-hero h1 { font-size: 30px; }
    .premium-card .card-image-slider { height: 180px; }
}
</style>
</head>
<body>

<section class="wishlist-hero">
    <div class="container">
        <h1><i class="fas fa-heart"></i> My Wishlist</h1>
        <p>Properties you've saved for later</p>
        <span class="wishlist-count-badge" id="wishlistCountBadge"><?php echo $wishlist_count; ?> <?php echo $wishlist_count == 1 ? 'property' : 'properties'; ?></span>
    </div>
</section>

<section class="wishlist-section">
    <div class="container">

        <?php if ($wishlist_count > 0): ?>
            <div class="wishlist-grid" id="wishlistGrid">
                <?php while ($prop = mysqli_fetch_assoc($result)):
                    $images = getPropertyImages($prop['property_type'], $prop['property_id']);
                    $price_formatted = number_format($prop['price'] / 1000000, 1);
                    $price_display = $prop['price'] > 0 ? 'PKR ' . $price_formatted . 'M' : '<span class="contact-price">Contact for Price</span>';
                    $price_suffix = ($prop['purpose'] == 'Rent' && $prop['price'] > 0) ? ' <span class="per-month">/ Month</span>' : '';
                ?>
                    <div class="property-card premium-card" data-property-id="<?php echo $prop['property_id']; ?>">
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

                        <a href="javascript:void(0)" class="wishlist-icon active" data-title="<?php echo htmlspecialchars($prop['title']); ?>" onclick="toggleWishlistHome(this)" title="Remove from wishlist">
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
                            <a href="property-detail.php?id=<?php echo $prop['property_id']; ?>" class="view-detail-btn">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist" id="emptyWishlist">
                <div class="empty-icon">💔</div>
                <h3>Your wishlist is empty</h3>
                <p>Start adding properties to your wishlist by clicking the heart icon on any property.</p>
                <a href="listings.php" class="browse-btn">Browse Properties</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
// ============================================ //
// SLIDER FUNCTIONS - SAME AS HOME PAGE          //
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
// WISHLIST TOGGLE (heart icon) - AJAX          //
// Same endpoint/behaviour as home page, but    //
// on this page a successful "removed" fades    //
// the card out and removes it from the grid.   //
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

function toggleWishlistHome(el) {
    const title = el.getAttribute('data-title');
    const card = el.closest('.property-card');

    fetch('toggle-wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'ajax=1&property_title=' + encodeURIComponent(title)
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

<?php include 'includes/footer.php'; ?>
</body>
</html>