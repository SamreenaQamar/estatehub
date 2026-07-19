<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/config.php';
include 'includes/header.php';

// Titles already in the logged-in user's wishlist, used to pre-fill the heart icons below.
$wishlisted_titles = [];
if (isset($_SESSION['user_id'])) {
    $wl_user_id = (int) $_SESSION['user_id'];
    $wl_query = mysqli_query($conn, "SELECT p.title FROM wishlist w INNER JOIN properties p ON w.property_id = p.id WHERE w.user_id = $wl_user_id");
    if ($wl_query) {
        while ($wl_row = mysqli_fetch_assoc($wl_query)) {
            $wishlisted_titles[] = $wl_row['title'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dream Home Finder</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', sans-serif;
}

.hero {
    position: relative;
    height: 640px;
    display: flex;
    align-items: flex-start;
    overflow: hidden;
}

/* MOVING BACKGROUND */
.hero-bg {
    position: absolute;
    inset: 0;
    background: url('https://images.unsplash.com/photo-1600585154340-be6161a56a0c') center/cover no-repeat;
    animation: slowZoom 18s infinite alternate;
    transform-origin: center center;
    scale: 1.01;
}

@keyframes slowZoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.08); }
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(0,0,0,0.75), rgba(0,0,0,0.3));
}

.hero-container {
    position: relative;
    z-index: 2;
    max-width: 1200px;
    margin: auto;
    padding: 0 30px;
    width: 100%;
}
/* 🔥 SIDE SLIDE ANIMATION (CLEAN + PREMIUM) */
.hero-text {
    margin-top: -40px;
}

.hero-text h1,
.hero-text p {
    opacity: 0;
    transform: translateX(-60px); /* 👈 left se start */
    animation: slideIn 0.9s ease forwards;
}

/* second line delay */
.hero-text h1:nth-child(2) {
    animation-delay: 0.2s;
}

/* paragraph delay */
.hero-text p {
    animation-delay: 0.4s;
}

/* animation */
@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* text style */
.hero-text h1 {
    font-family: 'Fraunces', serif;
    font-size: 64px;
    font-weight: 500;
    color: white;
    line-height: 1.1;
    letter-spacing: -0.5px;
}

.dream-text {
    font-style: italic;
    color: #6EE7B7;
    font-weight: 600;
}

.hero-text p {
    color: #ddd;
    margin: 10px 0 25px;
}

.section-title-wrapper h2 {
    font-family: 'Fraunces', serif;
    font-size: 32px;
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.3px;
}


/* ============================================ */
/* TABS - EXACTLY LIKE REFERENCE IMAGE         */
/* ============================================ */

.tabs{
    display:flex;
    gap:15px;
    margin-top:30px;
    margin-bottom:70px;
}

.tabs .tab{
    padding:8px 24px;
    border-radius:6px;
    border:1px solid rgba(255,255,255,.35);
    background:transparent;
    color:#fff;
    font-size:15px;
    font-weight:500;
    cursor:pointer;
    transition:all .35s ease;
}

/* Hover */
.tabs .tab:hover{
    background:rgba(14,122,78,.25);
    border-color:#16a34a;
    color:#fff;
    transform:translateY(-2px);
    box-shadow:0 8px 18px rgba(22,163,74,.25);
}

/* Active Button */
.tabs .tab.active{
    background:#0E7A4E;
    border-color:#0E7A4E;
    color:#fff;
    box-shadow:0 10px 22px rgba(14,122,78,.35);
}

/* Click Animation */
.tabs .tab:active{
    transform:scale(.97);
}
/* Search Card */
.search-card {
    position: absolute;
    bottom: -60px;
    left: 50%;
    transform: translateX(-50%);
    width: 95%;
    max-width: 1200px;
    background: white;
    border-radius: 18px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}

.search-row {
    display: flex;
    align-items: center;
}

/* Field */
.search-field {
    flex: 1;
    padding: 15px;
    border-right: 1px solid #eee;
}

.search-field:last-child {
    border-right: none;
}

.search-field label {
    font-size: 11px;
    color: #000000;
    margin-bottom: 5px;
    display: block;
}

.input {
    display: flex;
    align-items: center;
    gap: 8px;
}

.input svg {
    width: 18px;
    height: 18px;
}

select {
    border: none;
    outline: none;
    background: transparent;
    width: 100%;
    font-size: 14px;
}

/* Button */
.search-btn {
    background: #0E7A4E;
    color: white;
    border: none;
    padding: 0 25px;
    height: 55px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 10px;
    cursor: pointer;
    font-weight: 500;
}

.search-btn svg {
    stroke: white;
    width: 18px;
}
/* ============================================ */
/* FEATURE STRIP - COMPLETE FIXED CSS          */
/* ============================================ */

.feature-strip {
    background: #F9FAFB;
    display: flex;
    flex-direction: row;
    justify-content: space-around;
    align-items: center;
    padding: 40px 60px;
    margin-top: 30px;
    border-bottom: 1px solid #E5E7EB;
    flex-wrap: wrap;
    gap: 20px;
    width: 100%;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
    min-width: 200px;
    justify-content: center;
}

.feature-icon {
    width: 35px;
    height: 35px;
    background: #E7F5EC;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.feature-icon svg {
    width: 35px;
    height: 35px;
    stroke: #0E7A4E;
    stroke-width: 1.8;
    fill: none;
}

.feature-text {
    text-align: left;
}

.feature-text h4 {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 4px;
}

.feature-text p {
    font-size: 16px;
    color: #6B7280;
    margin: 0;
}

