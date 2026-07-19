<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$messageType = '';
$admin_id = $_SESSION['user_id'];

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id");
$user = mysqli_fetch_assoc($user_query);

$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($result) > 0;

$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'bio'");
$has_bio_column = mysqli_num_rows($result) > 0;

if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0 && $_FILES['profile_pic']['size'] > 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $_FILES['profile_pic']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $upload_dir = "../uploads/profiles/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
        $target_file = $upload_dir . $new_filename;

        $old_files = glob($upload_dir . 'admin_' . $admin_id . '_*');
        if ($old_files) {
            foreach ($old_files as $old_file) {
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
        }

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            if ($has_pic_column) {
                $pic_path = mysqli_real_escape_string($conn, $new_filename);
                mysqli_query($conn, "UPDATE users SET profile_pic = '$pic_path' WHERE id = $admin_id");
            }
            $_SESSION['profile_pic'] = $new_filename;
            $message = "Profile picture updated successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to upload image.";
            $messageType = "error";
        }
    } else {
        $message = "Invalid file type. Allowed: jpg, jpeg, png, gif, webp";
        $messageType = "error";
    }
}

if (isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $bio_text = isset($_POST['bio']) ? mysqli_real_escape_string($conn, $_POST['bio']) : '';

    $sql = "UPDATE users SET full_name = '$full_name', email = '$email', phone = '$phone'";

    if ($has_bio_column) {
        $sql .= ", bio = '$bio_text'";
    }

    if (!empty($_POST['new_password'])) {
        $password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $sql .= ", password = '$password'";
    }

    $sql .= " WHERE id = $admin_id";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['user_name'] = $full_name;
        $message = "Profile updated successfully!";
        $messageType = "success";

        $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id");
        $user = mysqli_fetch_assoc($user_query);
    } else {
        $message = "Error updating profile. Please try again.";
        $messageType = "error";
    }
}

$profile_pic_path = '';

if ($has_pic_column && !empty($user['profile_pic'])) {
    $pic_file = "../uploads/profiles/" . $user['profile_pic'];
    if (file_exists($pic_file)) {
        $profile_pic_path = $pic_file . '?t=' . time();
    }
}

if (empty($profile_pic_path) && isset($_SESSION['profile_pic'])) {
    $pic_file = "../uploads/profiles/" . $_SESSION['profile_pic'];
    if (file_exists($pic_file)) {
        $profile_pic_path = $pic_file . '?t=' . time();
    }
}

if (empty($profile_pic_path)) {
    $found_files = glob("../uploads/profiles/admin_" . $admin_id . "_*");
    if (!empty($found_files)) {
        $profile_pic_path = $found_files[0] . '?t=' . time();
        $_SESSION['profile_pic'] = basename($found_files[0]);

        if ($has_pic_column) {
            $safe_filename = mysqli_real_escape_string($conn, basename($found_files[0]));
            mysqli_query($conn, "UPDATE users SET profile_pic = '$safe_filename' WHERE id = $admin_id");
        }
    }
}

$stats = [
    'users' => 0,
    'pending' => 0,
    'unread' => 0
];

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['users'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties WHERE status = 'Pending'");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['pending'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE is_read = 0");
if ($result && mysqli_num_rows($result) > 0) {
    $stats['unread'] = mysqli_fetch_assoc($result)['total'] ?? 0;
}

