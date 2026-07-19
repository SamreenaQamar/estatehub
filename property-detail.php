<?php
$page_title = 'Property Details';
include 'includes/config.php';
include 'includes/header.php';

// Get property ID from URL
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// All properties data (matching your index.php)
$all_properties = [
    1 => [
        'id' => 1, 'title' => 'Modern 10 Marla House', 'location' => 'DHA Phase 6, Lahore', 'city' => 'Lahore',
        'price' => 5200000, 'type' => 'Sale', 'ptype' => 'House', 'beds' => 5, 'baths' => 6, 'area' => '10 Marla',
        'extra' => 'Ready', 'year' => 2023, 'purpose' => 'Sale',
        'description' => 'Beautiful modern family home located in the heart of DHA Lahore. Contemporary design, spacious layout and contemporary furniture make this property a perfect fit for your family. Features include modern kitchen, spacious bedrooms, luxurious bathrooms, and a beautiful garden area.',
        'features' => ['Central AC', 'Modern Kitchen', 'Security System', 'Parking', 'Wardrobes', 'Garden'],
        'images' => [
            'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg',
            'https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg',
            'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg',
            'https://images.pexels.com/photos/1571459/pexels-photo-1571459.jpeg'
        ],
        'agent_name' => 'Ali Raza', 'agent_phone' => '+92 300 1234567', 'agent_email' => 'ali@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/men/1.jpg'
    ],
    2 => [
        'id' => 2, 'title' => 'Designer Villa', 'location' => 'F-10, Islamabad', 'city' => 'Islamabad',
        'price' => 7800000, 'type' => 'Sale', 'ptype' => 'House', 'beds' => 6, 'baths' => 7, 'area' => '1 Kanal',
        'extra' => 'Luxury', 'year' => 2024, 'purpose' => 'Sale',
        'description' => 'Premium designer villa in F-10 sector with stunning views. This luxurious property features spacious rooms, modern amenities, and prime location. Perfect for families looking for luxury living.',
        'features' => ['Swimming Pool', 'Gym', 'Smart Home', 'Security', 'Terrace', 'Double Garage'],
        'images' => [
            'https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg',
            'https://images.pexels.com/photos/1396132/pexels-photo-1396132.jpeg',
            'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg',
            'https://images.pexels.com/photos/1571458/pexels-photo-1571458.jpeg'
        ],
        'agent_name' => 'Sara Khan', 'agent_phone' => '+92 300 7654321', 'agent_email' => 'sara@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/women/1.jpg'
    ],
    3 => [
        'id' => 3, 'title' => 'Luxury Apartment', 'location' => 'Bahria Town, Karachi', 'city' => 'Karachi',
        'price' => 120000, 'type' => 'Rent', 'ptype' => 'Apartment', 'beds' => 3, 'baths' => 3, 'area' => '2000 Sqft',
        'extra' => 'Furnished', 'year' => 2022, 'purpose' => 'Rent',
        'description' => 'Luxury apartment in Bahria Town with all modern amenities. This fully furnished apartment offers comfortable living with easy access to all facilities.',
        'features' => ['Furnished', 'Lift', 'Parking', 'Security', 'Gym', 'Swimming Pool'],
        'images' => [
            'https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg',
            'https://images.pexels.com/photos/2587056/pexels-photo-2587056.jpeg',
            'https://images.pexels.com/photos/2587052/pexels-photo-2587052.jpeg',
            'https://images.pexels.com/photos/2587058/pexels-photo-2587058.jpeg'
        ],
        'agent_name' => 'Ahmed Malik', 'agent_phone' => '+92 300 9876543', 'agent_email' => 'ahmed@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/men/2.jpg'
    ],
    4 => [
        'id' => 4, 'title' => 'Double Kitchen House', 'location' => 'Model Town, Lahore', 'city' => 'Lahore',
        'price' => 3450000, 'type' => 'Sale', 'ptype' => 'House', 'beds' => 4, 'baths' => 4, 'area' => '8 Marla',
        'extra' => '2 Kitchens', 'year' => 2021, 'purpose' => 'Sale',
        'description' => 'Beautiful house with double kitchen setup. Perfect for joint family system. Spacious rooms and modern amenities.',
        'features' => ['Double Kitchen', 'Store Room', 'Parking', 'Garden', 'Security'],
        'images' => [
            'https://images.pexels.com/photos/280229/pexels-photo-280229.jpeg',
            'https://images.pexels.com/photos/280232/pexels-photo-280232.jpeg',
            'https://images.pexels.com/photos/280233/pexels-photo-280233.jpeg',
            'https://images.pexels.com/photos/280234/pexels-photo-280234.jpeg'
        ],
        'agent_name' => 'Ali Raza', 'agent_phone' => '+92 300 1234567', 'agent_email' => 'ali@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/men/1.jpg'
    ],
    5 => [
        'id' => 5, 'title' => 'Luxury Penthouse', 'location' => 'Clifton, Karachi', 'city' => 'Karachi',
        'price' => 250000, 'type' => 'Rent', 'ptype' => 'Apartment', 'beds' => 4, 'baths' => 5, 'area' => '3500 Sqft',
        'extra' => 'Sea View', 'year' => 2023, 'purpose' => 'Rent',
        'description' => 'Luxury penthouse with breathtaking sea views. Premium location with all modern amenities.',
        'features' => ['Sea View', 'Jacuzzi', 'Smart Home', 'Parking', 'Gym'],
        'images' => [
            'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg',
            'https://images.pexels.com/photos/258158/pexels-photo-258158.jpeg',
            'https://images.pexels.com/photos/258159/pexels-photo-258159.jpeg',
            'https://images.pexels.com/photos/258160/pexels-photo-258160.jpeg'
        ],
        'agent_name' => 'Sara Khan', 'agent_phone' => '+92 300 7654321', 'agent_email' => 'sara@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/women/1.jpg'
    ],
    6 => [
        'id' => 6, 'title' => 'Modern Farm House', 'location' => 'Gulberg Greens, Islamabad', 'city' => 'Islamabad',
        'price' => 9500000, 'type' => 'Sale', 'ptype' => 'Farm House', 'beds' => 5, 'baths' => 6, 'area' => '2 Kanal',
        'extra' => 'Pool+Garden', 'year' => 2024, 'purpose' => 'Sale',
        'description' => 'Beautiful farm house with swimming pool and lush garden. Perfect for weekend getaways.',
        'features' => ['Swimming Pool', 'Garden', 'Gazebo', 'Parking', 'Security'],
        'images' => [
            'https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg',
            'https://images.pexels.com/photos/208740/pexels-photo-208740.jpeg',
            'https://images.pexels.com/photos/208738/pexels-photo-208738.jpeg',
            'https://images.pexels.com/photos/208739/pexels-photo-208739.jpeg'
        ],
        'agent_name' => 'Ahmed Malik', 'agent_phone' => '+92 300 9876543', 'agent_email' => 'ahmed@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/men/2.jpg'
    ],
    7 => [
        'id' => 7, 'title' => 'Beach Front Villa', 'location' => 'DHA, Karachi', 'city' => 'Karachi',
        'price' => 15800000, 'type' => 'Sale', 'ptype' => 'Villa', 'beds' => 7, 'baths' => 8, 'area' => '4 Kanal',
        'extra' => 'Beach View', 'year' => 2024, 'purpose' => 'Sale',
        'description' => 'Exclusive beach front villa with private beach access. Ultimate luxury living.',
        'features' => ['Private Beach', 'Infinity Pool', 'Home Theater', 'Smart Home', 'Security'],
        'images' => ['https://images.pexels.com/photos/1029599/pexels-photo-1029599.jpeg'],
        'agent_name' => 'Ali Raza', 'agent_phone' => '+92 300 1234567', 'agent_email' => 'ali@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/men/1.jpg'
    ],
    8 => [
        'id' => 8, 'title' => 'Cozy Studio', 'location' => 'Gulberg, Lahore', 'city' => 'Lahore',
        'price' => 45000, 'type' => 'Rent', 'ptype' => 'Apartment', 'beds' => 2, 'baths' => 2, 'area' => '1200 Sqft',
        'extra' => 'Fully Furnished', 'year' => 2023, 'purpose' => 'Rent',
        'description' => 'Cozy studio apartment, fully furnished and ready to move in. Perfect for bachelors or small families.',
        'features' => ['Fully Furnished', 'AC', 'WiFi', 'Parking', 'Security'],
        'images' => ['https://images.pexels.com/photos/1643383/pexels-photo-1643383.jpeg'],
        'agent_name' => 'Sara Khan', 'agent_phone' => '+92 300 7654321', 'agent_email' => 'sara@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/women/1.jpg'
    ],
    9 => [
        'id' => 9, 'title' => 'Commercial Plaza', 'location' => 'Blue Area, Islamabad', 'city' => 'Islamabad',
        'price' => 25500000, 'type' => 'Sale', 'ptype' => 'Commercial', 'beds' => 0, 'baths' => 0, 'area' => '5000 Sqft',
        'extra' => 'Prime Location', 'year' => 2024, 'purpose' => 'Sale',
        'description' => 'Prime commercial plaza in the heart of Blue Area. Perfect for business investment.',
        'features' => ['Prime Location', 'Parking', 'Security', 'Lift', 'Generator'],
        'images' => ['https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg'],
        'agent_name' => 'Ahmed Malik', 'agent_phone' => '+92 300 9876543', 'agent_email' => 'ahmed@estatehub.com', 'agent_image' => 'https://randomuser.me/api/portraits/men/2.jpg'
    ],
];