/* Responsive for Feature Strip */
@media (max-width: 900px) {
    .feature-strip {
        flex-direction: column;
        gap: 25px;
        padding: 30px;
    }
    
    .feature-item {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>
</head>

<body>

<section class="hero">

    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>

    <div class="hero-container">

        <!-- HERO TEXT -->
<div class="hero-text">
    <h1>
        Find Your <br>
        <span class="dream-text">Dream</span> Home
    </h1>
    <p>Discover the best properties for sale or rent all over Pakistan</p>

<!-- TABS - Functional Buy, Rent, PG -->
<div class="tabs" id="propertyTabs">
    <!-- ✅ FIXED: pass 'event' so the button is received correctly -->
    <button class="tab active" data-type="buy" onclick="goToHome('Sale', event)">Buy</button>
    <button class="tab" data-type="rent" onclick="goToHome('Rent', event)">Rent</button>
    <button class="tab" data-type="pg" onclick="goToHome('PG', event)">PG</button>
</div>
  <!-- ✅ IMPORTANT CLOSE -->

    
       <!-- SEARCH CARD -->
<div class="search-card">
    <form class="search-form" action="search-results.php" method="GET">
        <div class="search-row">

            <!-- LOCATION - All 29 Cities -->
            <div class="search-field">
                <label>Location</label>
                <div class="input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#a6a7a4" stroke-width="1.8">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                        <circle cx="12" cy="9" r="2.5" fill="white"/>
                    </svg>
                    <select name="location">
                        <option value="">All Cities</option>
                        <option value="Lahore">Lahore</option>
                        <option value="Karachi">Karachi</option>
                        <option value="Islamabad">Islamabad</option>
                        <option value="Rawalpindi">Rawalpindi</option>
                        <option value="Faisalabad">Faisalabad</option>
                        <option value="Multan">Multan</option>
                        <option value="Peshawar">Peshawar</option>
                        <option value="Quetta">Quetta</option>
                        <option value="Gujranwala">Gujranwala</option>
                        <option value="Sialkot">Sialkot</option>
                        <option value="Bahawalpur">Bahawalpur</option>
                        <option value="Sargodha">Sargodha</option>
                        <option value="Hyderabad">Hyderabad</option>
                        <option value="Sukkur">Sukkur</option>
                        <option value="Larkana">Larkana</option>
                        <option value="Abbottabad">Abbottabad</option>
                        <option value="Mardan">Mardan</option>
                        <option value="Swat">Swat</option>
                        <option value="Gwadar">Gwadar</option>
                        <option value="Muzaffarabad">Muzaffarabad</option>
                        <option value="Sahiwal">Sahiwal</option>
                        <option value="Rahim Yar Khan">Rahim Yar Khan</option>
                        <option value="Dera Ghazi Khan">Dera Ghazi Khan</option>
                        <option value="Nawabshah">Nawabshah</option>
                        <option value="Mirpur Khas">Mirpur Khas</option>
                        <option value="Kohat">Kohat</option>
                        <option value="Dera Ismail Khan">Dera Ismail Khan</option>
                        <option value="Turbat">Turbat</option>
                        <option value="Khuzdar">Khuzdar</option>
                    </select>
                </div>
            </div>

            <!-- PROPERTY TYPE - All 8 Types -->
            <div class="search-field">
                <label>Property Type</label>
                <div class="input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#a6a7a4" stroke-width="1.8">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-8H9v8H5a2 2 0 0 1-2-2z"/>
                    </svg>
                    <select name="property_type">
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
            </div>

            <!-- PRICE -->
<div class="search-field">
    <label>Price</label>
    <div class="input">
        <svg viewBox="0 0 24 24" fill="none" stroke="#a6a7a4" stroke-width="2">
            <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>

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
</div>

            <button type="submit" class="search-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                Search Property
            </button>

        </div>
    </form>
</div>

</section>

</body>
</html>
<!-- ============================================ -->
<!-- FEATURE STRIP - 4 ITEMS IN ROW              -->
<!-- ============================================ -->
<section class="feature-strip">
    <div class="feature-item">
        <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </div>
        <div class="feature-text">
            <h4>40+ Properties</h4>
            <p>Available for you</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                <circle cx="12" cy="12" r="10"/>
                <line x1="2" y1="12" x2="22" y2="12"/>
                <path d="M12 2a15 15 0 0 0 0 20 15 15 0 0 0 0-20z"/>
            </svg>
        </div>
        <div class="feature-text">
            <h4>All Over Pakistan</h4>
            <p>100+ Cities</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                <path d="M12 2a10 10 0 0 1 10 10c0 5.5-4 10-10 10S2 17.5 2 12 6.5 2 12 2z"/>
                <path d="M12 6v6l3 2"/>
            </svg>
        </div>
        <div class="feature-text">
            <h4>Trusted by Thousands</h4>
            <p>Happy Customers</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <div class="feature-text">
            <h4>Easy & Secure</h4>
            <p>Best Platform</p>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- MIDDLE CONTENT: FEATURED + SIDEBAR          -->
<!-- ============================================ -->
<section class="main-content">
    <div class="container">
        <div class="content-wrapper">
            
            <!-- LEFT COLUMN: Featured Properties -->
            <div class="properties-column">
                <div class="section-title-wrapper">
                    <h2>Featured Properties</h2>
                    <a href="listings.php" class="view-all">View All →</a>
                </div>
                
                <div class="properties-grid">
                    
                  <!-- Property Card 1 - For Sale -->
<div class="property-card">
    <div class="card-image-slider" id="slider1">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg" alt="Modern House">
                <img src="https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg" alt="Kitchen">
                <img src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg" alt="Bedroom">
                <img src="https://images.pexels.com/photos/1571459/pexels-photo-1571459.jpeg" alt="Living Room">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider1')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider1')">›</button>
        <div class="slider-dots" id="dots1"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <div class="featured-badge">Featured</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Modern 10 Marla House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
    </a>
    <div class="card-body">
        <h3>Modern 10 Marla House</h3>
        <div class="card-location">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            DHA Phase 6, Lahore
        </div>
        <div class="card-price">PKR 5.2M</div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>5 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>6 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>10 Marla</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>Ready</span>
        </div>
        <a href="property-detail.php?id=1" class="view-detail-btn">View Details</a>
    </div>
</div>
                                       <!-- Property Card 2 - For Rent -->
                    <div class="property-card">
                        <div class="card-image-slider" id="slider2">
                            <div class="slider-container">
                                <div class="slider-track">
                                    <img src="https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg" alt="Apartment">
                                    <img src="https://images.pexels.com/photos/2587056/pexels-photo-2587056.jpeg" alt="Kitchen">
                                    <img src="https://images.pexels.com/photos/2587052/pexels-photo-2587052.jpeg" alt="Bedroom">
                                    <img src="https://images.pexels.com/photos/2587058/pexels-photo-2587058.jpeg" alt="Living Area">
                                </div>
                            </div>
                            <button class="slider-btn slider-prev" onclick="prevSlide('slider2')">‹</button>
                            <button class="slider-btn slider-next" onclick="nextSlide('slider2')">›</button>
                            <div class="slider-dots" id="dots2"></div>
                        </div>
                        <div class="card-tag for-rent">For Rent</div>
                        <a href="javascript:void(0)" class="wishlist-icon" data-title="Luxury Apartment" onclick="toggleWishlistHome(this)" title="Add to wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </a>
                        <div class="card-body">
                            <h3>Luxury Apartment</h3>
                            <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Bahria Town, Karachi</div>
                            <div class="card-price">PKR 1.2M <span class="per-month">/ Month</span></div>
                            <div class="card-meta">
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>3 Beds</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>3 Baths</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>2000 Sqft</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h20M12 2v20"/></svg>Furnished</span>
                            </div>
                            <a href="property-detail.php?id=2" class="view-detail-btn">View Details</a>
                        </div>
                    </div>
                    <!-- Property Card 3 - For Sale -->
                    <div class="property-card">
                        <div class="card-image-slider" id="slider3">
                            <div class="slider-container">
                                <div class="slider-track">
                                    <img src="https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg" alt="Villa">
                                    <img src="https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg" alt="Kitchen">
                                    <img src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg" alt="Bedroom">
                                    <img src="https://images.pexels.com/photos/1571458/pexels-photo-1571458.jpeg" alt="Living">
                                </div>
                            </div>
                            <button class="slider-btn slider-prev" onclick="prevSlide('slider3')">‹</button>
                            <button class="slider-btn slider-next" onclick="nextSlide('slider3')">›</button>
                            <div class="slider-dots" id="dots3"></div>
                        </div>
                        <div class="card-tag for-sale">For Sale</div>
                        <div class="featured-badge">Featured</div>
<a href="javascript:void(0)" class="wishlist-icon" data-title="Designer Villa" onclick="toggleWishlistHome(this)" title="Add to wishlist">                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </a>
                        <div class="card-body">
                            <h3>Designer Villa</h3>
                            <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>F-10, Islamabad</div>
                            <div class="card-price">PKR 7.8M</div>
                            <div class="card-meta">
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>6 Beds</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>7 Baths</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>1 Kanal</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M12 3v18"/></svg>Luxury</span>
                            </div>
<a href="property-detail.php?id=2" class="view-detail-btn">View Details</a>                        </div>
                    </div>

                    <!-- Property Card 4 - For Sale -->
                    <div class="property-card">
                        <div class="card-image-slider" id="slider4">
                            <div class="slider-container">
                                <div class="slider-track">
                                    <img src="https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg" alt="House">
                                    <img src="https://images.pexels.com/photos/280232/pexels-photo-280232.jpeg" alt="Kitchen">
                                    <img src="https://images.pexels.com/photos/280233/pexels-photo-280233.jpeg" alt="Bedroom">
                                    <img src="https://images.pexels.com/photos/280234/pexels-photo-280234.jpeg" alt="Dining">
                                </div>
                            </div>
                            <button class="slider-btn slider-prev" onclick="prevSlide('slider4')">‹</button>
                            <button class="slider-btn slider-next" onclick="nextSlide('slider4')">›</button>
                            <div class="slider-dots" id="dots4"></div>
                        </div>
                        <div class="card-tag for-sale">For Sale</div>
                        <a href="javascript:void(0)" class="wishlist-icon" data-title="Double Kitchen House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </a>
                        <div class="card-body">
                            <h3>Double Kitchen House</h3>
                            <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Model Town, Lahore</div>
                            <div class="card-price">PKR 3.45M</div>
                            <div class="card-meta">
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>4 Beds</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>4 Baths</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>8 Marla</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>2 Kitchens</span>
                            </div>
                            <a href="property-detail.php?id=4" class="view-detail-btn">View Details</a>
                        </div>
                    </div>

                    <!-- Property Card 5 - For Rent -->
                    <div class="property-card">
                        <div class="card-image-slider" id="slider5">
                            <div class="slider-container">
                                <div class="slider-track">
                                    <img src="https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg" alt="Penthouse">
                                    <img src="https://images.pexels.com/photos/258158/pexels-photo-258158.jpeg" alt="Living">
                                    <img src="https://images.pexels.com/photos/258159/pexels-photo-258159.jpeg" alt="Bedroom">
                                    <img src="https://images.pexels.com/photos/258160/pexels-photo-258160.jpeg" alt="Bathroom">
                                </div>
                            </div>
                            <button class="slider-btn slider-prev" onclick="prevSlide('slider5')">‹</button>
                            <button class="slider-btn slider-next" onclick="nextSlide('slider5')">›</button>
                            <div class="slider-dots" id="dots5"></div>
                        </div>
                        <div class="card-tag for-rent">For Rent</div>
                        <div class="featured-badge">Hot Deal</div>
                        <a href="javascript:void(0)" class="wishlist-icon" data-title="Luxury Penthouse" onclick="toggleWishlistHome(this)" title="Add to wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </a>
                        <div class="card-body">
                            <h3>Luxury Penthouse</h3>
                            <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Clifton, Karachi</div>
                            <div class="card-price">PKR 2.5M <span class="per-month">/ Month</span></div>
                            <div class="card-meta">
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>4 Beds</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>5 Baths</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>3500 Sqft</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>Sea View</span>
                            </div>
                            <a href="property-detail.php?id=5" class="view-detail-btn">View Details</a>
                        </div>
                    </div>

                    <!-- Property Card 6 - For Sale -->
                    <div class="property-card">
                        <div class="card-image-slider" id="slider6">
                            <div class="slider-container">
                                <div class="slider-track">
                                    <img src="https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg" alt="Farm House">
                                    <img src="https://images.pexels.com/photos/208740/pexels-photo-208740.jpeg" alt="Garden">
                                    <img src="https://images.pexels.com/photos/208738/pexels-photo-208738.jpeg" alt="Interior">
                                    <img src="https://images.pexels.com/photos/208739/pexels-photo-208739.jpeg" alt="Pool">
                                </div>
                            </div>
                            <button class="slider-btn slider-prev" onclick="prevSlide('slider6')">‹</button>
                            <button class="slider-btn slider-next" onclick="nextSlide('slider6')">›</button>
                            <div class="slider-dots" id="dots6"></div>
                        </div>
                        <div class="card-tag for-sale">For Sale</div>
                        <a href="javascript:void(0)" class="wishlist-icon" data-title="Modern Farm House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </a>
                        <div class="card-body">
                            <h3>Modern Farm House</h3>
                            <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Gulberg Greens, Islamabad</div>
                            <div class="card-price">PKR 9.5M</div>
                            <div class="card-meta">
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>5 Beds</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>6 Baths</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>2 Kanal</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4 L20 4 L20 20 L4 20 Z"/><line x1="4" y1="8" x2="20" y2="8"/></svg>Pool+Garden</span>
                            </div>
                            <a href="property-detail.php?id=6" class="view-detail-btn">View Details</a>
                        </div>
                    </div>

                    <!-- Property Card 7 - For Sale -->
                    <div class="property-card">
                        <div class="card-image-slider" id="slider7">
                            <div class="slider-container">
                                <div class="slider-track">
                                    <img src="https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg" alt="Bahria Town Luxury Home">
                                    <img src="https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg" alt="View">
                                    <img src="https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg" alt="Bedroom">
                                    <img src="https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg" alt="Terrace">
                                </div>
                            </div>
                            <button class="slider-btn slider-prev" onclick="prevSlide('slider7')">‹</button>
                            <button class="slider-btn slider-next" onclick="nextSlide('slider7')">›</button>
                            <div class="slider-dots" id="dots7"></div>
                        </div>
                        <div class="card-tag for-sale">For Sale</div>
                        <div class="featured-badge">Featured</div>
                        <a href="javascript:void(0)" class="wishlist-icon" data-title="Bahria Town Luxury Home" onclick="toggleWishlistHome(this)" title="Add to wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </a>
                        <div class="card-body">
                            <h3>Bahria Town Luxury Home</h3>
                            <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Bahria Town, Lahore</div>
                            <div class="card-price">PKR 8.5M</div>
                            <div class="card-meta">
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>5 Beds</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>6 Baths</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>1 Kanal</span>
                                <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20"/></svg>Modern Amenities</span>
                            </div>
                            <a href="property-detail.php?id=9" class="view-detail-btn">View Details</a>
                        </div>
                    </div>

                    <!-- Property Card 8 - For Rent -->
<div class="property-card">
    <div class="card-image-slider" id="slider8">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg" alt="Cozy Studio">
                <img src="https://images.pexels.com/photos/1643384/pexels-photo-1643384.jpeg" alt="Studio Kitchen">
                <img src="https://images.pexels.com/photos/1643385/pexels-photo-1643385.jpeg" alt="Studio Bedroom">
                <img src="https://images.pexels.com/photos/1643386/pexels-photo-1643386.jpeg" alt="Studio Living">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider8')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider8')">›</button>
        <div class="slider-dots" id="dots8"></div>
    </div>
    <div class="card-tag for-rent">For Rent</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Cozy Studio Apartment" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
    </a>
    <div class="card-body">
        <h3>Cozy Studio Apartment</h3>
        <div class="card-location">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            Gulberg, Lahore
        </div>
        <div class="card-price">PKR 450K <span class="per-month">/ Month</span></div>
        <div class="card-meta">
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                2 Beds
            </span>
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12H2"/>
                    <path d="M6 9h12"/>
                </svg>
                2 Baths
            </span>
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                </svg>
                1200 Sqft
            </span>
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                </svg>
                Fully Furnished
            </span>
        </div>
        <a href="property-detail.php?id=8" class="view-detail-btn">View Details</a>
    </div>