$page_title = 'My Profile';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EstateHub Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ===== ALL EXISTING STYLES REMAIN UNCHANGED ===== */
        :root {
            --bg: #f1f5f9;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --blue: #16A366;
            --green: #10b981;
            --amber: #f59e0b;
            --purple: #8b5cf6;
            --red: #ef4444;
            --border: #e2e8f0;
            --text-primary: #0f172a;
            --text-muted: #94a3b8;
            --text-secondary: #475569;
            --card-bg: #ffffff;
            --radius-2xl: 24px;
            --radius-md: 12px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.1);
            --transition: all 0.25s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            display: flex;
            min-height: 100vh;
        }

        /* ===== SIDEBAR - EXACTLY LIKE OTHER ADMIN FILES ===== */
        .sidebar {
            width: 280px;
            min-width: 280px;
            max-width: 280px;
            background: #000000;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            color: white;
            z-index: 100;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 40px rgba(0, 0, 0, 0.15);
            overflow-y: auto;
            padding: 24px 16px;
        }

        .logo {
            padding:0 4px 20px;
            margin-bottom:20px;
            border-bottom:1px solid rgba(255,255,255,0.06);
        }
        .logo a {
            display:flex;
            align-items:center;
            gap:10px;
            text-decoration:none;
        }
        .logo-icon {
            font-size:30px;
            color:#0E7A4E;
            filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3));
        }
        .logo-text {
            font-size:29px;
            font-weight:800;
            letter-spacing:-0.5px;
            color:#ffffff;
            position:relative;
        }
        .logo-text::after {
            content:'';
            position:absolute;
            left:0;
            bottom:-10px;
            width:45px;
            height:3px;
            background:#0E7A4E;
            border-radius:10px;
        }

        .sidebar-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 8px 12px;
            margin-bottom: 6px;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-bottom: 12px;
        }
        .sidebar-menu li {
            margin-bottom: 6px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            width: fit-content;
            min-width: 165px;
            color: #b8c2cc;
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            transition: all .3s ease;
        }
        .sidebar-menu a:hover {
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: #fff;
            transform: translateX(6px);
            box-shadow: 0 8px 20px rgba(14,122,78,.35);
        }
        .sidebar-menu a svg {
            width: 20px;
            height: 20px;
            transition: .3s;
        }
        .sidebar-menu a:hover svg {
            transform: scale(1.15);
        }
        .sidebar-menu a.active {
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: #fff;
            box-shadow: 0 8px 20px rgba(14,122,78,.35);
        }
        .sidebar-menu a.active svg {
            transform: scale(1.1);
        }
        .nav-badge {
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
        .nav-badge.blue {
            background: #0E7A4E;
        }

        .logout-link {
            color: #b8c2cc !important;
        }
        .logout-link:hover {
            background: linear-gradient(135deg, #dc2626, #ef4444) !important;
            color: #fff !important;
            box-shadow: 0 8px 20px rgba(220,38,38,.35) !important;
        }

        .sidebar-footer {
            padding: 20px 16px;
            border-top: 1px solid rgba(255,255,255,0.08);
            flex-shrink: 0;
            margin-top: auto;
        }
        .admin-profile-mini {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 14px;
            cursor: pointer;
            transition: .3s;
        }
        .admin-profile-mini:hover {
            background: rgba(255,255,255,0.1);
        }
        .admin-avatar-mini {
            width: 44px;
            height: 44px;
            min-width: 44px;
            min-height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--amber), var(--red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
            overflow: hidden;
        }
        .admin-avatar-mini img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .admin-info-mini h4 {
            font-size: 14px;
            font-weight: 600;
            color: #e2e8f0;
        }
        .admin-info-mini span {
            font-size: 11px;
            color: #64748b;
        }

        /* ===== REST OF THE STYLES (UNCHANGED) ===== */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
        }

        .topbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 20px 36px;
            position: sticky;
            top: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .topbar h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .topbar-profile-pic {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--blue), var(--purple));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid var(--border);
        }

        .topbar-profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .content-area {
            padding: 36px;
            max-width: 800px;
        }

        .profile-header-card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            padding: 40px;
            border: 1px solid var(--border);
            text-align: center;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .profile-avatar-lg {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--amber), var(--red));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
            margin: 0 auto 20px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(245, 158, 11, 0.3);
            transition: var(--transition);
        }

        .profile-avatar-lg:hover {
            transform: scale(1.05);
        }

        .profile-avatar-lg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-avatar-lg .overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            padding: 8px;
            font-size: 10px;
            color: white;
            opacity: 0;
            transition: var(--transition);
        }

        .profile-avatar-lg:hover .overlay {
            opacity: 1;
        }

        .profile-header-card h2 {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .profile-header-card .email-text {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .profile-header-card .badge-admin {
            display: inline-block;
            background: linear-gradient(135deg, var(--amber), var(--red));
            color: white;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 12px;
        }

        .profile-form-card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            padding: 36px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .profile-form-card h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-form-card h2 i {
            color: var(--blue);
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid var(--border);
            border-radius: 14px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: #f8fafc;
            color: var(--text-primary);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--blue);
            background: white;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .btn {
            padding: 14px 32px;
            border-radius: 50px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--blue), #0e7a4e);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-outline {
            background: #f1f5f9;
            color: var(--text-secondary);
            border-radius: var(--radius-md);
            padding: 10px 20px;
            font-size: 13px;
            border: none;
        }

        .btn-outline:hover {
            background: #e2e8f0;
        }

        .alert {
            padding: 16px 24px;
            border-radius: 16px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: opacity 0.5s, transform 0.5s;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .content-area {
                padding: 16px;
            }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR - REPLACED WITH EXACT SAME AS OTHER ADMIN FILES ===== -->
<aside class="sidebar">
    <div class="logo">
        <a href="../index.php">
            <i class="fas fa-home logo-icon"></i>
            <span class="logo-text">EstateHub</span>
        </a>
    </div>

    <div class="sidebar-label">Main Menu</div>
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                    <path d="M3 9h18M9 21V9"/>
                </svg>
                Dashboard
            </a>
        </li>
        <li>
            <a href="manage-users.php" class="<?php echo ($current_page == 'manage-users.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="5"/>
                    <path d="M20 21a8 8 0 1 0-16 0"/>
                </svg>
                Users
                <span class="nav-badge"><?php echo $stats['users']; ?></span>
            </a>
        </li>
        <li>
            <a href="manage-properties.php" class="<?php echo ($current_page == 'manage-properties.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                Properties
                <?php if ($stats['pending'] > 0): ?>
                    <span class="nav-badge"><?php echo $stats['pending']; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="manage-messages.php" class="<?php echo ($current_page == 'manage-messages.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                Messages
                <?php if ($stats['unread'] > 0): ?>
                    <span class="nav-badge blue"><?php echo $stats['unread']; ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>

    <div class="sidebar-label">System</div>
    <ul class="sidebar-menu">
        <li>
            <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                    <line x1="2" y1="20" x2="22" y2="20"/>
                </svg>
                Reports
            </a>
        </li>
        <li>
            <a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                Settings
            </a>
        </li>
        <li>
            <a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M5 20v-2a7 7 0 0 1 14 0v2"/>
                </svg>
                Profile
            </a>
        </li>
        <li>
            <a href="../logout.php" class="logout-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                Logout
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="admin-profile-mini" onclick="window.location.href='profile.php'">
            <div class="admin-avatar-mini">
                <?php if (!empty($profile_pic_path)): ?>
                    <img src="<?php echo $profile_pic_path; ?>" alt="Admin" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <i class="fas fa-crown" style="display:none;"></i>
                <?php else: ?>
                    <i class="fas fa-crown"></i>
                <?php endif; ?>
            </div>
            <div class="admin-info-mini">
                <h4><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h4>
                <span>Super Admin</span>
            </div>
        </div>
    </div>
</aside>

<!-- ===== MAIN CONTENT - UNCHANGED ===== -->
<main class="main-content">
    <header class="topbar">
        <h1>My Profile</h1>
        <div class="topbar-profile-pic" onclick="window.location.href='profile.php'">
            <?php if (!empty($profile_pic_path)): ?>
                <img src="<?php echo $profile_pic_path; ?>" alt="Admin" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <i class="fas fa-user-shield" style="display:none;"></i>
            <?php else: ?>
                <i class="fas fa-user-shield"></i>
            <?php endif; ?>
        </div>
    </header>

    <div class="content-area">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$has_pic_column): ?>
            <div class="alert alert-warning">
                <i class="fas fa-database"></i>
                Run SQL: <code style="background: #fef3c7; padding: 2px 6px; border-radius: 4px;">ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER phone;</code>
            </div>
        <?php endif; ?>

        <div class="profile-header-card">
            <div class="profile-avatar-lg" onclick="document.getElementById('picInput').click()" title="Click to change photo">
                <?php if (!empty($profile_pic_path)): ?>
                    <img src="<?php echo $profile_pic_path; ?>" alt="Profile" id="profilePreview" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                    <i class="fas fa-crown" style="display:none;"></i>
                <?php else: ?>
                    <i class="fas fa-crown"></i>
                <?php endif; ?>
                <div class="overlay">Change Photo</div>
            </div>
            <form method="POST" enctype="multipart/form-data" id="picForm" style="display: none;">
                <input type="file" id="picInput" name="profile_pic" accept="image/*" onchange="this.form.submit()">
            </form>
            <h2><?php echo htmlspecialchars($user['full_name'] ?? $_SESSION['user_name'] ?? 'Admin'); ?></h2>
            <p class="email-text"><?php echo htmlspecialchars($user['email'] ?? 'admin@estatehub.com'); ?></p>
            <span class="badge-admin"><i class="fas fa-shield-alt"></i> Super Admin</span>
        </div>

        <div class="profile-form-card">
            <h2><i class="fas fa-user-edit"></i> Edit Profile Information</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Profile Picture</label>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('picInput2').click()">
                        <i class="fas fa-camera"></i> Choose Photo
                    </button>
                    <input type="file" id="picInput2" name="profile_pic" accept="image/*" style="display: none;" onchange="this.form.submit()">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '+92 300 1234567'); ?>">
                    </div>
                    <div class="form-group">
                        <label>New Password (optional)</label>
                        <input type="password" name="new_password" placeholder="Leave blank to keep current">
                    </div>
                </div>
                <?php if ($has_bio_column): ?>
                    <div class="form-group">
                        <label>Bio</label>
                        <textarea name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                <?php endif; ?>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        }, 4000);
    });
});
</script>

</body>
</html>