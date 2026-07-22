<?php
// No $basePath needed — original theme
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>EstateHub | Find Your Dream Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,400;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
   <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php
// Unread message count (no longer used, but kept for compatibility)
$header_unread_count = 0;
$header_profile_pic = '';
if (isset($_SESSION['user_id']) && isset($conn)) {
    $hdr_uid = (int) $_SESSION['user_id'];
    $hdr_msg_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE receiver_id = $hdr_uid AND is_read = 0");
    if ($hdr_msg_result) {
        $header_unread_count = (int) (mysqli_fetch_assoc($hdr_msg_result)['total'] ?? 0);
    }
    $hdr_user_result = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = $hdr_uid");
    if ($hdr_user_result && mysqli_num_rows($hdr_user_result) > 0) {
        $header_profile_pic = mysqli_fetch_assoc($hdr_user_result)['profile_pic'] ?? '';
    }
}

// Determine dashboard link and user role
$dashboard_link = 'dashboard.php';
$user_role_display = '';
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] == 'seller') {
        $dashboard_link = 'seller-dashboard.php';
        $user_role_display = 'Seller';
    } elseif ($_SESSION['user_type'] == 'user') {
        $dashboard_link = 'buyer-dashboard.php';
        $user_role_display = 'Buyer';
    } elseif ($_SESSION['user_type'] == 'admin') {
        $dashboard_link = 'admin/index.php';
        $user_role_display = 'Admin';
    }
}

// Check if we are on the home page to decide whether to show the role label
$is_home_page = (basename($_SERVER['PHP_SELF']) == 'index.php');

$header_pic_path = "uploads/profiles/" . $header_profile_pic;
$has_header_pic = !empty($header_profile_pic) && file_exists($header_pic_path);
?>

<?php /* ===== CONDITIONAL HEADER – hide when $hide_navbar = true ===== */ ?>
<?php if (!isset($hide_navbar) || !$hide_navbar): ?>
<header class="header">
    <div class="header-container">
        <div class="logo">
            <a href="index.php">
                <div class="logo-box">
                    <i class="fas fa-home"></i>
                </div>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>

        <nav class="nav-menu">
            <ul>
                <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="listings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'listings.php' ? 'active' : ''; ?>">Listings</a></li>
                <li><a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a></li>
                <li><a href="contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Wishlist and Messages links REMOVED -->
             <!-- inside header-actions -->
<div class="profile-dropdown">
    <button type="button" class="profile-trigger-btn" onclick="document.getElementById('headerProfileMenu').classList.toggle('open')">
        <span class="profile-avatar-btn">
            <?php if ($has_header_pic): ?>
                <img src="<?php echo htmlspecialchars($header_pic_path); ?>" alt="Profile">
            <?php else: ?>
                <span><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
            <?php endif; ?>
        </span>
        <span class="profile-info">
            <span class="profile-name-text"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
            <?php if ($is_home_page && !empty($user_role_display)): ?>
                <span class="profile-role-label"><?php echo htmlspecialchars($user_role_display); ?></span>
            <?php endif; ?>
        </span>
    </button>
    <!-- dropdown menu stays same -->
</div>
                    <div class="profile-dropdown-menu" id="headerProfileMenu">
                        <div class="profile-dropdown-header">
                            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
                            <!-- No role badge displayed here -->
                        </div>
                        <a href="<?php echo $dashboard_link; ?>"><i class="fas fa-gauge"></i> Dashboard</a>
                        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- For guests: no wishlist link either -->
                <a href="login.php" class="btn-outline">Login</a>
                <a href="signup.php" class="btn-solid">Sign Up</a>
            <?php endif; ?>
        </div>

        <button class="mobile-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>
<?php endif; /* end conditional header */ ?>

<script>
document.addEventListener('click', function(e) {
    const menu = document.getElementById('headerProfileMenu');
    const btn = document.querySelector('.profile-trigger-btn');
    if (menu && menu.classList.contains('open') && !menu.contains(e.target) && btn && !btn.contains(e.target)) {
        menu.classList.remove('open');
    }
});
</script>