</div>

                  <!-- Property Card 9 - For Sale -->
<div class="property-card">
    <div class="card-image-slider" id="slider9">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg" alt="Commercial Plaza">
                <img src="https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg" alt="Office Space">
                <img src="https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg" alt="Shop Area">
                <img src="https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg" alt="Building">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider9')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider9')">›</button>
        <div class="slider-dots" id="dots9"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Commercial Plaza" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
    </a>
    <div class="card-body">
        <h3>Commercial Plaza</h3>
        <div class="card-location">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            Blue Area, Islamabad
        </div>
        <div class="card-price">PKR 25.5M</div>
        <div class="card-meta">
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                10 Shops
            </span>
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 12H2"/>
                    <path d="M6 9h12"/>
                </svg>
                6 Offices
            </span>
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                </svg>
                5000 Sqft
            </span>
            <span class="meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="2" y1="12" x2="22" y2="12"/>
                </svg>
                Prime Location
            </span>
        </div>
        <a href="property-detail.php?id=9" class="view-detail-btn">View Details</a>
    </div>
</div>
<!-- Property Card 10 - For Sale - Rawalpindi -->
<div class="property-card">
    <div class="card-image-slider" id="slider10">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/323780/pexels-photo-323780.jpeg" alt="Bahria Town House">
                <img src="https://images.pexels.com/photos/323781/pexels-photo-323781.jpeg" alt="Living Room">
                <img src="https://images.pexels.com/photos/323782/pexels-photo-323782.jpeg" alt="Kitchen">
                <img src="https://images.pexels.com/photos/323783/pexels-photo-323783.jpeg" alt="Garden">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider10')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider10')">›</button>
        <div class="slider-dots" id="dots10"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <div class="featured-badge">Featured</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Bahria Town Phase 8 House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>Bahria Town Phase 8 House</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Bahria Town, Rawalpindi</div>
        <div class="card-price">PKR 7.2M</div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>4 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>4 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>10 Marla</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Modern</span>
        </div>
        <a href="property-detail.php?id=10" class="view-detail-btn">View Details</a>
    </div>
