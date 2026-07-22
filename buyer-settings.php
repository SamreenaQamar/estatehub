<?php
$page_title = 'Settings';
$hide_navbar = true;
require_once __DIR__ . '/includes/config.php';
include 'includes/header.php';

// ============================================================
// USER AUTHENTICATION
// ============================================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$buyer_id = (int) $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? '';

if (!in_array($user_type, ['user', 'buyer'])) {
    if ($user_type == 'seller') {
        header("Location: seller-dashboard.php");
    } elseif ($user_type == 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

$buyer_name = $_SESSION['user_name'] ?? 'Buyer';

// ============================================================
// PROFILE PICTURE PATH
// ============================================================
$profile_pic_path = '';
$pic_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($pic_check) > 0;

if ($has_pic_column) {
    $user_query = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = $buyer_id");
    if ($user_query && mysqli_num_rows($user_query) > 0) {
        $user_data = mysqli_fetch_assoc($user_query);
        if (!empty($user_data['profile_pic'])) {
            $pic_file = "uploads/profiles/" . $user_data['profile_pic'];
            if (file_exists($pic_file)) {
                $profile_pic_path = $pic_file . '?t=' . time();
            }
        }
    }
}
if (empty($profile_pic_path) && isset($_SESSION['profile_pic'])) {
    $pic_file = "uploads/profiles/" . $_SESSION['profile_pic'];
    if (file_exists($pic_file)) {
        $profile_pic_path = $pic_file . '?t=' . time();
    }
}
if (empty($profile_pic_path)) {
    $found_files = glob("uploads/profiles/user_" . $buyer_id . "_*");
    if (!empty($found_files)) {
        $profile_pic_path = $found_files[0] . '?t=' . time();
        $_SESSION['profile_pic'] = basename($found_files[0]);
        if ($has_pic_column) {
            $safe_filename = mysqli_real_escape_string($conn, basename($found_files[0]));
            mysqli_query($conn, "UPDATE users SET profile_pic = '$safe_filename' WHERE id = $buyer_id");
        }
    }
}

// ============================================================
// UNREAD MESSAGES COUNT (for badge)
// ============================================================
$unread_msgs = 0;
$msg_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE receiver_id = $buyer_id AND is_read = 0");
if ($msg_query) {
    $unread_msgs = (int) mysqli_fetch_assoc($msg_query)['total'];
}

// ============================================================
// FETCH USER SETTINGS (if any stored in DB)
// ============================================================
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $buyer_id"));

// Default settings (if columns exist, otherwise use defaults)
$email_notifications = $user['email_notifications'] ?? 1;
$sms_notifications = $user['sms_notifications'] ?? 0;
$marketing_emails = $user['marketing_emails'] ?? 1;
$two_factor = $user['two_factor'] ?? 0;
$show_profile = $user['show_profile'] ?? 1;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;
    $two_factor = isset($_POST['two_factor']) ? 1 : 0;
    $show_profile = isset($_POST['show_profile']) ? 1 : 0;

    // Check if columns exist, if not, add them (or use user_settings table)
    // We'll use the users table directly for simplicity (like seller settings)
    // First ensure columns exist
    $check_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'email_notifications'");
    if (mysqli_num_rows($check_col) == 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN email_notifications TINYINT DEFAULT 1");
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN sms_notifications TINYINT DEFAULT 0");
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN marketing_emails TINYINT DEFAULT 1");
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN two_factor TINYINT DEFAULT 0");
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN show_profile TINYINT DEFAULT 1");
    }

    $query = "UPDATE users SET 
              email_notifications = $email_notifications,
              sms_notifications = $sms_notifications,
              marketing_emails = $marketing_emails,
              two_factor = $two_factor,
              show_profile = $show_profile
              WHERE id = $buyer_id";
    if (mysqli_query($conn, $query)) {
        $success = "Settings saved successfully!";
        // Refresh user data
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $buyer_id"));
        $email_notifications = $user['email_notifications'];
        $sms_notifications = $user['sms_notifications'];
        $marketing_emails = $user['marketing_emails'];
        $two_factor = $user['two_factor'];
        $show_profile = $user['show_profile'];
    } else {
        $error = "Failed to save settings: " . mysqli_error($conn);
    }
}
?>
<style>
    /* ===== BUYER DASHBOARD LAYOUT (identical to dashboard) ===== */
    .buyer-dashboard-wrapper {
        display: flex;
        min-height: 100vh;
        background: #f4f6f5;
    }
    .buyer-sidebar {
        width: 250px;
        min-width: 250px;
        background: #000000;
        padding: 24px 16px;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow: hidden;
        z-index: 100;
        border-right: 1px solid rgba(255,255,255,0.05);
    }
    .buyer-sidebar .logo {
        padding:0 4px 20px;
        margin-bottom:20px;
        border-bottom:1px solid rgba(255,255,255,0.06);
    }
    .buyer-sidebar .logo a {
        display:flex;
        align-items:center;
        gap:10px;
        text-decoration:none;
    }
    .buyer-sidebar .logo-icon {
        font-size:30px;
        color:#0E7A4E;
        filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3));
    }
    .buyer-sidebar .logo-text {
        font-size:29px;
        font-weight:800;
        letter-spacing:-0.5px;
        color:#ffffff;
        position:relative;
    }
    .buyer-sidebar .logo-text::after {
        content:'';
        position:absolute;
        left:0;
        bottom:-10px;
        width:45px;
        height:3px;
        background:#0E7A4E;
        border-radius:10px;
    }
    .buyer-sidebar .sidebar-label {
        font-size: 11px;
        font-weight: 600;
        color: rgba(255,255,255,0.3);
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 8px 12px;
        margin-bottom: 6px;
    }
    .buyer-sidebar .sidebar-menu {
        list-style:none;
        padding:0;
        margin-bottom:12px;
    }
    .buyer-sidebar .sidebar-menu li {
        margin-bottom:6px;
    }
    .buyer-sidebar .sidebar-menu a {
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
    .buyer-sidebar .sidebar-menu a:hover {
        background:linear-gradient(135deg,#0E7A4E,#16a34a);
        color:#fff;
        transform:translateX(6px);
        box-shadow:0 8px 20px rgba(14,122,78,.35);
    }
    .buyer-sidebar .sidebar-menu a svg {
        width:20px;
        height:20px;
        transition:.3s;
    }
    .buyer-sidebar .sidebar-menu a:hover svg {
        transform:scale(1.15);
    }
    .buyer-sidebar .sidebar-menu a.active {
        background:linear-gradient(135deg,#0E7A4E,#16a34a);
        color:#fff;
        box-shadow:0 8px 20px rgba(14,122,78,.35);
    }
    .buyer-sidebar .sidebar-menu a.active svg {
        transform:scale(1.1);
    }
    .buyer-sidebar .badge {
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
    .logout-link {
        color:#b8c2cc !important;
    }
    .logout-link:hover {
        background:linear-gradient(135deg,#dc2626,#ef4444) !important;
        color:#fff !important;
        box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
    }

    .buyer-main-content {
        flex:1;
        overflow-y:auto;
    }
    .content-inner {
        padding:28px 32px 40px;
    }

    /* ===== TOP BAR (no search) ===== */
    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding: 14px 32px;
        background: #fff;
        border-bottom: 1px solid #e9ecef;
        position: sticky;
        top:0;
        z-index:50;
    }
    .topbar-menu-btn { display:none; background:none; border:none; cursor:pointer; padding:6px; }
    .topbar-actions { display:flex; align-items:center; gap:10px; margin-left: auto; }
    .user-chip { display:flex; align-items:center; gap:10px; text-decoration:none; }
    .user-avatar {
        width:36px; height:36px; border-radius:10px;
        background:#0E7A4E; color:#fff;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:15px; overflow:hidden;
        flex-shrink:0;
    }
    .user-avatar img { width:100%; height:100%; object-fit:cover; }
    .user-info { display:flex; flex-direction:column; line-height:1.2; }
    .user-name { font-size:14px; font-weight:700; color:#0b1a2e; }
    .user-role { font-size:12px; color:#6b7a8f; }

    /* ===== SETTINGS CONTAINER (centered, max-width: 800px) ===== */
    .settings-container {
        max-width: 800px;
        margin: 0 auto;
    }
    .settings-container h1 {
        font-size: 28px;
        font-weight: 800;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .settings-container .subtitle {
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 24px;
    }

    .settings-card {
        background: white;
        border-radius: 18px;
        padding: 28px 32px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 25px rgba(0,0,0,.07);
        margin-bottom: 24px;
    }
    .settings-card h2 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #E5E7EB;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #1f2937;
    }
    .settings-card h2 svg {
        flex-shrink: 0;
    }

    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 0;
        border-bottom: 1px solid #F3F4F6;
    }
    .setting-item:last-child {
        border-bottom: none;
    }
    .setting-info h4 {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 4px;
        color: #1f2937;
    }
    .setting-info p {
        font-size: 13px;
        color: #6B7280;
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
        flex-shrink: 0;
    }
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 24px;
    }
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
    }
    input:checked + .toggle-slider {
        background-color: #0E7A4E;
    }
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }

    .form-group {
        margin-bottom: 18px;
    }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 6px;
        color: #374151;
    }
    .form-group select {
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
    .form-group select:focus {
        outline: none;
        border-color: #15803d;
        box-shadow: 0 0 0 4px rgba(22,163,74,.12);
    }

    .save-settings-btn {
        background: #0E7A4E;
        color: white;
        padding: 14px 40px;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        margin-top: 8px;
        transition: background 0.3s ease;
        width: auto;
        display: inline-block;
    }
    .save-settings-btn:hover {
        background: #0a5c3a;
    }

    .danger-zone {
        background: #FEF2F2;
        border: 1px solid #FEE2E2;
    }
    .danger-zone h2 {
        color: #DC2626;
    }
    .delete-account-btn {
        background: #DC2626;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.3s ease;
        width: auto;
        display: inline-block;
    }
    .delete-account-btn:hover {
        background: #B91C1C;
    }

    .success-msg {
        background: #D1FAE5;
        color: #059669;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-weight: 500;
    }
    .error-msg {
        background: #FEE2E2;
        color: #DC2626;
        padding: 12px 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    @media (max-width:1100px) {
        .buyer-sidebar { position:fixed; left:-280px; transition:left 0.3s; }
        .buyer-sidebar.open { left:0; }
        .topbar-menu-btn { display:flex; }
    }
    @media (max-width:768px) {
        .content-inner { padding:18px; }
        .topbar { padding:12px 18px; justify-content:space-between; }
        .settings-card { padding:20px; }
        .setting-item { flex-direction:column; align-items:flex-start; gap:12px; }
        .settings-container h1 { font-size:24px; }
        .user-info { display:none; }
    }
</style>

<div class="buyer-dashboard-wrapper">
    <!-- ===== SIDEBAR ===== -->
    <aside class="buyer-sidebar" id="buyerSidebar">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-home logo-icon"></i>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>

        <div class="sidebar-label">Main Menu</div>
        <ul class="sidebar-menu">
            <li><a href="buyer-dashboard.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                Dashboard
            </a></li>
            <li><a href="buyer-messages.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Messages
                <?php if ($unread_msgs > 0): ?><span class="badge"><?php echo $unread_msgs; ?></span><?php endif; ?>
            </a></li>
            <li><a href="buyer-wishlist.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                Wishlist
            </a></li>
        </ul>

        <div class="sidebar-label">Account</div>
        <ul class="sidebar-menu">
            <li><a href="buyer-profile.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg>
                Profile Settings
            </a></li>
            <li><a href="buyer-settings.php" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                Settings
            </a></li>
            <li><a href="logout.php" class="logout-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a></li>
        </ul>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="buyer-main-content">

        <!-- ===== TOP BAR ===== -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('buyerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="topbar-actions">
                <a href="buyer-profile.php" class="user-chip">
                    <div class="user-avatar">
                        <?php if(!empty($profile_pic_path)): ?>
                            <img src="<?php echo htmlspecialchars($profile_pic_path); ?>" alt="<?php echo htmlspecialchars($buyer_name); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($buyer_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($buyer_name); ?></span>
                        <span class="user-role">Buyer</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="content-inner">
            <div class="settings-container">

                <h1>Settings</h1>
                <p class="subtitle">Manage your preferences and account settings.</p>

                <?php if ($success): ?>
                    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Notification Preferences -->
                <div class="settings-card">
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="2">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                        </svg>
                        Notification Preferences
                    </h2>
                    <form method="POST">
                        <div class="setting-item">
                            <div class="setting-info">
                                <h4>Email Notifications</h4>
                                <p>Receive property updates and offers via email</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <h4>SMS Alerts</h4>
                                <p>Get instant alerts on your phone</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="sms_notifications" <?php echo $sms_notifications ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="setting-item">
                            <div class="setting-info">
                                <h4>Marketing Emails</h4>
                                <p>Receive promotional offers and newsletters</p>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="marketing_emails" <?php echo $marketing_emails ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <button type="submit" name="save_settings" class="save-settings-btn">Save Preferences</button>
                    </form>
                </div>

                <!-- Language & Region -->
                <div class="settings-card">
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15 15 0 0 0 0 20 15 15 0 0 0 0-20z"/>
                        </svg>
                        Language & Region
                    </h2>
                    <div class="form-group">
                        <label>Language</label>
                        <select class="language-select" name="language">
                            <option value="en">English (United States)</option>
                            <option value="ur">Urdu (Pakistan)</option>
                            <option value="ar">Arabic</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Currency Display</label>
                        <select class="language-select" name="currency">
                            <option value="pkr">PKR - Pakistani Rupee</option>
                            <option value="usd">USD - US Dollar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Time Zone</label>
                        <select class="language-select" name="timezone">
                            <option value="asia/karachi">Asia/Karachi (PKT)</option>
                            <option value="asia/dubai">Asia/Dubai (GST)</option>
                        </select>
                    </div>
                </div>

                <!-- Privacy & Security -->
                <div class="settings-card">
                    <h2>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        Privacy & Security
                    </h2>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Two-Factor Authentication</h4>
                            <p>Add an extra layer of security to your account</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="two_factor" <?php echo $two_factor ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Show Profile in Search</h4>
                            <p>Allow others to find your profile</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="show_profile" <?php echo $show_profile ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>

            
                
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
        window.location.href = "delete-account.php";
    }
}

// ============================================ //
// SIDEBAR TOGGLE FOR MOBILE
// ============================================ //
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('buyerSidebar');
    if (sidebar) {
        document.querySelector('.topbar-menu-btn').addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
});
</script>

