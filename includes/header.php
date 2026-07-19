<!DOCTYPE html>
<h1>Heelo jind</h1>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>EstateHub | Find Your Dream Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Fraunces:ital,wght@0,400;0,500;0,600;0,700;1,500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body>

<?php
// Unread message count for the header notification badge
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
?>

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
                <a href="wishlist.php" class="wishlist">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    Wishlist
                </a>
                <a href="messages.php" class="wishlist" style="position: relative;">
                    <svg width="35" height="35" viewBox="0 0 24 24" fill="currentColor">
    <path d="M6 4C4.34 4 3 5.34 3 7V15C3 16.66 4.34 18 6 18H8L6.5 22L12 18H18C19.66 18 21 16.66 21 15V7C21 5.34 19.66 4 18 4H6Z"/>

    <circle cx="8" cy="11" r="1.3" fill="white"/>
    <circle cx="12" cy="11" r="1.3" fill="white"/>
    <circle cx="16" cy="11" r="1.3" fill="white"/>
</svg>

                    <?php if ($header_unread_count > 0): ?>
                        <span style="position:absolute;top:-6px;right:-10px;background:#EF4444;color:#fff;font-size:11px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px;line-height:1;"><?php echo $header_unread_count > 9 ? '9+' : $header_unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php
                $dashboard_link = 'dashboard.php';
                $role_label = 'User';
                if (isset($_SESSION['user_type'])) {
                    if ($_SESSION['user_type'] == 'seller') {
                        $dashboard_link = 'seller-dashboard.php';
                        $role_label = 'Seller';
                    } elseif ($_SESSION['user_type'] == 'user') {
                        $dashboard_link = 'buyer-dashboard.php';
                        $role_label = 'User';
                    } elseif ($_SESSION['user_type'] == 'admin') {
                        $dashboard_link = 'admin/index.php';
                        $role_label = 'Admin';
                    }
                }
                $header_pic_path = "uploads/profiles/" . $header_profile_pic;
                $has_header_pic = !empty($header_profile_pic) && file_exists($header_pic_path);
                ?>
                <div class="profile-dropdown">
                    <button type="button" class="profile-trigger-btn" onclick="document.getElementById('headerProfileMenu').classList.toggle('open')">
                        <span class="profile-avatar-btn">
                            <?php if ($has_header_pic): ?>
                                <img src="<?php echo htmlspecialchars($header_pic_path); ?>" alt="Profile">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="profile-name-text"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                    </button>
                    <div class="profile-dropdown-menu" id="headerProfileMenu">
                        <div class="profile-dropdown-header">
                            <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></strong>
                            <span class="profile-role-badge"><?php echo htmlspecialchars($role_label); ?></span>
                        </div>
                        <a href="<?php echo $dashboard_link; ?>"><i class="fas fa-gauge"></i> Dashboard</a>
                        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="wishlist.php" class="wishlist">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                    Wishlist
                </a>
                <a href="login.php" class="btn-outline">Login</a>
                <a href="signup.php" class="btn-solid">Sign Up</a>
            <?php endif; ?>
        </div>

        <button class="mobile-toggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<script>
document.addEventListener('click', function(e) {
    const menu = document.getElementById('headerProfileMenu');
    const btn = document.querySelector('.profile-trigger-btn');
    if (menu && menu.classList.contains('open') && !menu.contains(e.target) && btn && !btn.contains(e.target)) {
        menu.classList.remove('open');
    }
});
</script>