</div>

<!-- Property Card 11 - For Rent - Faisalabad -->
<div class="property-card">
    <div class="card-image-slider" id="slider11">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg" alt="Canal Road House">
                <img src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg" alt="Bedroom">
                <img src="https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg" alt="Kitchen">
                <img src="https://images.pexels.com/photos/1571459/pexels-photo-1571459.jpeg" alt="Living">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider11')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider11')">›</button>
        <div class="slider-dots" id="dots11"></div>
    </div>
    <div class="card-tag for-rent">For Rent</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Canal Road House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>Canal Road House</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Canal Road, Faisalabad</div>
        <div class="card-price">PKR 55K <span class="per-month">/ Month</span></div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>4 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>3 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>10 Marla</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Family</span>
        </div>
        <a href="property-detail.php?id=11" class="view-detail-btn">View Details</a>
    </div>
</div>

<!-- Property Card 12 - For Sale - Multan -->
<div class="property-card">
    <div class="card-image-slider" id="slider12">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg" alt="Multan House">
                <img src="https://images.pexels.com/photos/280232/pexels-photo-280232.jpeg" alt="Kitchen">
                <img src="https://images.pexels.com/photos/280233/pexels-photo-280233.jpeg" alt="Bedroom">
                <img src="https://images.pexels.com/photos/280234/pexels-photo-280234.jpeg" alt="Dining">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider12')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider12')">›</button>
        <div class="slider-dots" id="dots12"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Cantt Area House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>Cantt Area House</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Cantt, Multan</div>
        <div class="card-price">PKR 4.8M</div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>3 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>3 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>8 Marla</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Secure</span>
        </div>
        <a href="property-detail.php?id=12" class="view-detail-btn">View Details</a>
    </div>
