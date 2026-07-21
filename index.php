<?php
$page_title = 'Home';
require_once __DIR__ . '/includes/config.php';
include 'includes/header.php';

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

// ============================================
// SAME FUNCTION AS listings.php / wishlist.php
// (isi wajah se image bilkul same rahegi wishlist mein)
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

// ============================================
// REAL PROPERTIES FROM DATABASE (dummy/demo cards ki jagah)
// ============================================
$featured_query  = "SELECT * FROM properties WHERE status = 'Active' ORDER BY featured DESC, created_at DESC LIMIT 6";
$featured_result = mysqli_query($conn, $featured_query);
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

.hero-text {
    margin-top: -40px;
}

.hero-text h1,
.hero-text p {
    opacity: 0;
    transform: translateX(-60px);
    animation: slideIn 0.9s ease forwards;
}

.hero-text h1:nth-child(2) {
    animation-delay: 0.2s;
}

.hero-text p {
    animation-delay: 0.4s;
}

@keyframes slideIn {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

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

.tabs .tab:hover{
    background:rgba(14,122,78,.25);
    border-color:#16a34a;
    color:#fff;
    transform:translateY(-2px);
    box-shadow:0 8px 18px rgba(22,163,74,.25);
}

.tabs .tab.active{
    background:#0E7A4E;
    border-color:#0E7A4E;
    color:#fff;
    box-shadow:0 10px 22px rgba(14,122,78,.35);
}

.tabs .tab:active{
    transform:scale(.97);
}

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
}

/*=========================================
        FEATURE STRIP - PREMIUM
=========================================*/

.feature-strip{
    max-width:1280px;
    margin:25px auto;
    padding:20px 25px;
    display:flex;
justify-content: center;
 gap: 95px
    
    
}

.feature-item{
    display:flex;
    align-items:center;
    gap:12px;      /* pehle 12px ya 15px tha */
}

.feature-item:not(:last-child){
    padding-right:0px;
}

.feature-item:hover{
    transform:translateY(-2px);
}

.feature-icon{
    width:40px;
    height:40px;
    border-radius:50%;
    background:#EAF7F0;
    display:flex;
    justify-content:center;
    align-items:center;
    flex-shrink:0;
    transition:.3s;
    gap: 5px;
}

.feature-icon i{
    font-size:20px;
    color:#0E7A4E;
    transition:.3s;
}


.feature-text{
    display:flex;
    flex-direction:column;
    gap:3px;   /* Heading aur paragraph ka gap */
}

.feature-text h4{
    margin:0;
    font-size:15px;
    font-weight:700;
    line-height:1.1;
    color:#111827;
}

.feature-text p{
    margin:0;
    font-size:13px;
    color:#6B7280;
    line-height:1.1;
}

@media(max-width:992px){

.feature-strip{
    flex-wrap:wrap;
    gap:20px;
}

.feature-item{
    flex:0 0 calc(50% - 10px);
}

}

@media(max-width:576px){

.feature-item{
    flex:100%;
}

}

/* ===== FIND YOUR PROPERTY - PREMIUM WIDGET ===== */
.filter-widget {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-radius: 24px;
    padding: 28px 24px 24px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
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

.map-placeholder {
    position: relative;
    width: 100%;
    height: 180px;
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 22px;
    background: linear-gradient(145deg, #d9e6df, #b3d0c0);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    cursor: pointer;
    transition: transform 0.3s ease;
}
.map-placeholder:hover {
    transform: scale(1.01);
}
.map-overlay {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 30% 40%, rgba(14, 122, 78, 0.12), rgba(0, 0, 0, 0.05));
    display: flex;
    align-items: center;
    justify-content: center;
}
.map-pin {
    position: relative;
    animation: pinFloat 2.5s ease-in-out infinite;
}
@keyframes pinFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
.pin-pulse {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 40px;
    height: 40px;
    background: rgba(14, 122, 78, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(1);
    animation: pulseRing 2s ease-out infinite;
}
@keyframes pulseRing {
    0% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
    100% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; }
}
.map-zoom {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.zoom-btn {
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(4px);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    transition: 0.2s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}
.zoom-btn:hover {
    background: #0E7A4E;
    color: white;
}
.map-cta {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(8px);
    color: white;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 18px;
    border-radius: 30px;
    letter-spacing: 0.3px;
    transition: 0.3s;
    border: 1px solid rgba(255, 255, 255, 0.15);
}
.map-cta:hover {
    background: rgba(14, 122, 78, 0.85);
    transform: translateX(-50%) scale(1.03);
}
.map-cta svg {
    stroke: white;
}
.map-attribution {
    position: absolute;
    bottom: 8px;
    right: 14px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 10px;
    font-weight: 500;
    background: rgba(0, 0, 0, 0.3);
    padding: 2px 10px;
    border-radius: 12px;
    backdrop-filter: blur(4px);
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
    background: rgba(255, 255, 255, 0.3);
    transform: scale(0);
    animation: rippleAnim 0.6s linear;
    pointer-events: none;
}
@keyframes rippleAnim {
    to {
        transform: scale(4);
        opacity: 0;
    }
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
        padding: 20px 16px 18px;
    }
    .map-placeholder {
        height: 140px;
    }
    .map-cta {
        font-size: 11px;
        padding: 6px 14px;
    }
}
@media (max-width: 900px) {
    .feature-strip {
        flex-direction: column;
        gap: 20px;
        padding: 30px;
    }
    .feature-item {
        width: 100%;
        justify-content: flex-start;
    }
}

/* ============================================ */
/* PREMIUM PROPERTY CARD STYLES                */
/* ============================================ */

.premium-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
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
.premium-card .card-body {
    padding: 20px 20px 18px;
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
.premium-card .card-price {
    font-size: 24px;
    font-weight: 800;
    color: #0E7A4E;
    margin: 8px 0 12px;
}
.premium-card .per-month {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
}
.contact-price {
    font-size: 16px;
    font-weight: 700;
    color: #ef4444;
}
.premium-card .card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 14px;
    margin-bottom: 14px;
    border-top: 1px solid #eef2f7;
    padding-top: 12px;
}
.premium-card .meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b; /* black/dark gray */
}
.premium-card .meta-item i {
    font-size: 14px;
    color: #1e293b; /* black/dark gray */
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
    margin-top: 4px;
}
.view-detail-btn:hover {
    background: #0a5c3a;
    transform: scale(1.01);
    box-shadow: 0 8px 25px rgba(14,122,78,0.3);
}