// Get current property
$property = isset($all_properties[$property_id]) ? $all_properties[$property_id] : $all_properties[1];

// Get related properties (same city, excluding current)
$related = array_filter($all_properties, function($p) use ($property) {
    return $p['city'] == $property['city'] && $p['id'] != $property['id'];
});
$related = array_slice($related, 0, 3);

// Handle message submission
$message_sent = false;
$message_error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $msg_text = mysqli_real_escape_string($conn, $_POST['message']);

    // Always keep a copy in inquiries (useful even for logged-out visitors)
    $subject = mysqli_real_escape_string($conn, 'Inquiry about ' . $property['title']);
    mysqli_query($conn, "INSERT INTO inquiries (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$msg_text')");

    // Figure out the sender: use the logged-in account if there is one,
    // otherwise try to match an existing account by the email they typed
    // (so the message shows up for them once they log in).
    if (isset($_SESSION['user_id'])) {
        $sender_id = (int) $_SESSION['user_id'];
    } else {
        $sender_id = 0;
        $email_esc = mysqli_real_escape_string($conn, $email);
        $sender_lookup = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_esc' LIMIT 1");
        if ($sender_lookup && mysqli_num_rows($sender_lookup) > 0) {
            $sender_id = (int) mysqli_fetch_assoc($sender_lookup)['id'];
        }
    }

    // Resolve the agent's real user id from their email shown on this listing
    $receiver_id = 0;
    $agent_email_esc = mysqli_real_escape_string($conn, $property['agent_email']);
    $agent_lookup = mysqli_query($conn, "SELECT id FROM users WHERE email = '$agent_email_esc' LIMIT 1");
    if ($agent_lookup && mysqli_num_rows($agent_lookup) > 0) {
        $receiver_id = (int) mysqli_fetch_assoc($agent_lookup)['id'];
    }

    if ($sender_id > 0 && $receiver_id > 0 && $receiver_id != $sender_id) {
        // Only link property_id if it actually exists in the properties table (foreign key)
        $real_property_id = (int) $property['id'];
        $prop_check = mysqli_query($conn, "SELECT id FROM properties WHERE id = $real_property_id");
        $linked_property_id = ($prop_check && mysqli_num_rows($prop_check) > 0) ? $real_property_id : 'NULL';

        $full_message = $msg_text;
        if (!empty($_POST['phone'])) {
            $full_message .= "\n\nContact phone: " . $phone;
        }
        $full_message_esc = mysqli_real_escape_string($conn, $full_message);

        $insert = mysqli_query($conn, "INSERT INTO messages (sender_id, receiver_id, property_id, message, created_at) VALUES ($sender_id, $receiver_id, $linked_property_id, '$full_message_esc', NOW())");
        if ($insert) {
            $message_sent = true;
        } else {
            $message_error = "Something went wrong, please try again.";
        }
    } else if ($sender_id == 0) {
        $message_error = "Your inquiry was saved. Login with the same email to see the agent's replies in Messages.";
    } else {
        $message_error = "This agent can't be messaged right now.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $property['title']; ?> - EstateHub</title>
    <style>
        .property-detail-section {
            padding: 40px 0 60px;
            background: #F8FAFC;
        }
        .property-gallery {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .main-image {
            position: relative;
            height: 500px;
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
            background: white;
            overflow-x: auto;
        }
        .gallery-thumbs img {
            width: 100px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .gallery-thumbs img:hover,
        .gallery-thumbs img.active {
            border-color: #0E7A4E;
        }
        .property-info-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .property-title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 15px;
        }
        .property-location {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6B7280;
            margin-bottom: 20px;
        }
        .property-price {
            font-size: 28px;
            font-weight: 700;
            color: #0E7A4E;
            margin-bottom: 20px;
        }
        .property-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            padding: 20px 0;
            border-top: 1px solid #E5E7EB;
            border-bottom: 1px solid #E5E7EB;
            margin-bottom: 20px;
        }
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .meta-item svg {
            width: 22px;
            height: 22px;
            stroke: #0E7A4E;
        }
        .description {
            color: #6B7280;
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        .feature-tag {
            background: #E7F5EC;
            color: #0E7A4E;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
        }
        .agent-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .agent-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }
        .agent-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .agent-title {
            color: #6B7280;
            font-size: 13px;
            margin-bottom: 15px;
        }
        .agent-contact {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .agent-contact a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .agent-contact .phone-btn {
            background: #0E7A4E;
            color: white;
        }
        .agent-contact .email-btn {
            background: #F3F4F6;
            color: #111827;
        }
        .contact-form-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .send-btn {
            width: 100%;
            padding: 12px;
            background: #0E7A4E;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }
        .map-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .related-title {
            font-size: 24px;
            font-weight: 700;
            margin: 30px 0 20px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .related-card {
            background: white;
            border-radius: 18px;
            overflow: hidden;
            transition: all 0.3s;
        }
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .related-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .related-card-body {
            padding: 15px;
        }
        .related-card-body h4 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .related-price {
            color: #0E7A4E;
            font-weight: 700;
            margin: 8px 0;
        }
        .success-msg {
            background: #D1FAE5;
            color: #059669;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        @media (max-width: 900px) {
            .related-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .main-image { height: 300px; }
            .related-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<section class="property-detail-section">
    <div class="container">
        <div class="content-wrapper" style="display: flex; gap: 30px; flex-wrap: wrap;">
            
            <!-- LEFT COLUMN -->
            <div style="flex: 2;">
                <!-- Image Gallery -->
                <div class="property-gallery">
                    <div class="main-image" id="mainImage">
                        <img src="<?php echo $property['images'][0]; ?>" alt="<?php echo $property['title']; ?>">
                    </div>
                    <div class="gallery-thumbs" id="galleryThumbs">
                        <?php foreach($property['images'] as $index => $img): ?>
                        <img src="<?php echo $img; ?>" alt="Thumb" class="<?php echo $index == 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo $img; ?>', this)">
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Property Info -->
                <div class="property-info-card">
                    <h1 class="property-title"><?php echo $property['title']; ?></h1>
                    <div class="property-location">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <?php echo $property['location']; ?>, <?php echo $property['city']; ?>
                    </div>
                    <div class="property-price">
                        PKR <?php echo number_format($property['price'] / 1000000, 1); ?>M
                        <?php if($property['type'] == 'Rent'): ?>
                        <span style="font-size: 16px;">/ Month</span>
                        <?php endif; ?>
                    </div>
                    <div class="property-meta">
                        <?php if($property['beds'] > 0): ?>
                        <div class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                            <span><?php echo $property['beds']; ?> Beds</span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12H2"/><path d="M6 9h12"/></svg>
                            <span><?php echo $property['baths']; ?> Baths</span>
                        </div>
                        <div class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                            <span><?php echo $property['area']; ?></span>
                        </div>
                        <div class="meta-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/></svg>
                            <span><?php echo $property['type']; ?></span>
                        </div>
                    </div>
                    <div class="description">
                        <h3 style="margin-bottom: 10px;">Description</h3>
                        <p><?php echo $property['description']; ?></p>
                    </div>
                    <div>
                        <h3 style="margin-bottom: 15px;">Key Features</h3>
                        <div class="features-list">
                            <?php foreach($property['features'] as $feature): ?>
                            <span class="feature-tag">✓ <?php echo $feature; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- RIGHT COLUMN -->
            <div style="flex: 1;">
                <!-- Agent Card -->
                <div class="agent-card">
                    <img src="<?php echo $property['agent_image']; ?>" alt="Agent" class="agent-image">
                    <h3 class="agent-name"><?php echo $property['agent_name']; ?></h3>
                    <p class="agent-title">Property Agent</p>
                    <div class="agent-contact">
                        <a href="tel:<?php echo $property['agent_phone']; ?>" class="phone-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                            <?php echo $property['agent_phone']; ?>
                        </a>
                        <a href="mailto:<?php echo $property['agent_email']; ?>" class="email-btn">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Email Agent
                        </a>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form-card">
                    <h3 style="margin-bottom: 20px;">Request a Visit</h3>
                    <?php if($message_sent): ?>
                    <div class="success-msg">Message sent successfully! Agent will contact you soon.</div>
                    <?php elseif($message_error): ?>
                    <div class="success-msg" style="background:#FEE2E2;color:#991B1B;border-color:#FECACA;"><?php echo htmlspecialchars($message_error); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Your Name" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="Your Phone">
                        </div>
                        <div class="form-group">
                            <textarea name="message" placeholder="I'm interested in this property. Please contact me..." required></textarea>
                        </div>
                        <button type="submit" name="send_message" class="send-btn">Send Message</button>
                    </form>
                </div>
                
                <!-- Map Card -->
                <div class="map-card">
                    <h3 style="margin-bottom: 15px;">Location on Map</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d217295.548657656!2d74.15480682463207!3d31.482859200428948!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39190483e58107d9%3A0xc23abe6ccc7e2462!2sLahore%2C%20Pakistan!5e0!3m2!1sen!2s!4v1714320000000!5m2!1sen!2s" width="100%" height="200" style="border:0; border-radius:12px;" allowfullscreen="" loading="lazy"></iframe>
                    <p style="margin-top: 10px; font-size: 13px; color: #6B7280;">📍 <?php echo $property['location']; ?>, <?php echo $property['city']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Related Properties -->
        <?php if(count($related) > 0): ?>
        <h2 class="related-title">Similar Properties You May Like</h2>
        <div class="related-grid">
            <?php foreach($related as $rel): ?>
            <div class="related-card">
                <img src="<?php echo $rel['images'][0]; ?>" alt="<?php echo $rel['title']; ?>">
                <div class="related-card-body">
                    <h4><?php echo $rel['title']; ?></h4>
                    <p style="font-size: 13px; color: #6B7280;">📍 <?php echo $rel['location']; ?></p>
                    <div class="related-price">PKR <?php echo number_format($rel['price'] / 1000000, 1); ?>M</div>
                    <a href="property-detail.php?id=<?php echo $rel['id']; ?>" style="display: inline-block; margin-top: 10px; color: #0E7A4E; text-decoration: none; font-weight: 600;">View Details →</a>
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

<?php include 'includes/footer.php'; ?>
</body>
</html>