</div>

<!-- Property Card 13 - For Sale - Peshawar -->
<div class="property-card">
    <div class="card-image-slider" id="slider13">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg" alt="Hayatabad House">
                <img src="https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg" alt="Kitchen">
                <img src="https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg" alt="Bedroom">
                <img src="https://images.pexels.com/photos/1571458/pexels-photo-1571458.jpeg" alt="Living">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider13')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider13')">›</button>
        <div class="slider-dots" id="dots13"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <div class="featured-badge">Featured</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Hayatabad Phase 5 House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>Hayatabad Phase 5 House</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Hayatabad, Peshawar</div>
        <div class="card-price">PKR 6.5M</div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>5 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>5 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>1 Kanal</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Gated</span>
        </div>
        <a href="property-detail.php?id=13" class="view-detail-btn">View Details</a>
    </div>
</div>

<!-- Property Card 14 - For Rent - Abbottabad -->
<div class="property-card">
    <div class="card-image-slider" id="slider14">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg" alt="Abbottabad House">
                <img src="https://images.pexels.com/photos/208740/pexels-photo-208740.jpeg" alt="Garden">
                <img src="https://images.pexels.com/photos/208738/pexels-photo-208738.jpeg" alt="Interior">
                <img src="https://images.pexels.com/photos/208739/pexels-photo-208739.jpeg" alt="View">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider14')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider14')">›</button>
        <div class="slider-dots" id="dots14"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Clifton Sea View Apartment" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>Clifton Sea View Apartment</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Clifton, Karachi</div>
        <div class="card-price">PKR 12M</div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>4 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>4 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>2500 Sqft</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Sea View</span>
        </div>
        <a href="property-detail.php?id=12" class="view-detail-btn">View Details</a>
    </div>
</div>

<!-- Property Card 15 - For Rent - Swat -->
<div class="property-card">
    <div class="card-image-slider" id="slider15">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg" alt="Fizagat Villa">
                <img src="https://images.pexels.com/photos/258158/pexels-photo-258158.jpeg" alt="Living">
                <img src="https://images.pexels.com/photos/258159/pexels-photo-258159.jpeg" alt="Bedroom">
                <img src="https://images.pexels.com/photos/258160/pexels-photo-258160.jpeg" alt="View">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider15')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider15')">›</button>
        <div class="slider-dots" id="dots15"></div>
    </div>
    <div class="card-tag for-rent">For Rent</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="Gulshan-e-Iqbal House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>Gulshan-e-Iqbal House</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Gulshan-e-Iqbal, Karachi</div>
        <div class="card-price">PKR 85K <span class="per-month">/ Month</span></div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>4 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>3 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>1800 Sqft</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Peaceful Area</span>
        </div>
        <a href="property-detail.php?id=13" class="view-detail-btn">View Details</a>
    </div>
</div>

<!-- Property Card 16 - For Sale - Gwadar -->
<div class="property-card">
    <div class="card-image-slider" id="slider16">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg" alt="Gwadar Commercial">
                <img src="https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg" alt="Office">
                <img src="https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg" alt="Building">
                <img src="https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg" alt="Exterior">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider16')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider16')">›</button>
        <div class="slider-dots" id="dots16"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <div class="featured-badge">Hot Deal</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="DHA Karachi Villa" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>DHA Karachi Villa</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>DHA Phase 8, Karachi</div>
        <div class="card-price">PKR 22M</div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>6 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>7 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>2 Kanal</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Private Pool</span>
        </div>
        <a href="property-detail.php?id=14" class="view-detail-btn">View Details</a>
    </div>
</div>

<!-- Property Card 17 - For Rent - Muzaffarabad -->
<div class="property-card">
    <div class="card-image-slider" id="slider17">
        <div class="slider-container">
            <div class="slider-track">
                <img src="https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg" alt="River View House">
                <img src="https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg" alt="Living Room">
                <img src="https://images.pexels.com/photos/1643384/pexels-photo-1643384.jpeg" alt="Kitchen">
                <img src="https://images.pexels.com/photos/1643385/pexels-photo-1643385.jpeg" alt="Bedroom">
            </div>
        </div>
        <button class="slider-btn slider-prev" onclick="prevSlide('slider17')">‹</button>
        <button class="slider-btn slider-next" onclick="nextSlide('slider17')">›</button>
        <div class="slider-dots" id="dots17"></div>
    </div>
    <div class="card-tag for-sale">For Sale</div>
    <a href="javascript:void(0)" class="wishlist-icon" data-title="F-7 Sector House" onclick="toggleWishlistHome(this)" title="Add to wishlist">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </a>
    <div class="card-body">
        <h3>F-7 Sector House</h3>
        <div class="card-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>F-7, Islamabad</div>
        <div class="card-price">PKR 15M</div>
        <div class="card-meta">
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>5 Beds</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/></svg>6 Baths</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>1 Kanal</span>
            <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>Beautiful Garden</span>
        </div>
        <a href="property-detail.php?id=15" class="view-detail-btn">View Details</a>
    </div>