/* ============================================ */
/* VIEW ALL BUTTON - PREMIUM STYLE             */
/* ============================================ */
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

.section-title-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}
.section-title-wrapper h2 {
    font-family: 'Fraunces', serif;
    font-size: 32px;
    font-weight: 600;
    color: #111827;
    letter-spacing: -0.3px;
}

/* Reduce gap between property cards in grid */
.properties-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
@media (max-width: 992px) {
    .properties-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 576px) {
    .properties-grid {
        grid-template-columns: 1fr;
    }
}

/* ============================================ */
/* BROWSE BY CATEGORY - IMPROVED               */
/* ============================================ */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 10px;
}
.category-card {
    background: #ffffff;
    border: 1px solid #edf2f7;
    border-radius: 16px;
    padding: 24px 20px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}
.category-card:hover {
    background: #f8fafc;
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.06);
    border-color: #0E7A4E;
}
.category-card svg {
    width: 36px;
    height: 36px;
    stroke: #0E7A4E;
    stroke-width: 1.6;
    fill: none;
    transition: stroke 0.3s;
}
.category-card:hover svg {
    stroke: #0E7A4E;
}
.category-card h4 {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-top: 12px;
    margin-bottom: 4px;
}
.category-card p {
    font-size: 14px;
    color: #6B7280;
    margin: 0;
}

@media (max-width: 768px) {
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 480px) {
    .categories-grid {
        grid-template-columns: 1fr;
    }
}

/* ============================================ */
/* ABOUT US SECTION (unchanged)                 */
/* ============================================ */
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

<div class="tabs" id="propertyTabs">
    <button class="tab active" data-type="buy" onclick="goToHome('Sale', event)">Buy</button>
    <button class="tab" data-type="rent" onclick="goToHome('Rent', event)">Rent</button>
    <button class="tab" data-type="pg" onclick="goToHome('PG', event)">PG</button>
</div>

<!-- SEARCH CARD -->
<div class="search-card">
    <form class="search-form" action="search-results.php" method="GET">
        <div class="search-row">

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

            <div class="search-field">
                <label>Property Type</label>
                <div class="input">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#a6a7a4" stroke-width="1.8">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-8H9v8H5a2 2 0 0 1-2-2z"/>
                    </svg>
<select name="type">
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

</div>
</section>

