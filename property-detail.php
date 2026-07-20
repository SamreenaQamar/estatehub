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
        
        // --- Build images array with proper path checking ---
        $image_paths = [];
        
        // Check main image
        if (!empty($property['image_main'])) {
            // Try direct path
            if (file_exists($property['image_main'])) {
                $image_paths[] = $property['image_main'];
            } elseif (file_exists('../' . $property['image_main'])) {
                $image_paths[] = '../' . $property['image_main'];
            } else {
                $image_paths[] = $property['image_main'];
            }
        }
        
        // Check extra images
        if (!empty($property['images'])) {
            $extra = explode(',', $property['images']);
            foreach ($extra as $img) {
                $img = trim($img);
                if (!empty($img)) {
                    if (file_exists($img)) {
                        $image_paths[] = $img;
                    } elseif (file_exists('../' . $img)) {
                        $image_paths[] = '../' . $img;
                    } else {
                        $image_paths[] = $img;
                    }
                }
            }
        }
        
        // Deduplicate and filter out empty
        $images = array_unique(array_filter($image_paths));
        
        // If no images, use placeholder
        if (empty($images)) {
            // Get property type for better placeholder
            $type = strtolower($property['property_type'] ?? 'house');
            $images[] = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800&h=600&fit=crop&crop=center&sig=' . $property_id;
        }
        
        // --- Agent image ---
        $agent_image = 'https://ui-avatars.com/api/?name=' . urlencode($property['agent_name']) . '&background=0E7A4E&color=fff&size=100';
        if (!empty($property['agent_image'])) {
            $pic_file = "../uploads/profiles/" . $property['agent_image'];
            if (file_exists($pic_file)) {
                $agent_image = $pic_file;
            }
        }
        $property['agent_image_url'] = $agent_image;
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
    SELECT id, title, price, location, image_main 
    FROM properties 
    WHERE city = '$city' AND id != $property_id AND status = 'Active' 
    ORDER BY RAND() LIMIT 3