</div>
                </div>
            </div>
            <div class="filter-widget">
    <h3>Find Your Property</h3>

    <div class="filter-group">
        <label>Location</label>
        <select>
            <option value="">All Cities</option>
                        <option value="Lahore">Lahore</option>
                        <option value="Karachi">Karachi</option>
                        <option value="Islamabad">Islamabad</option>
                        <option value="Rawalpindi">Rawalpindi</option>
                        <option value="Faisalabad">Faisalabad</option>
                        <option value="Multan">Multan</option>
                        <option value="Peshawar">Peshawar</option>
                        <option value="Quetta">Quetta</option>
                        <option value="Gujranwala">Gujranwala</option>
                        <option value="Sialkot">Sialkot</option>
                        <option value="Bahawalpur">Bahawalpur</option>
                        <option value="Sargodha">Sargodha</option>
                        <option value="Hyderabad">Hyderabad</option>
                        <option value="Sukkur">Sukkur</option>
                        <option value="Larkana">Larkana</option>
                        <option value="Abbottabad">Abbottabad</option>
                        <option value="Mardan">Mardan</option>
                        <option value="Swat">Swat</option>
                        <option value="Gwadar">Gwadar</option>
                        <option value="Muzaffarabad">Muzaffarabad</option>
                        <option value="Sahiwal">Sahiwal</option>
                        <option value="Rahim Yar Khan">Rahim Yar Khan</option>
                        <option value="Dera Ghazi Khan">Dera Ghazi Khan</option>
                        <option value="Nawabshah">Nawabshah</option>
                        <option value="Mirpur Khas">Mirpur Khas</option>
                        <option value="Kohat">Kohat</option>
                        <option value="Dera Ismail Khan">Dera Ismail Khan</option>
                        <option value="Turbat">Turbat</option>
                        <option value="Khuzdar">Khuzdar</option>
        </select>
    </div>

    <div class="filter-group">
        <label>Property Type</label>
        <select>
            <option value="">All Property Types</option>
            <option>House</option>
            <option>Apartment</option>
            <option>Villa</option>
            <option>Penthouse</option>
            <option>Studio Apartment</option>
            <option>Farm House</option>
            <option>Commercial Plaza</option>
            <option>Office</option>
            <option>Shop</option>
            <option>Residential Plot</option>
            <option>Commercial Plot</option>
        </select>
    </div>

    <div class="filter-group">
        <label>Price</label>
        <select>
            <option value="">Any Price</option>
            <option>PKR 1 Million</option>
            <option>PKR 2 Million</option>
            <option>PKR 5 Million</option>
            <option>PKR 10 Million</option>
            <option>PKR 20 Million</option>
            <option>PKR 50 Million</option>
            <option>PKR 100 Million+</option>
        </select>
    </div>

    <button class="apply-filter">
         Search Property
    </button>

    <button class="reset-filter">
        Reset
    </button>
</div>
</section>
<!-- ============================================ -->
<!-- BROWSE BY CATEGORY SECTION                  -->
<!-- ============================================ -->
<section class="categories-section">
    <div class="container">
        <div class="section-title-wrapper">
            <h2>Browse by Category</h2>
            <a href="listings.php" class="view-all">View All →</a>
        </div>
        
        <div class="categories-grid">
            <div class="category-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <h4>Houses</h4>
                <p>100+ Properties</p>
            </div>
            <div class="category-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="1.8">
                    <rect x="6" y="4" width="12" height="16" rx="1"/>
                    <line x1="10" y1="8" x2="14" y2="8"/>
                    <line x1="10" y1="12" x2="14" y2="12"/>
                    <line x1="10" y1="16" x2="14" y2="16"/>
                </svg>
                <h4>Apartments</h4>
                <p>80+ Properties</p>
            </div>
            <div class="category-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="1.8">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="9" y1="3" x2="9" y2="21"/>
                    <line x1="15" y1="3" x2="15" y2="21"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="3" y1="15" x2="21" y2="15"/>
                </svg>
                <h4>Plots</h4>
                <p>150+ Properties</p>
            </div>
            <div class="category-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="1.8">
                    <rect x="4" y="6" width="16" height="14" rx="1"/>
                    <path d="M12 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                </svg>
                <h4>Commercial</h4>
                <p>60+ Properties</p>
            </div>
            <div class="category-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#06B6D4" stroke-width="1.8">
                    <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                    <path d="M2 17l10 5 10-5"/>
                    <path d="M2 12l10 5 10-5"/>
                </svg>
                <h4>Farm Houses</h4>
                <p>40+ Properties</p>
            </div>
            <div class="category-card">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#EC4899" stroke-width="1.8">
                    <rect x="4" y="4" width="7" height="16" rx="1"/>
                    <rect x="13" y="4" width="7" height="10" rx="1"/>
                </svg>
                <h4>Portions</h4>
                <p>70+ Properties</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- ABOUT US SECTION (Replaces Cities)          -->
