<?php
$page_title = 'Add Property';
require_once __DIR__ . '/includes/config.php';

// Only logged-in sellers (or admins) can add a property
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_type = $_SESSION['user_type'] ?? '';
if ($user_type != 'seller' && $user_type != 'admin') {
    $_SESSION['error'] = "Only sellers can add properties. Please contact support if you'd like to become a seller.";
    header("Location: buyer-dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Seller';

// ============================================================
// PROFILE PICTURE PATH (same as dashboard)
// ============================================================
$profile_pic_path = '';
$pic_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($pic_check) > 0;

$profile_query = "SELECT u.*, SUM(CASE WHEN m.is_read = 0 THEN 1 ELSE 0 END) as total_messages
                  FROM users u
                  LEFT JOIN messages m ON u.id = m.receiver_id AND m.is_read = 0
                  WHERE u.id = $user_id
                  GROUP BY u.id";
$profile_result = mysqli_query($conn, $profile_query);
$profile = mysqli_fetch_assoc($profile_result);
$total_messages = (int)($profile['total_messages'] ?? 0);

if ($has_pic_column && !empty($profile['profile_pic'])) {
    $pic_file = "uploads/profiles/" . $profile['profile_pic'];
    if (file_exists($pic_file)) {
        $profile_pic_path = $pic_file . '?t=' . time();
    }
}
if (empty($profile_pic_path) && isset($_SESSION['profile_pic'])) {
    $pic_file = "uploads/profiles/" . $_SESSION['profile_pic'];
    if (file_exists($pic_file)) {
        $profile_pic_path = $pic_file . '?t=' . time();
    }
}
if (empty($profile_pic_path)) {
    $found_files = glob("uploads/profiles/seller_" . $user_id . "_*");
    if (!empty($found_files)) {
        $profile_pic_path = $found_files[0] . '?t=' . time();
        $_SESSION['profile_pic'] = basename($found_files[0]);
        if ($has_pic_column) {
            $safe_filename = mysqli_real_escape_string($conn, basename($found_files[0]));
            mysqli_query($conn, "UPDATE users SET profile_pic = '$safe_filename' WHERE id = $user_id");
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);

// ============================================================
// ERROR / SUCCESS MESSAGES
// ============================================================
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

// ============================================================
// DROPDOWN DATA (based on updated database)
// ============================================================
$property_types = ['House', 'Apartment', 'Plot', 'Commercial', 'Farm House', 'Villa', 'Penthouse', 'Portion'];
$purposes = ['Sale', 'Rent'];
$prices = [
    '1000000' => 'PKR 1M',
    '2000000' => 'PKR 2M',
    '3000000' => 'PKR 3M',
    '4000000' => 'PKR 4M',
    '5000000' => 'PKR 5M',
    '6000000' => 'PKR 6M',
    '7000000' => 'PKR 7M',
    '8000000' => 'PKR 8M',
    '9000000' => 'PKR 9M',
    '10000000' => 'PKR 10M'
];
$area_sizes = [
    '3 Marla', '5 Marla', '7 Marla', '10 Marla', '12 Marla',
    '1 Kanal', '2 Kanal',
    '500 Sqft', '1000 Sqft', '2000 Sqft', '5000 Sqft'
];
$cities = [
    'Lahore', 'Karachi', 'Islamabad', 'Rawalpindi', 'Faisalabad',
    'Multan', 'Peshawar', 'Quetta', 'Gujranwala', 'Sialkot',
    'Bahawalpur', 'Sargodha', 'Hyderabad', 'Sukkur', 'Larkana',
    'Abbottabad', 'Mardan', 'Swat', 'Gwadar', 'Muzaffarabad',
    'Sahiwal', 'Rahim Yar Khan', 'Dera Ghazi Khan', 'Nawabshah',
    'Mirpur Khas', 'Kohat', 'Dera Ismail Khan', 'Turbat', 'Khuzdar'
];
$locations = [
    'DHA', 'Bahria Town', 'Gulberg', 'Johar Town', 'Model Town',
    'Wapda Town', 'Canal View', 'Lake City', 'Valencia Town',
    'Paragon City', 'Askari', 'Garden Town', 'Township',
    'Iqbal Town', 'Sabzazar', 'Shadman', 'Muslim Town',
    'Faisal Town', 'Gulshan-e-Ravi', 'Defence', 'Clifton',
    'Gulshan-e-Iqbal', 'North Nazimabad', 'PECHS', 'Malir',
    'Scheme 33', 'Saddar', 'Nazimabad', 'F-6', 'F-7', 'F-8',
    'F-10', 'F-11', 'G-10', 'G-11', 'G-13', 'E-11',
    'Blue Area', 'PWD Housing Society', 'Satellite Town',
    'Chaklala Scheme', 'University Town', 'Hayatabad', 'Cantt',
    'Gulgasht Colony', 'Bosan Road', 'New Multan',
    'Shah Rukn-e-Alam', 'Others'
];
$bedrooms = [0,1,2,3,4,5,6];
$bathrooms = [1,2,3,4,5,6];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== RESET & BASE ===== */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        /* ===== SIDEBAR (exactly same as dashboard) ===== */
        .sidebar {
            width: 250px;
            min-width: 250px;
            background: #000000;
            padding: 24px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar::-webkit-scrollbar { width:4px; }
        .sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12); border-radius:10px; }

        .logo { padding:0 4px 20px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.06); }
        .logo a { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo-icon { font-size:30px; color:#0E7A4E; filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3)); }
        .logo-text { font-size:29px; font-weight:800; letter-spacing:-0.5px; color:#ffffff; position:relative; }
        .logo-text::after { content:''; position:absolute; left:0; bottom:-10px; width:45px; height:3px; background:#0E7A4E; border-radius:10px; }

        .sidebar-label { font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.3); text-transform: uppercase; letter-spacing: 1.5px; padding: 8px 12px; margin-bottom: 6px; }
        .sidebar-menu { list-style:none; padding:0; margin-bottom:12px; }
        .sidebar-menu li { margin-bottom:6px; }
        .sidebar-menu a {
            display:flex;
            align-items:center;
            gap:12px;
            padding:12px 16px;
            width:fit-content;
            min-width:165px;
            color:#b8c2cc;
            text-decoration:none;
            border-radius:12px;
            font-size:14px;
            font-weight:600;
            transition:all .3s ease;
        }
        .sidebar-menu a:hover {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            transform:translateX(6px);
            box-shadow:0 8px 20px rgba(14,122,78,.35);
        }
        .sidebar-menu a svg { width:20px; height:20px; transition:.3s; flex-shrink:0; }
        .sidebar-menu a:hover svg { transform:scale(1.15); }
        .sidebar-menu a.active {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            box-shadow:0 8px 20px rgba(14,122,78,.35);
        }
        .sidebar-menu a.active svg { transform:scale(1.1); }
        .badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            border-radius: 50px;
            padding: 2px 10px;
            font-size: 10px;
            font-weight: 700;
            min-width: 22px;
            text-align: center;
        }
        .logout-link { color:#b8c2cc !important; }
        .logout-link:hover { background:linear-gradient(135deg,#dc2626,#ef4444) !important; color:#fff !important; box-shadow:0 8px 20px rgba(220,38,38,.35) !important; }

        /* ===== TOP BAR (no search bar) ===== */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 20px;
            padding: 14px 32px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top:0;
            z-index:50;
        }
        .topbar-menu-btn { display:none; background:none; border:none; cursor:pointer; padding:6px; }

        .topbar-actions { display:flex; align-items:center; gap:10px; }
        .user-chip { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .user-avatar { width:36px; height:36px; border-radius:10px; background:#0E7A4E; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; overflow:hidden; flex-shrink:0; }
        .user-avatar img { width:100%; height:100%; object-fit:cover; }
        .user-info { display:flex; flex-direction:column; line-height:1.2; }
        .user-name { font-size:14px; font-weight:700; color:#0b1a2e; }
        .user-role { font-size:12px; color:#6b7a8f; }

        /* ===== MAIN CONTENT ===== */
        .main-content { flex:1; overflow-y:auto; }
        .content-inner { padding:28px 32px 40px; }

        /* ===== ADD PROPERTY FORM ===== */
        .add-property-section {
            max-width: 700px;
            margin: 0 auto;
        }
        .add-property-section h1 {
            font-size: 28px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .add-property-section .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }

        .property-form {
            background: #fff;
            padding: 28px 32px;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0,0,0,.07);
        }
        .form-row {
            display: flex;
            gap: 18px;
        }
        .form-row .form-group { flex:1; }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group.full { width:100%; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            height: 48px;
            padding: 0 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 14px;
            background: #fff;
            transition: .3s;
            box-sizing: border-box;
        }
        .form-group textarea {
            height: 120px;
            padding: 14px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #15803d;
            box-shadow: 0 0 0 4px rgba(22,163,74,.12);
        }
        input[type=file] {
            padding: 10px;
            height: auto;
        }
        .hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
            line-height: 1.5;
        }
        .submit-btn {
            display: inline-block;
            width: auto;
            padding: 14px 40px;
            background: #16A34A;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }
        .submit-btn:hover {
            background: #15803D;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(22,101,52,.25);
        }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
        }
        @media (max-width:768px) {
            .content-inner { padding:18px; }
            .topbar { padding:12px 18px; justify-content:space-between; }
            .form-row { flex-direction:column; gap:0; }
            .property-form { padding:20px; }
            .submit-btn { width:100%; }
            .add-property-section h1 { font-size:24px; }
            .user-info { display:none; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- SIDEBAR (exactly same as dashboard) -->
    <aside class="sidebar" id="sellerSidebar">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-home logo-icon"></i>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>
        <div class="sidebar-label">Main Menu</div>
        <ul class="sidebar-menu">
            <li>
                <a href="seller-dashboard.php" class="<?php echo ($current_page == 'seller-dashboard.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="my-properties.php" class="<?php echo ($current_page == 'my-properties.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    My Properties
                </a>
            </li>
            <li>
                <a href="add-property.php" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Add Property
                </a>
            </li>
            <li>
                <a href="messages.php" class="<?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Messages
                    <?php if ($total_messages > 0): ?><span class="badge"><?php echo $total_messages; ?></span><?php endif; ?>
                </a>
            </li>
            <li>
                <a href="wishlist.php" class="<?php echo ($current_page == 'wishlist.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    Wishlist
                </a>
            </li>
        </ul>

        <div class="sidebar-label">Account</div>
        <ul class="sidebar-menu">
            <li>
                <a href="profile-settings.php" class="<?php echo ($current_page == 'profile-settings.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg>
                    Profile Settings
                </a>
            </li>
            <li>
                <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a>
            </li>
            <li>
                <a href="logout.php" class="logout-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </a>
            </li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- TOP BAR (no search bar, only user) -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('sellerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-actions">
                <a href="profile-settings.php" class="user-chip">
                    <div class="user-avatar">
                        <?php if (!empty($profile_pic_path)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="<?php echo htmlspecialchars($user_name); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                        <span class="user-role">Seller</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="content-inner">
            <div class="add-property-section">

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form method="POST" action="process-property.php" enctype="multipart/form-data" class="property-form">
                    <!-- Heading moved inside the form to be part of the grid -->
                    <h1>Add New Property</h1>
                    <p class="subtitle">Fill in the details below to list your property.</p>

                    <input type="hidden" name="add_property" value="1">

                    <div class="form-group full">
                        <label>Enter Property Title</label>
                        <input type="text" name="title" placeholder="Modern 10 Marla House" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Property Type</label>
                            <select name="property_type" required>
                                <option value="">Choose Type</option>
                                <?php foreach ($property_types as $pt): ?>
                                    <option value="<?php echo htmlspecialchars($pt); ?>"><?php echo htmlspecialchars($pt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Purpose</label>
                            <select name="purpose" required>
                                <option value="">Select Purpose</option>
                                <?php foreach ($purposes as $pur): ?>
                                    <option value="<?php echo htmlspecialchars($pur); ?>"><?php echo $pur == 'Rent' ? 'For Rent' : 'For Sale'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (PKR)</label>
                            <select name="price" required>
                                <option value="">Select Price</option>
                                <?php foreach ($prices as $val => $label): ?>
                                    <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Area Size</label>
                            <select name="area" required>
                                <option value="">Select Area Size</option>
                                <?php foreach ($area_sizes as $area): ?>
                                    <option value="<?php echo htmlspecialchars($area); ?>"><?php echo htmlspecialchars($area); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <select name="city" required>
                                <option value="">Select City</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"><?php echo htmlspecialchars($city); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location / Area</label>
                            <select name="location" required>
                                <option value="">Select Area</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Bedrooms</label>
                            <select name="bedrooms">
                                <option value="">Select Bed-rooms</option>
                                <?php foreach ($bedrooms as $b): ?>
                                    <option value="<?php echo $b; ?>"><?php echo $b == 0 ? 'Studio Rooms' : ($b . ' Bed-room' . ($b > 1 ? 's' : '')); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bath-Room</label>
                            <select name="bathrooms">
                                <option value="">Choose Bath-Room</option>
                                <?php foreach ($bathrooms as $b): ?>
                                    <option value="<?php echo $b; ?>"><?php echo $b . ' Bathroom' . ($b > 1 ? 's' : ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" rows="5" placeholder="Describe the property..." required></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Property Photo</label>
                        <input type="file" name="main_image" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <p class="hint">Upload the real photo of this property (JPG, PNG, GIF, or WEBP). If you skip this, a generic placeholder image will be used instead.</p>
                    </div>

                    <button type="submit" class="submit-btn">Add Property</button>
                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>