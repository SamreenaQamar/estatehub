<?php
$page_title = 'Property Details';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/header.php';

// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch property from database
$property = null;
$agent = null;
$images = [];

// wishlist state (needed so related-property cards can show the heart icon,
// same as index.php / listings.php)
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
// SAME FUNCTION AS index.php / listings.php / wishlist.php
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

function formatCardPrice($price, $purpose)
{
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

if ($property_id > 0) {
    $query = "
        SELECT p.*, u.id as agent_id, u.full_name as agent_name, u.email as agent_email, u.phone as agent_phone, u.profile_pic as agent_image
        FROM properties p
        LEFT JOIN users u ON p.user_id = u.id
        WHERE p.id = $property_id AND p.status = 'Active'
    ";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $property = mysqli_fetch_assoc($result);

        // Same image set as shown on home/listings/wishlist for this property
        $images = getPropertyImages($property['property_type'], $property['id']);

        // --- Agent image ---
        $agent_image = 'https://ui-avatars.com/api/?name=' . urlencode($property['agent_name'] ?: 'Agent') . '&background=0E7A4E&color=fff&size=100';
        if (!empty($property['agent_image'])) {
            $pic_file = __DIR__ . "/uploads/profiles/" . $property['agent_image'];
            if (file_exists($pic_file)) {
                $agent_image = "uploads/profiles/" . $property['agent_image'];
            }
        }
        $property['agent_image_url'] = $agent_image;

        // --- Agent WhatsApp number, normalized to international format ---
        $wa_number = '';
        if (!empty($property['agent_phone'])) {
            $digits = preg_replace('/[^0-9]/', '', $property['agent_phone']);
            if (substr($digits, 0, 1) === '0') {
                $digits = substr($digits, 1);
            }
            if (substr($digits, 0, 2) !== '92') {
                $digits = '92' . $digits;
            }
            $wa_number = $digits;
        }
        $property['agent_whatsapp'] = $wa_number;
    }
}

// If no property found, redirect to properties page
if (!$property) {
    header("Location: properties.php");
    exit();
}

// Get related properties (same city, exclude current)
$related = [];
$city = mysqli_real_escape_string($conn, $property['city']);
$rel_query = "
    SELECT id, title, price, location, city, property_type, purpose, bedrooms, bathrooms, area_size, featured
    FROM properties 
    WHERE city = '$city' AND id != $property_id AND status = 'Active' 
    ORDER BY RAND() LIMIT 4
"; // now fetch 4 for the grid
$rel_result = mysqli_query($conn, $rel_query);
if ($rel_result) {
    while ($row = mysqli_fetch_assoc($rel_result)) {
        $row['images'] = getPropertyImages($row['property_type'], $row['id']);
        $related[] = $row;
    }
}

// Handle message submission
$message_sent = false;
$message_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $msg_text = mysqli_real_escape_string($conn, trim($_POST['message']));

    if (empty($name) || empty($email) || empty($msg_text)) {
        $message_error = 'Please fill in all required fields.';
    } else {
        // Save inquiry
        $subject = "Inquiry about " . $property['title'];
        mysqli_query($conn, "INSERT INTO inquiries (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$msg_text')");

        // Determine sender
        $sender_id = 0;
        if (isset($_SESSION['user_id'])) {
            $sender_id = (int)$_SESSION['user_id'];
        } else {
            $user_check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email' LIMIT 1");
            if ($user_check && mysqli_num_rows($user_check) > 0) {
                $sender_id = (int)mysqli_fetch_assoc($user_check)['id'];
            }
        }

        $receiver_id = (int)$property['agent_id'];
        $real_property_id = (int)$property['id'];

        if ($sender_id > 0 && $receiver_id > 0 && $receiver_id != $sender_id) {
            $full_message = $msg_text;
            if (!empty($phone)) {
                $full_message .= "\n\nContact phone: " . $phone;
            }
            $full_message_esc = mysqli_real_escape_string($conn, $full_message);

            $insert = mysqli_query($conn, "INSERT INTO messages (sender_id, receiver_id, property_id, message, created_at) VALUES ($sender_id, $receiver_id, $real_property_id, '$full_message_esc', NOW())");
            if ($insert) {
                $message_sent = true;
            } else {
                $message_error = "Something went wrong. Please try again.";
            }
        } else if ($sender_id == 0) {
            $message_error = "Your inquiry was saved. Login with the same email to see replies.";
        } else {
            $message_error = "Unable to send message to this agent.";
        }
    }
}