<!-- ============================================ -->
<style>
    /* About Section Styles – matches your theme */
    .about-section {
        padding: 60px 0 70px;
        background: #ffffff;
    }
    .about-header {
        text-align: center;
        margin-bottom: 45px;
    }
    .about-header h2 {
        font-family: 'Fraunces', serif;
        font-size: 32px;
        font-weight: 600;
        color: #111827;
        letter-spacing: -0.3px;
        margin-bottom: 10px;
    }
    .about-header p {
        font-size: 16px;
        color: #6B7280;
        max-width: 600px;
        margin: 0 auto;
    }
    .about-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 50px;
        align-items: center;
        margin-bottom: 50px;
    }
    .about-text h3 {
        font-size: 26px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 16px;
        font-family: 'Fraunces', serif;
    }
    .about-text p {
        color: #6B7280;
        line-height: 1.7;
        margin-bottom: 16px;
        font-size: 15px;
    }
    .about-image img {
        width: 100%;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transition: transform 0.3s ease;
    }
    .about-image img:hover {
        transform: scale(1.01);
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 50px;
    }
    .stat-box {
        background: #F8FAFC;
        padding: 28px 15px;
        border-radius: 16px;
        text-align: center;
        border: 1px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    .stat-box:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(14, 122, 78, 0.08);
        border-color: #0E7A4E;
    }
    .stat-number {
        display: block;
        font-size: 32px;
        font-weight: 800;
        color: #0E7A4E;
        font-family: 'Fraunces', serif;
    }
    .stat-label {
        font-size: 14px;
        color: #6B7280;
        margin-top: 6px;
        font-weight: 500;
    }
    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
    }
    .feature-card {
        background: #F9FAFB;
        padding: 30px 20px;
        border-radius: 16px;
        text-align: center;
        border: 1px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    .feature-card:hover {
        background: white;
        transform: translateY(-5px);
        box-shadow: 0 10px 28px rgba(0,0,0,0.06);
        border-color: #0E7A4E;
    }
    .feature-icon {
        width: 60px;
        height: 60px;
        background: #E7F5EC;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        transition: background 0.3s;
    }
    .feature-card:hover .feature-icon {
        background: #0E7A4E;
    }
    .feature-card:hover .feature-icon svg {
        stroke: white;
    }
    .feature-icon svg {
        width: 28px;
        height: 28px;
        stroke: #0E7A4E;
        stroke-width: 1.8;
        fill: none;
        transition: stroke 0.3s;
    }
    .feature-card h4 {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 8px;
    }
    .feature-card p {
        font-size: 14px;
        color: #6B7280;
        margin: 0;
        line-height: 1.5;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .about-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
        .about-image {
            order: -1;
        }
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .features-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 600px) {
        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .features-grid {
            grid-template-columns: 1fr;
        }
        .about-header h2 {
            font-size: 26px;
        }
        .about-text h3 {
            font-size: 22px;
        }
    }
</style>

<section class="about-section">
    <div class="container">
        <!-- Heading -->
        <div class="about-header">
            <h2>About EstateHub</h2>
            <p>Pakistan's most trusted real estate platform connecting buyers and sellers</p>
        </div>

        <!-- Text + Image -->
        <div class="about-grid">
            <div class="about-text">
                <h3>Your Trusted Real Estate Partner</h3>
                <p>EstateHub is a modern real estate platform that helps you find, buy, sell or rent properties with ease. Our mission is to provide a trusted, transparent and convenient experience for all.</p>
                <p>With thousands of verified listings across Pakistan, we connect property seekers with genuine sellers, ensuring a seamless real estate journey.</p>
            </div>
            <div class="about-image">
                <img src="https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg" alt="About EstateHub">
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <span class="stat-number">10K+</span>
                <span class="stat-label">Properties</span>
            </div>
            <div class="stat-box">
                <span class="stat-number">5K+</span>
                <span class="stat-label">Happy Clients</span>
            </div>
            <div class="stat-box">
                <span class="stat-number">500+</span>
                <span class="stat-label">Verified Sellers</span>
            </div>
            <div class="stat-box">
                <span class="stat-number">20+</span>
                <span class="stat-label">Cities</span>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                </div>
                <h4>Trusted Platform</h4>
                <p>Verified listings and genuine sellers</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15 15 0 0 0 0 20 15 15 0 0 0 0-20z"/></svg>
                </div>
                <h4>All Over Pakistan</h4>
                <p>Properties in 20+ major cities</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 1 10 10c0 5.5-4 10-10 10S2 17.5 2 12 6.5 2 12 2z"/><path d="M12 6v6l3 2"/></svg>
                </div>
                <h4>Easy Communication</h4>
                <p>Direct contact with property owners</p>
            </div>
        </div>
    </div>
</section>

<!-- ✅ ===== CORRECTED JAVASCRIPT ===== ✅ -->
<script>
// ============================================ //
// 1. GLOBALLY ACCESSIBLE DATA & FILTER        //
// ============================================ //

// All properties used for filtering (Buy / Rent / PG)
const allProperties = [
    // Buy Properties
    { title: 'Modern 10 Marla House', location: 'DHA Phase 6, Lahore', price: '5.2M', type: 'buy', beds: 5, baths: 6, area: '10 Marla', extra: 'Ready', img: 'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg' },
    { title: 'Designer Villa', location: 'F-10, Islamabad', price: '7.8M', type: 'buy', beds: 6, baths: 7, area: '1 Kanal', extra: 'Luxury', img: 'https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg' },
    { title: 'Double Kitchen House', location: 'Model Town, Lahore', price: '3.45M', type: 'buy', beds: 4, baths: 4, area: '8 Marla', extra: '2 Kitchens', img: 'https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg' },
    { title: 'Modern Farm House', location: 'Gulberg Greens, Islamabad', price: '9.5M', type: 'buy', beds: 5, baths: 6, area: '2 Kanal', extra: 'Pool+Garden', img: 'https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg' },
    { title: 'Beach Front Villa', location: 'DHA, Karachi', price: '15.8M', type: 'buy', beds: 7, baths: 8, area: '4 Kanal', extra: 'Beach View', img: 'https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg' },
    { title: 'Commercial Plaza', location: 'Blue Area, Islamabad', price: '25.5M', type: 'buy', beds: 0, baths: 0, area: '5000 Sqft', extra: 'Prime Location', img: 'https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg' },
    
    // Rent Properties
    { title: 'Luxury Apartment', location: 'Bahria Town, Karachi', price: '1.2M', type: 'rent', beds: 3, baths: 3, area: '2000 Sqft', extra: 'Furnished', img: 'https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg' },
    { title: 'Luxury Penthouse', location: 'Clifton, Karachi', price: '2.5M', type: 'rent', beds: 4, baths: 5, area: '3500 Sqft', extra: 'Sea View', img: 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg' },
    { title: 'Cozy Studio', location: 'Gulberg, Lahore', price: '450K', type: 'rent', beds: 2, baths: 2, area: '1200 Sqft', extra: 'Fully Furnished', img: 'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg' },
    
    // PG Properties
    { title: 'Shared PG Room', location: 'F-11, Islamabad', price: '25K', type: 'pg', beds: 1, baths: 1, area: '500 Sqft', extra: 'Meals Included', img: 'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg' },
    { title: 'Luxury PG', location: 'DHA, Lahore', price: '45K', type: 'pg', beds: 2, baths: 2, area: '800 Sqft', extra: 'AC & WiFi', img: 'https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg' },
    { title: 'Budget PG', location: 'Township, Lahore', price: '15K', type: 'pg', beds: 1, baths: 1, area: '400 Sqft', extra: 'Basic', img: 'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg' },
    { title: 'Girls PG', location: 'Johar Town, Lahore', price: '20K', type: 'pg', beds: 1, baths: 1, area: '600 Sqft', extra: 'Security', img: 'https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg' }
];

function filterProperties(type) {
    const grid = document.querySelector('.properties-grid');
    if (!grid) return;

    let filtered = allProperties.filter(p => p.type === type);

    grid.innerHTML = '';
    filtered.forEach(prop => {
        const card = document.createElement('div');
        card.className = 'property-card';
        card.innerHTML = `
            <div class="card-image-slider">
                <div class="slider-container">
                    <div class="slider-track">
                        <img src="${prop.img}" alt="${prop.title}">
                        <img src="${prop.img}" alt="${prop.title}">
                        <img src="${prop.img}" alt="${prop.title}">
                    </div>
                </div>
            </div>
            <div class="card-tag ${prop.type === 'buy' ? 'for-sale' : (prop.type === 'rent' ? 'for-rent' : 'for-pg')}">
                ${prop.type === 'buy' ? 'For Sale' : (prop.type === 'rent' ? 'For Rent' : 'PG Available')}
            </div>
            <a href="javascript:void(0)" class="wishlist-icon" data-title="${prop.title}" onclick="toggleWishlistHome(this)" title="Add to wishlist">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
            </a>
            <div class="card-body">
                <h3>${prop.title}</h3>
                <div class="card-location">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    ${prop.location}
                </div>
                <div class="card-price">
                    PKR ${prop.price}${prop.type !== 'buy' ? '<span class="per-month">/ Month</span>' : ''}
                </div>
                <div class="card-meta">
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="3" y1="9" x2="21" y2="9"/>
                            <line x1="9" y1="21" x2="9" y2="9"/>
                        </svg>
                        ${prop.beds} Beds
                    </span>
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12H2"/>
                            <path d="M6 9h12"/>
                        </svg>
                        ${prop.baths} Baths
                    </span>
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                        </svg>
                        ${prop.area}
                    </span>
                    <span class="meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                        </svg>
                        ${prop.extra}
                    </span>
                </div>
                <a href="property-detail.php?id=1" class="view-detail-btn">View Details</a>
            </div>
        `;
        grid.appendChild(card);
    });
}

// ============================================ //
// 2. FIXED goToHome FUNCTION                  //
// ============================================ //
function goToHome(purpose, event) {
    // Prevent default if any
    if (event) event.preventDefault();

    // Get the clicked button
    const btn = event ? event.currentTarget : null;
    if (btn) {
        // Remove active class from all tabs and set on clicked one
        document.querySelectorAll('#propertyTabs .tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
    }

    // Convert purpose to lowercase: 'Sale' → 'sale', 'Rent' → 'rent', 'PG' → 'pg'
    const filterType = purpose.toLowerCase();
    if (typeof filterProperties === 'function') {
        filterProperties(filterType);
    }

    // Smooth scroll to the properties grid after a tiny delay
    setTimeout(() => {
        const grid = document.querySelector('.properties-grid');
        if (grid) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 350);
}

// ============================================ //
// 3. SLIDER FUNCTIONS (unchanged)             //
// ============================================ //
let slideIndexes = {};

function initSlider(sliderId, imageCount) {
    slideIndexes[sliderId] = 0;
    updateDots(sliderId, imageCount);
}

function updateDots(sliderId, imageCount) {
    const sliderNum = sliderId.replace('slider', '');
    const dotsContainer = document.getElementById(`dots${sliderNum}`);
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
// 4. WISHLIST (unchanged)                     //
// ============================================ //
const wishlistedTitles = <?php echo json_encode($wishlisted_titles); ?>;

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
    const title = el.getAttribute('data-title');

    fetch('toggle-wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
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
            el.classList.remove('active');
            showWishlistToast('Removed from wishlist', false);
        } else {
            showWishlistToast(data.message || 'Something went wrong', true);
        }
    })
    .catch(() => showWishlistToast('Network error, please try again', true));
}

// ============================================ //
// 5. DOMContentLoaded - Only minimal setup    //
// ============================================ //
document.addEventListener('DOMContentLoaded', function() {
    // --- Set active tab based on URL parameter (if any) ---
    const urlParams = new URLSearchParams(window.location.search);
    const currentPurpose = urlParams.get('purpose');
    const tabs = document.querySelectorAll('#propertyTabs .tab');
    if (tabs.length > 0) {
        tabs.forEach(t => t.classList.remove('active'));
        if (currentPurpose === 'Sale') {
            document.querySelector('[data-type="buy"]')?.classList.add('active');
        } else if (currentPurpose === 'Rent') {
            document.querySelector('[data-type="rent"]')?.classList.add('active');
        } else if (currentPurpose === 'PG') {
            document.querySelector('[data-type="pg"]')?.classList.add('active');
        } else {
            document.querySelector('[data-type="buy"]')?.classList.add('active');
        }
    }

    // --- Initialize sliders for all property cards ---
    for (let i = 1; i <= 17; i++) {
        const sliderId = `slider${i}`;
        const slider = document.getElementById(sliderId);
        if (slider) {
            const track = slider.querySelector('.slider-track');
            if (track) {
                const images = track.querySelectorAll('img');
                initSlider(sliderId, images.length);
            }
        }
    }

    // --- Pre‑fill wishlist hearts ---
    document.querySelectorAll('.wishlist-icon[data-title]').forEach(el => {
        if (wishlistedTitles.includes(el.getAttribute('data-title'))) {
            el.classList.add('active');
        }
    });

    // --- Load default tab (Buy) if no purpose in URL ---
    if (!currentPurpose) {
        filterProperties('buy');
    } else {
        // If purpose exists, filter accordingly
        const typeMap = { 'Sale': 'buy', 'Rent': 'rent', 'PG': 'pg' };
        const type = typeMap[currentPurpose] || 'buy';
        filterProperties(type);
    }
});
</script>

<?php include 'includes/footer.php'; ?>