";
$rel_result = mysqli_query($conn, $rel_query);
if ($rel_result) {
    while ($row = mysqli_fetch_assoc($rel_result)) {
        // Determine image path
        $img = 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400&h=300&fit=crop&crop=center&sig=' . $row['id'];
        if (!empty($row['image_main'])) {
            if (file_exists($row['image_main'])) {
                $img = $row['image_main'];
            } elseif (file_exists('../' . $row['image_main'])) {
                $img = '../' . $row['image_main'];
            } else {
                $img = $row['image_main'];
            }
        }
        $row['image'] = $img;
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

// formatPrice() is already defined in config.php - do not redeclare
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['title']); ?> - EstateHub</title>
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

        /* ===== GALLERY ===== */
        .gallery-wrapper {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
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
        }
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
        .info-card .price .rent-unit {
            font-size: 16px;
            font-weight: 400;
            color: #6B7280;
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
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .agent-card .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid #0E7A4E;
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
        .agent-card .email-display {
            font-size: 13px;
            color: #4B5563;
            margin-bottom: 15px;
            word-break: break-all;
        }
        .agent-card .email-display i {
            color: #0E7A4E;
            margin-right: 6px;
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
            color: #0E7A4E;
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
            color: #0E7A4E;
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

        /* ===== RELATED PROPERTIES ===== */
        .related-title {
            font-size: 24px;
            font-weight: 700;
            color: #0b1a2e;
            margin: 40px 0 20px;
        }
        .related-title i {
            color: #0E7A4E;
            margin-right: 10px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        @media (max-width: 768px) {
            .related-grid {
                grid-template-columns: 1fr;
            }
        }
        .related-card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            transition: 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .related-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.1);
        }
        .related-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .related-card-body {
            padding: 16px 18px;
        }
        .related-card-body h4 {
            font-size: 16px;
            font-weight: 700;
            color: #0b1a2e;
            margin-bottom: 4px;
        }
        .related-card-body .location {
            font-size: 13px;
            color: #6B7280;
            margin-bottom: 8px;
        }
        .related-card-body .location i {
            color: #0E7A4E;
            margin-right: 4px;
        }
        .related-card-body .price {
            font-weight: 700;
            color: #0E7A4E;
            margin-bottom: 10px;
        }
        .related-card-body .view-link {
            color: #0E7A4E;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: 0.3s;
            padding: 8px 16px;
            background: #E7F5EC;
            border-radius: 8px;
        }
        .related-card-body .view-link:hover {
            gap: 12px;
            background: #0E7A4E;
            color: #fff;
        }
        .related-card-body .view-link i {
            font-size: 14px;
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
        }
    </style>
</head>
<body>

<section class="property-detail-section">
    <div class="container">

        <div class="detail-grid">
            <!-- LEFT COLUMN -->
            <div>

                <!-- Gallery -->
                <div class="gallery-wrapper">
                    <div class="main-image" id="mainImage">
                        <img src="<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>">
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs" id="galleryThumbs">
                        <?php foreach ($images as $index => $img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="Thumb" class="<?php echo $index == 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo htmlspecialchars($img); ?>', this)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Property Info -->
                <div class="info-card">
                    <h1><?php echo htmlspecialchars($property['title']); ?></h1>
                    <div class="location">
                        <i class="fas fa-map-pin"></i>
                        <?php echo htmlspecialchars($property['location'] . ', ' . $property['city']); ?>
                    </div>
                    <div class="price">
                        PKR <?php echo formatPrice($property['price']); ?>
                        <?php if ($property['purpose'] == 'Rent'): ?>
                            <span class="rent-unit">/ Month</span>
                        <?php endif; ?>
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
                            <span><?php echo htmlspecialchars($property['area']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span><?php echo $property['purpose'] == 'Sale' ? 'For Sale' : 'For Rent'; ?></span>
                        </div>
                        <?php if (!empty($property['property_type'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-home"></i>
                            <span><?php echo htmlspecialchars($property['property_type']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="description">
                        <h3><i class="fas fa-align-left"></i> Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                    </div>

                    <?php
                    // Features from property
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
                            <span class="feature-tag"><i class="fas fa-check"></i><?php echo htmlspecialchars($feature); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div>

                <!-- Agent Card -->
                <div class="agent-card">
                    <img src="<?php echo htmlspecialchars($property['agent_image_url']); ?>" alt="Agent" class="avatar">
                    <div class="name"><?php echo htmlspecialchars($property['agent_name']); ?></div>
                    <div class="title"><i class="fas fa-user-tie"></i> Property Agent</div>
                    <div class="email-display">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($property['agent_email']); ?>
                    </div>

                    <div class="agent-contact-buttons">
                        <?php if (!empty($property['agent_phone'])): ?>
                        <a href="https://wa.me/92<?php echo ltrim($property['agent_phone'], '+'); ?>" target="_blank" class="btn-whatsapp">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a href="tel:<?php echo htmlspecialchars($property['agent_phone']); ?>" class="btn-call">
                            <i class="fas fa-phone"></i> Call Now
                        </a>
                        <?php endif; ?>
                        <a href="mailto:<?php echo htmlspecialchars($property['agent_email']); ?>" class="btn-email">
                            <i class="fas fa-envelope"></i> Email Agent
                        </a>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form-card">
                    <h3><i class="fas fa-pen"></i> Request a Visit</h3>

                    <?php if ($message_sent): ?>
                        <div class="success-msg"><i class="fas fa-check-circle"></i> Message sent successfully! Agent will contact you soon.</div>
                    <?php elseif ($message_error): ?>
                        <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($message_error); ?></div>
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
                        <?php echo htmlspecialchars($property['location'] . ', ' . $property['city']); ?>
                    </div>
                </div>

            </div>
        </div>

        <!-- Related Properties -->
        <?php if (!empty($related)): ?>
        <h2 class="related-title"><i class="fas fa-building"></i> Similar Properties You May Like</h2>
        <div class="related-grid">
            <?php foreach ($related as $rel): ?>
            <div class="related-card">
                <img src="<?php echo htmlspecialchars($rel['image']); ?>" alt="<?php echo htmlspecialchars($rel['title']); ?>">
                <div class="related-card-body">
                    <h4><?php echo htmlspecialchars($rel['title']); ?></h4>
                    <div class="location"><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($rel['location']); ?></div>
                    <div class="price">PKR <?php echo formatPrice($rel['price']); ?></div>
                    <a href="property-detail.php?id=<?php echo $rel['id']; ?>" class="view-link">
                        View Details <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<script>
function changeImage(src, element) {
    document.getElementById('mainImage').querySelector('img').src = src;
    document.querySelectorAll('.gallery-thumbs img').forEach(img => img.classList.remove('active'));
    element.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>