// Helper function for safe output
function safe($val, $default = '') {
    return !empty($val) ? htmlspecialchars($val) : $default;
}

// formatPrice() is already defined in config.php - do not redeclare
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo safe($property['title']); ?> - EstateHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== PROPERTY DETAIL STYLES ===== */
        .property-detail-section {
            padding: 40px 0 60px;
            background: #f4f6f5;
        }
        .property-detail-section .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        @media (max-width: 992px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== BREADCRUMB ===== */
        .detail-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .detail-breadcrumb a {
            color: #6B7280;
            text-decoration: none;
            transition: 0.2s;
        }
        .detail-breadcrumb a:hover {
            color: #0E7A4E;
        }
        .detail-breadcrumb i {
            font-size: 10px;
            color: #cbd5e1;
        }
        .detail-breadcrumb .current {
            color: #0b1a2e;
            font-weight: 600;
        }

        /* ===== GALLERY ===== */
        .gallery-wrapper {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            position: relative;
        }
        .main-image {
            height: 450px;
            background: #e9ecef;
            position: relative;
            overflow: hidden;
        }
        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .detail-tag {
            position: absolute;
            top: 18px;
            left: 18px;
            padding: 6px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.3px;
            z-index: 3;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .detail-tag.for-sale { background: #0E7A4E; }
        .detail-tag.for-rent { background: #10B981; }
        .detail-featured-badge {
            position: absolute;
            top: 18px;
            left: 150px;
            background: #F59E0B;
            color: #fff;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            z-index: 3;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .gallery-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #0b1a2e;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
            z-index: 5;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .gallery-arrow:hover {
            background: #fff;
            transform: translateY(-50%) scale(1.08);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        .gallery-arrow.prev { left: 12px; }
        .gallery-arrow.next { right: 12px; }

        .gallery-thumbs {
            display: flex;
            gap: 10px;
            padding: 15px;
            overflow-x: auto;
            background: #fff;
        }
        .gallery-thumbs img {
            width: 90px;
            height: 65px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: 0.3s;
            flex-shrink: 0;
        }
        .gallery-thumbs img:hover,
        .gallery-thumbs img.active {
            border-color: #0E7A4E;
        }

        /* ===== INFO CARD ===== */
        .info-card {
            background: #fff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .info-card h1 {
            font-size: 28px;
            font-weight: 800;
            color: #0b1a2e;
            margin-bottom: 10px;
        }
        .info-card .location {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6B7280;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .info-card .location i {
            color: #0E7A4E;
        }
        .info-card .price {
            font-size: 28px;
            font-weight: 700;
            color: #0E7A4E;
            margin-bottom: 20px;
        }
        .info-card .price .per-month {
            font-size: 16px;
            font-weight: 400;
            color: #6B7280;
        }
        .info-card .price .contact-price {
            font-size: 22px;
            font-weight: 700;
            color: #ef4444;
        }
        .property-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px 30px;
            padding: 18px 0;
            border-top: 1px solid #E5E7EB;
            border-bottom: 1px solid #E5E7EB;
            margin-bottom: 20px;
        }
        .property-meta .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            font-weight: 500;
        }
        .property-meta .meta-item i {
            color: #1a1a1a;
            font-size: 18px;
            width: 22px;
            text-align: center;
        }
        .description {
            color: #4B5563;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .description h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 10px;
        }
        .description h3 i {
            color: #0E7A4E;
        }
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
        }
        .features-list .feature-tag {
            background: #E7F5EC;
            color: #0E7A4E;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
        }
        .features-list .feature-tag i {
            color: #0E7A4E;
            margin-right: 6px;
        }

        /* ===== AGENT CARD ===== */
        .agent-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .agent-card .agent-header {
            background: linear-gradient(135deg, #0b1a2e 0%, #0E7A4E 100%);
            padding: 30px 25px 45px;
            position: relative;
        }
        .agent-card .avatar {
            width: 92px;
            height: 92px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto;
            border: 4px solid #fff;
            box-shadow: 0 6px 18px rgba(0,0,0,0.2);
            display: block;
            position: relative;
            top: 45px;
        }
        .agent-card .agent-body {
            padding: 55px 25px 25px;
        }
        .agent-card .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #E7F5EC;
            color: #0E7A4E;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 30px;
            margin-bottom: 10px;
        }
        .agent-card .name {
            font-size: 18px;
            font-weight: 700;
            color: #0b1a2e;
        }
        .agent-card .title {
            color: #6B7280;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .agent-card .contact-line {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;      /* reduced gap */
            font-size: 13px;
            color: #4B5563;
            margin-bottom: 4px; /* reduced margin */
            word-break: break-all;
            text-align: left;
            background: #F9FAFB;
            padding: 6px 12px;  /* reduced padding */
            border-radius: 10px;
        }
        .agent-card .contact-line i {
            color: #1a1a1a;   /* black/dark */
            width: 16px;
            flex-shrink: 0;
        }
        .agent-card .contact-lines {
            margin-bottom: 12px; /* reduced */
        }
        .agent-contact-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .agent-contact-buttons a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        .agent-contact-buttons .btn-whatsapp {
            background: #25D366;
            color: #fff;
        }
        .agent-contact-buttons .btn-whatsapp:hover {
            background: #1da851;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37,211,102,0.3);
        }
        .agent-contact-buttons .btn-call {
            background: #0E7A4E;
            color: #fff;
        }
        .agent-contact-buttons .btn-call:hover {
            background: #0a5c3a;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14,122,78,0.3);
        }
        .agent-contact-buttons .btn-email {
            background: #f1f5f9;
            color: #0b1a2e;
        }
        .agent-contact-buttons .btn-email:hover {
            background: #e2e8f0;
        }

        /* ===== CONTACT FORM ===== */
        .contact-form-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .contact-form-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 20px;
        }
        .contact-form-card h3 i {
            color: #1a1a1a; /* black/dark */
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4B5563;
            margin-bottom: 5px;
        }
        .form-group label i {
            color: #1a1a1a; /* black/dark */
            margin-right: 6px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #E5E7EB;
            border-radius: 12px;
            font-size: 14px;
            transition: 0.3s;
            background: #fafafa;
            font-family: 'Inter', sans-serif;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0E7A4E;
            box-shadow: 0 0 0 4px rgba(14,122,78,0.1);
            background: #fff;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .send-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
        }
        .send-btn:hover {
            background: linear-gradient(135deg, #0a5c3a, #0E7A4E);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14,122,78,0.3);
        }
        .send-btn i {
            margin-right: 8px;
        }
        .success-msg {
            background: #D1FAE5;
            color: #059669;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .error-msg {
            background: #FEE2E2;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* ===== MAP ===== */
        .map-card {
            background: #fff;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .map-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 15px;
        }
        .map-card h3 i {
            color: #0E7A4E;
        }
        .map-card iframe {
            border-radius: 12px;
            width: 100%;
            height: 200px;
            border: 0;
        }
        .map-card .address {
            margin-top: 10px;
            font-size: 13px;
            color: #6B7280;
        }
        .map-card .address i {
            color: #0E7A4E;
        }

        /* ===== RELATED PROPERTIES — 4 COLUMNS, HOME PAGE STYLE ===== */
        .related-title {
            font-size: 24px;
            font-weight: 700;
            color: #0b1a2e;
            margin: 40px 0 20px;
        }
        .related-title i {
            color: #1a1a1a;
            margin-right: 10px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        @media (max-width: 992px) {
            .related-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 768px) {
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 480px) {
            .related-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ===== PREMIUM PROPERTY CARD (copied from home page) ===== */
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
            height: 180px; /* slightly smaller for 4-col */
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
            padding: 16px 18px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .premium-card .card-body h4 {
            font-size: 15px;
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 4px;
        }
        .card-location {
            font-size: 12px;
            color: #6B7280;
            margin-bottom: 4px;
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
            font-size: 20px;
            font-weight: 800;
            color: #0E7A4E;
            margin: 6px 0 10px;
        }
        .premium-card .per-month {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
        }
        .contact-price {
            font-size: 15px;
            font-weight: 700;
            color: #ef4444;
        }
        .premium-card .card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 12px;
            margin-bottom: 12px;
            border-top: 1px solid #eef2f7;
            padding-top: 10px;
        }
        .premium-card .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
        }
        .premium-card .meta-item i {
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
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: 0.3s;
            border: none;
            margin-top: auto; /* push to bottom */
        }
        .view-detail-btn:hover {
            background: #0a5c3a;
            transform: scale(1.01);
            box-shadow: 0 8px 25px rgba(14,122,78,0.3);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-image {
                height: 300px;
            }
            .info-card h1 {
                font-size: 22px;
            }
            .info-card .price {
                font-size: 22px;
            }
            .property-meta {
                gap: 12px 20px;
            }
            .detail-grid {
                gap: 20px;
            }
            .gallery-thumbs img {
                width: 70px;
                height: 50px;
            }
            .gallery-arrow {
                width: 36px;
                height: 36px;
                font-size: 14px;
            }
            .gallery-arrow.prev { left: 8px; }
            .gallery-arrow.next { right: 8px; }
        }
    </style>
</head>
<body>

<section class="property-detail-section">
    <div class="container">

        <div class="detail-breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Home</a> <i class="fas fa-chevron-right"></i>
            <a href="listings.php">Listings</a> <i class="fas fa-chevron-right"></i>
            <span class="current"><?php echo safe($property['title']); ?></span>
        </div>

        <div class="detail-grid">
            <!-- LEFT COLUMN -->
            <div>

                <!-- Gallery -->
                <div class="gallery-wrapper">
                    <div class="main-image" id="mainImage">
                        <span class="detail-tag <?php echo $property['purpose'] == 'Rent' ? 'for-rent' : 'for-sale'; ?>">
                            <?php echo $property['purpose'] == 'Rent' ? 'For Rent' : 'For Sale'; ?>
                        </span>
                        <?php if (!empty($property['featured'])): ?>
                        <span class="detail-featured-badge"><i class="fas fa-star"></i> Featured</span>
                        <?php endif; ?>
                        <img id="mainImg" src="<?php echo safe($images[0]); ?>" alt="<?php echo safe($property['title']); ?>" onerror="this.src='assets/images/house/1.jpg';">

                        <button class="gallery-arrow prev" id="prevBtn" aria-label="Previous image">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="gallery-arrow next" id="nextBtn" aria-label="Next image">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs" id="galleryThumbs">
                        <?php foreach ($images as $index => $img): ?>
                        <img src="<?php echo safe($img); ?>" alt="Thumb" class="<?php echo $index == 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>" onclick="changeImage(<?php echo $index; ?>)" onerror="this.src='assets/images/house/1.jpg';">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Property Info -->
                <div class="info-card">
                    <h1><?php echo safe($property['title']); ?></h1>
                    <div class="location">
                        <i class="fas fa-map-pin"></i>
                        <?php echo safe($property['location'] . ', ' . $property['city']); ?>
                    </div>
                    <div class="price">
                        <?php echo formatCardPrice($property['price'], $property['purpose']); ?>
                    </div>

                    <div class="property-meta">
                        <?php if (!empty($property['bedrooms']) && $property['bedrooms'] > 0): ?>
                        <div class="meta-item">
                            <i class="fas fa-bed"></i>
                            <span><?php echo $property['bedrooms']; ?> Beds</span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="fas fa-bath"></i>
                            <span><?php echo $property['bathrooms']; ?> Baths</span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-vector-square"></i>
                            <span><?php echo safe($property['area'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span><?php echo $property['purpose'] == 'Sale' ? 'For Sale' : 'For Rent'; ?></span>
                        </div>
                        <?php if (!empty($property['property_type'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-home"></i>
                            <span><?php echo safe($property['property_type']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="description">
                        <h3><i class="fas fa-align-left"></i> Description</h3>
                        <p><?php echo nl2br(safe($property['description'])); ?></p>
                    </div>

                    <?php
                    $features = [];
                    if (!empty($property['features'])) {
                        if (is_string($property['features'])) {
                            $features = array_map('trim', explode(',', $property['features']));
                        }
                    }
                    if (empty($features)) {
                        $features = ['Central AC', 'Modern Kitchen', 'Security System', 'Parking'];
                    }
                    ?>
                    <div>
                        <h3 style="font-size:18px; font-weight:700; color:#0b1a2e; margin-bottom:15px;">
                            <i class="fas fa-check-circle" style="color:#0E7A4E; margin-right:8px;"></i>Key Features
                        </h3>
                        <div class="features-list">
                            <?php foreach ($features as $feature): ?>
                            <span class="feature-tag"><i class="fas fa-check"></i><?php echo safe($feature); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div>

                <!-- Agent Card -->
                <div class="agent-card">
                    <div class="agent-header">
                        <img src="<?php echo safe($property['agent_image_url']); ?>" alt="Agent" class="avatar" onerror="this.src='https://ui-avatars.com/api/?name=Agent&background=0E7A4E&color=fff&size=100';">
                    </div>
                    <div class="agent-body">
                        <div class="verified-badge"><i class="fas fa-circle-check"></i> Verified Agent</div>
                        <div class="name"><?php echo safe($property['agent_name'], 'Agent'); ?></div>
                        <div class="title"><i class="fas fa-user-tie"></i> Property Agent</div>

                        <div class="contact-lines">
                            <?php if (!empty($property['agent_email'])): ?>
                            <div class="contact-line">
                                <i class="fas fa-envelope"></i> <?php echo safe($property['agent_email']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($property['agent_phone'])): ?>
                            <div class="contact-line">
                                <i class="fas fa-phone"></i> <?php echo safe($property['agent_phone']); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                    <div class="agent-contact-buttons">
                        <?php if (!empty($property['agent_whatsapp'])): ?>
                        <a href="https://wa.me/<?php echo safe($property['agent_whatsapp']); ?>" target="_blank" class="btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($property['agent_phone'])): ?>
                        <a href="tel:<?php echo safe($property['agent_phone']); ?>" class="btn-call">
                            <i class="fas fa-phone"></i> Call Now
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($property['agent_email'])): ?>
                        <a href="mailto:<?php echo safe($property['agent_email']); ?>" class="btn-email">
                            <i class="fas fa-envelope"></i> Email Agent
                        </a>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form-card">
                    <h3><i class="fas fa-pen"></i> Request a Visit</h3>

                    <?php if ($message_sent): ?>
                        <div class="success-msg"><i class="fas fa-check-circle"></i> Message sent successfully! Agent will contact you soon.</div>
                    <?php elseif ($message_error): ?>
                        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo safe($message_error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Your Name <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="name" placeholder="Enter your full name" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address <span style="color:#ef4444;">*</span></label>
                            <input type="email" name="email" placeholder="you@example.com" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" placeholder="+92 300 1234567">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Message <span style="color:#ef4444;">*</span></label>
                            <textarea name="message" placeholder="I'm interested in this property. Please contact me..." required></textarea>
                        </div>
                        <button type="submit" name="send_message" class="send-btn">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>

                <!-- Map Card -->
                <div class="map-card">
                    <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d217295.548657656!2d74.15480682463207!3d31.482859200428948!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39190483e58107d9%3A0xc23abe6ccc7e2462!2sLahore%2C%20Pakistan!5e0!3m2!1sen!2s!4v1714320000000!5m2!1sen!2s" allowfullscreen="" loading="lazy"></iframe>
                    <div class="address">
                        <i class="fas fa-map-pin"></i>
                        <?php echo safe($property['location'] . ', ' . $property['city']); ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Related Properties — 4 columns, same premium card design as home page -->
        <?php if (!empty($related)): ?>
        <h2 class="related-title"><i class="fas fa-building"></i> Similar Properties You May Like</h2>
        <div class="related-grid" id="relatedGrid">
            <?php foreach ($related as $rel):
                $rel_is_wishlisted = in_array((int) $rel['id'], $wishlisted_ids);
            ?>
            <div class="property-card premium-card" data-property-id="<?php echo $rel['id']; ?>">
                <div class="card-image-slider" id="rel_slider_<?php echo $rel['id']; ?>">
                    <div class="slider-container">
                        <div class="slider-track">
                            <?php foreach ($rel['images'] as $rimg): ?>
                                <img src="<?php echo htmlspecialchars($rimg); ?>"
                                     alt="<?php echo htmlspecialchars($rel['title']); ?>"
                                     loading="lazy"
                                     onerror="this.src='assets/images/house/1.jpg';">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="slider-btn slider-prev" onclick="prevSlide('rel_slider_<?php echo $rel['id']; ?>')">‹</button>
                    <button class="slider-btn slider-next" onclick="nextSlide('rel_slider_<?php echo $rel['id']; ?>')">›</button>
                    <div class="slider-dots" id="rel_dots_<?php echo $rel['id']; ?>"></div>
                </div>

                <div class="card-tag <?php echo $rel['purpose'] == 'Rent' ? 'for-rent' : 'for-sale'; ?>">
                    <?php echo $rel['purpose'] == 'Rent' ? 'For Rent' : 'For Sale'; ?>
                </div>
                <?php if (!empty($rel['featured'])): ?>
                    <div class="featured-badge">Featured</div>
                <?php endif; ?>

                <a href="javascript:void(0)" class="wishlist-icon <?php echo $rel_is_wishlisted ? 'active' : ''; ?>"
                   data-id="<?php echo (int) $rel['id']; ?>"
                   onclick="toggleWishlistDetail(this)" title="Add to wishlist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </a>

                <div class="card-body">
                    <h4><?php echo safe($rel['title']); ?></h4>
                    <div class="card-location">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php echo safe($rel['location']); ?><?php echo !empty($rel['city']) ? ', ' . safe($rel['city']) : ''; ?>
                    </div>
                    <div class="card-price">
                        <?php echo formatCardPrice($rel['price'], $rel['purpose']); ?>
                    </div>
                    <div class="card-meta">
                        <?php if (!empty($rel['bedrooms'])): ?><span class="meta-item"><i class="fas fa-bed"></i> <?php echo $rel['bedrooms']; ?> Beds</span><?php endif; ?>
                        <?php if (!empty($rel['bathrooms'])): ?><span class="meta-item"><i class="fas fa-bath"></i> <?php echo $rel['bathrooms']; ?> Baths</span><?php endif; ?>
                        <?php if (!empty($rel['area_size'])): ?><span class="meta-item"><i class="fas fa-vector-square"></i> <?php echo safe($rel['area_size']); ?></span><?php endif; ?>
                        <?php if (!empty($rel['property_type'])): ?><span class="meta-item"><i class="fas fa-building"></i> <?php echo safe($rel['property_type']); ?></span><?php endif; ?>
                    </div>
                    <a href="property-detail.php?id=<?php echo $rel['id']; ?>" class="view-detail-btn">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<script>
// ===================== MAIN GALLERY ===================== //
let currentIndex = 0;
const images = <?php echo json_encode($images); ?>;
const mainImg = document.getElementById('mainImg');
const thumbs = document.querySelectorAll('.gallery-thumbs img');

function changeImage(index) {
    if (index < 0) index = images.length - 1;
    if (index >= images.length) index = 0;
    currentIndex = index;
    mainImg.src = images[currentIndex];
    thumbs.forEach((thumb, i) => {
        thumb.classList.toggle('active', i === currentIndex);
    });
}

document.getElementById('prevBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    changeImage(currentIndex - 1);
});

document.getElementById('nextBtn').addEventListener('click', function(e) {
    e.stopPropagation();
    changeImage(currentIndex + 1);
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') changeImage(currentIndex - 1);
    if (e.key === 'ArrowRight') changeImage(currentIndex + 1);
});

// ===================== RELATED CARDS SLIDER (same logic as home page) ===================== //
let slideIndexes = {};

function initSlider(sliderId, imageCount) {
    slideIndexes[sliderId] = 0;
    updateDots(sliderId, imageCount);
}

function updateDots(sliderId, imageCount) {
    const dotsContainer = document.getElementById(sliderId.replace('rel_slider_', 'rel_dots_'));
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
    const imgs = track.querySelectorAll('img');
    if (index < 0) index = 0;
    if (index >= imgs.length) index = imgs.length - 1;
    slideIndexes[sliderId] = index;
    track.style.transform = `translateX(-${index * 100}%)`;
    updateDots(sliderId, imgs.length);
}

function prevSlide(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const imgs = track.querySelectorAll('img');
    let idx = slideIndexes[sliderId] || 0;
    idx--;
    if (idx < 0) idx = imgs.length - 1;
    goToSlide(sliderId, idx);
}

function nextSlide(sliderId) {
    const slider = document.getElementById(sliderId);
    if (!slider) return;
    const track = slider.querySelector('.slider-track');
    if (!track) return;
    const imgs = track.querySelectorAll('img');
    let idx = slideIndexes[sliderId] || 0;
    idx++;
    if (idx >= imgs.length) idx = 0;
    goToSlide(sliderId, idx);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#relatedGrid .card-image-slider').forEach(function(slider) {
        const track = slider.querySelector('.slider-track');
        if (track) {
            const imgs = track.querySelectorAll('img');
            initSlider(slider.id, imgs.length);
        }
    });
});

// ===================== WISHLIST (same AJAX flow as home page) ===================== //
function showWishlistToastDetail(message, isError) {
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

function toggleWishlistDetail(el) {
    const propertyId = el.getAttribute('data-id');
    fetch('toggle-wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'ajax=1&property_id=' + encodeURIComponent(propertyId)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'login_required') {
            showWishlistToastDetail('Please login to use wishlist', true);
            setTimeout(() => { window.location.href = 'login.php'; }, 1200);
        } else if (data.status === 'added') {
            el.classList.add('active');
            showWishlistToastDetail('Added to wishlist ❤️', false);
        } else if (data.status === 'removed') {
            el.classList.remove('active');
            showWishlistToastDetail('Removed from wishlist', false);
        } else {
            showWishlistToastDetail(data.message || 'Something went wrong', true);
        }
    })
    .catch(() => showWishlistToastDetail('Network error, please try again', true));
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>