<!-- ============================================ -->
<!-- FEATURE STRIP                               -->
<!-- ============================================ -->
<section class="feature-strip">
    <div class="feature-item">
        <div class="feature-icon">
            <i class="fas fa-home"></i>
        </div>
        <div class="feature-text">
            <h4>40+ Properties</h4>
            <p>Available for you</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="feature-text">
            <h4>All Over Pakistan</h4>
            <p>100+ Cities</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <i class="fas fa-user-check"></i>
        </div>
        <div class="feature-text">
            <h4>Trusted by Thousands</h4>
            <p>Happy Customers</p>
        </div>
    </div>
    <div class="feature-item">
        <div class="feature-icon">
            <i class="fas fa-shield-alt"></i>
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
                    <a href="listings.php" class="view-all-btn">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="properties-grid" id="featuredGrid">
                    <?php if ($featured_result && mysqli_num_rows($featured_result) > 0): ?>
                        <?php while ($prop = mysqli_fetch_assoc($featured_result)):
                            $images = getPropertyImages($prop['property_type'], $prop['id']);
                            $is_wishlisted = in_array((int) $prop['id'], $wishlisted_ids);
                            $price_formatted = number_format($prop['price'] / 1000000, 1);
                            $price_display = $prop['price'] > 0 ? 'PKR ' . $price_formatted . 'M' : '<span class="contact-price">Contact for Price</span>';
                            $price_suffix = ($prop['purpose'] == 'Rent' && $prop['price'] > 0) ? ' <span class="per-month">/ Month</span>' : '';
                        ?>
                        <div class="property-card premium-card" data-property-id="<?php echo $prop['id']; ?>">
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

                            <a href="javascript:void(0)" class="wishlist-icon <?php echo $is_wishlisted ? 'active' : ''; ?>"
                               data-id="<?php echo (int) $prop['id']; ?>"
                               onclick="toggleWishlistHome(this)" title="Add to wishlist">
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
                    <?php else: ?>
                        <p>Abhi koi property available nahi hai.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ===== FIND YOUR PROPERTY WIDGET - MODERN PREMIUM ===== -->

            <div class="filter-widget">
    <h3>Find Your Property</h3>

    <div class="map-placeholder" id="propertyMap" onclick="window.location.href='search-results.php';">
        <div class="map-overlay">
            <div class="map-pin">
                <svg viewBox="0 0 24 24" fill="#0E7A4E" stroke="white" stroke-width="2" width="28" height="28">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3" fill="white"/>
                </svg>
            </div>
            <div class="map-zoom">
                <button class="zoom-btn" title="Zoom In">+</button>
                <button class="zoom-btn" title="Zoom Out">−</button>
            </div>
        </div>
        <div class="map-attribution">📍 Click to explore properties</div>
    </div>

    <form action="listings.php" method="GET" class="filter-form" id="filterForm">
        <div class="filter-group">
            <label>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
                Location
            </label>
            <select name="location" id="filterLocation">
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
            <label>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Property Type
            </label>
            <select name="type" id="filterType">
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
            <label>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="6" x2="12" y2="18"/>
                    <line x1="6" y1="12" x2="18" y2="12"/>
                </svg>
                Price
            </label>
            <select name="price" id="filterPrice">
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
</section>
<!-- ============================================ -->
<!-- BROWSE BY CATEGORY SECTION (improved)       -->
<!-- ============================================ -->
<section class="categories-section">
    <div class="container">
        <div class="section-title-wrapper">
            <h2>Browse by Category</h2>
            <a href="listings.php" class="view-all-btn">
                View All <i class="fas fa-arrow-right"></i>
            </a>
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
<!-- ABOUT US SECTION (unchanged)                 -->
<!-- ============================================ -->
<section class="about-section">
    <div class="container">
        <div class="about-header">
            <h2>About EstateHub</h2>
            <p>Pakistan's most trusted real estate platform connecting buyers and sellers</p>
        </div>

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

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
// ============================================ //
// 1. goToHome FUNCTION - ab sirf listings.php   //
//    par redirect karta hai (real DB data)      //
// ============================================ //
function goToHome(purpose, event) {
    if (event) event.preventDefault();
    window.location.href = 'listings.php?purpose=' + encodeURIComponent(purpose);
}

// ============================================ //
// 2. SLIDER FUNCTIONS                         //
// ============================================ //
let slideIndexes = {};

function initSlider(sliderId, imageCount) {
    slideIndexes[sliderId] = 0;
    updateDots(sliderId, imageCount);
}

function updateDots(sliderId, imageCount) {
    const dotsContainer = document.getElementById(sliderId.replace('slider_', 'dots_').replace('slider', 'dots'));
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
// 3. WISHLIST                                 //
// ============================================ //
const wishlistedIds = <?php echo json_encode($wishlisted_ids); ?>;

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
// 4. DOMContentLoaded – SETUP                 //
// ============================================ //
document.addEventListener('DOMContentLoaded', function() {
    // --- Active tab from URL (visual highlight only) ---
    const urlParams = new URLSearchParams(window.location.search);
    const currentPurpose = urlParams.get('purpose');
    const tabs = document.querySelectorAll('#propertyTabs .tab');
    if (tabs.length > 0 && currentPurpose) {
        tabs.forEach(t => t.classList.remove('active'));
        if (currentPurpose === 'Sale') {
            document.querySelector('[data-type="buy"]')?.classList.add('active');
        } else if (currentPurpose === 'Rent') {
            document.querySelector('[data-type="rent"]')?.classList.add('active');
        } else if (currentPurpose === 'PG') {
            document.querySelector('[data-type="pg"]')?.classList.add('active');
        }
    }

    // --- Initialize sliders for real (DB-driven) featured cards ---
    document.querySelectorAll('#featuredGrid .card-image-slider').forEach(function(slider) {
        const track = slider.querySelector('.slider-track');
        if (track) {
            const images = track.querySelectorAll('img');
            initSlider(slider.id, images.length);
        }
    });

    // --- Pre-fill wishlist hearts (safety net; PHP already sets 'active' class) ---
    document.querySelectorAll('.wishlist-icon[data-id]').forEach(el => {
        if (wishlistedIds.includes(parseInt(el.getAttribute('data-id'), 10))) {
            el.classList.add('active');
        }
    });

    // --- Ripple effect + reset button (Find Your Property widget) ---
    const searchBtn = document.getElementById('searchBtn');
    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
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
</script>

<?php include 'includes/footer.php'; ?>