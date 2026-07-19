<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message        = '';
$messageType    = '';
$admin_id       = $_SESSION['user_id'];

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id");
$user       = mysqli_fetch_assoc($user_query);

$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($result) > 0;

$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'bio'");
$has_bio_column = mysqli_num_rows($result) > 0;

// ----------------------------------------------------------------------
// UPLOAD PROFILE PICTURE
// ----------------------------------------------------------------------
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0 && $_FILES['profile_pic']['size'] > 0) {
    $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename  = $_FILES['profile_pic']['name'];
    $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $upload_dir   = "../uploads/profiles/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
        $target_file  = $upload_dir . $new_filename;

        $old_files = glob($upload_dir . 'admin_' . $admin_id . '_*');
        if ($old_files) {
            foreach ($old_files as $old_file) {
                if (file_exists($old_file)) unlink($old_file);
            }
        }

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            if ($has_pic_column) {
                $pic_path = mysqli_real_escape_string($conn, $new_filename);
                mysqli_query($conn, "UPDATE users SET profile_pic = '$pic_path' WHERE id = $admin_id");
            }
            $_SESSION['profile_pic'] = $new_filename;
            $message     = "Profile picture updated successfully!";
            $messageType = "success";
        } else {
            $message     = "Failed to upload image.";
            $messageType = "error";
        }
    } else {
        $message     = "Invalid file type. Allowed: jpg, jpeg, png, gif, webp";
        $messageType = "error";
    }
}

// ----------------------------------------------------------------------
// DELETE PROFILE PICTURE (FIXED)
// ----------------------------------------------------------------------
if (isset($_GET['delete_photo']) && $_GET['delete_photo'] == 1) {
    $has_pic        = false;
    $pic_to_delete  = '';

    // Check database for picture
    if ($has_pic_column && !empty($user['profile_pic'])) {
        $pic_to_delete = $user['profile_pic'];
        $has_pic       = true;
    }

    // Check session
    if (!$has_pic && isset($_SESSION['profile_pic'])) {
        $pic_to_delete = $_SESSION['profile_pic'];
        $has_pic       = true;
    }

    // Fallback: scan the uploads folder
    if (!$has_pic) {
        $found_files = glob("../uploads/profiles/admin_" . $admin_id . "_*");
        if (!empty($found_files)) {
            $pic_to_delete = basename($found_files[0]);
            $has_pic       = true;
        }
    }

    if ($has_pic && !empty($pic_to_delete)) {
        $pic_file = "../uploads/profiles/" . $pic_to_delete;
        if (file_exists($pic_file)) {
            unlink($pic_file);
        }

        // Remove from database
        if ($has_pic_column) {
            mysqli_query($conn, "UPDATE users SET profile_pic = NULL WHERE id = $admin_id");
        }
        $_SESSION['profile_pic'] = NULL;

        // Refresh user data
        $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $admin_id");
        $user       = mysqli_fetch_assoc($user_query);

        // Redirect to avoid resubmission
        header("Location: profile.php?deleted=1");
        exit();
    } else {
        // No picture to delete
        header("Location: profile.php?error=nopic");
        exit();
    }
}

// Show messages after redirect
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $message     = "Profile picture removed successfully.";
    $messageType = "success";
}
if (isset($_GET['error']) && $_GET['error'] == 'nopic') {
    $message     = "No profile picture found to delete.";
    $messageType = "error";
}

// ----------------------------------------------------------------------
// UPDATE PROFILE
// ----------------------------------------------------------------------
if (isset($_POST['update_profile'])) {
    $has_pic = false;

    if ($has_pic_column && !empty($user['profile_pic'])) {
        $pic_file = "../uploads/profiles/" . $user['profile_pic'];
        if (file_exists($pic_file)) $has_pic = true;
    }

    if (!$has_pic && isset($_SESSION['profile_pic'])) {
        $pic_file = "../uploads/profiles/" . $_SESSION['profile_pic'];
        if (file_exists($pic_file)) $has_pic = true;
    }

    if (!$has_pic) {
        $found_files = glob("../uploads/profiles/admin_" . $admin_id . "_*");
        if (!empty($found_files)) $has_pic = true;
    }

    if (!$has_pic) {
        $message     = "Profile picture is required. Please upload a photo before saving.";
        $messageType = "error";
    } else {
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $bio_text  = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));

        $sql = "UPDATE users SET full_name = '$full_name', phone = '$phone'";
        if ($has_bio_column) $sql .= ", bio = '$bio_text'";
        $sql .= " WHERE id = '$admin_id'";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['user_name'] = $full_name;
            $message     = "Profile updated successfully.";
            $messageType = "success";

            $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id='$admin_id'");
            $user       = mysqli_fetch_assoc($user_query);
        } else {
            $message     = "Database Error: " . mysqli_error($conn);
            $messageType = "error";
        }
    }
}

// ----------------------------------------------------------------------
// HANDLE PASSWORD CHANGE
// ----------------------------------------------------------------------
if (isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (password_verify($current, $user['password'])) {
        if ($new == $confirm && strlen($new) >= 6) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $admin_id");
            $message     = "Password changed successfully!";
            $messageType = "success";
        } else {
            $message     = "New password doesn't match or is too short (min 6 characters)!";
            $messageType = "error";
        }
    } else {
        $message     = "Current password is incorrect!";
        $messageType = "error";
    }
}

// ----------------------------------------------------------------------
// GET PROFILE PICTURE PATH
// ----------------------------------------------------------------------
$profile_pic_path = '';

if ($has_pic_column && !empty($user['profile_pic'])) {
    $pic_file = "../uploads/profiles/" . $user['profile_pic'];
    if (file_exists($pic_file)) $profile_pic_path = $pic_file . '?t=' . time();
}

if (empty($profile_pic_path) && isset($_SESSION['profile_pic'])) {
    $pic_file = "../uploads/profiles/" . $_SESSION['profile_pic'];
    if (file_exists($pic_file)) $profile_pic_path = $pic_file . '?t=' . time();
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

// ----------------------------------------------------------------------
// SIDEBAR STATS
// ----------------------------------------------------------------------
$stats = [
    'users'   => 0,
    'pending' => 0,
    'unread'  => 0
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

// ----------------------------------------------------------------------
// EXTRA STATS
// ----------------------------------------------------------------------
$my_properties_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties WHERE user_id = $admin_id");
if ($res && mysqli_num_rows($res) > 0) {
    $my_properties_count = mysqli_fetch_assoc($res)['total'] ?? 0;
}

$total_views = 0;
$res = mysqli_query($conn, "SHOW COLUMNS FROM properties LIKE 'views'");
if ($res && mysqli_num_rows($res) > 0) {
    $res2 = mysqli_query($conn, "SELECT SUM(views) as total FROM properties WHERE user_id = $admin_id");
    if ($res2 && mysqli_num_rows($res2) > 0) {
        $total_views = mysqli_fetch_assoc($res2)['total'] ?? 0;
    }
}

$page_title   = 'My Profile';
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
        /* ============================================================
           ROOT VARIABLES
           ============================================================ */
        :root {
            --bg: #f1f5f9;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --blue: #0E7A4E;
            --green: #0E7A4E;
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
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 40px rgba(0,0,0,0.1);
            --transition: all 0.25s ease;
        }

        /* ============================================================
           RESET & BASE
           ============================================================ */
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
            overflow-y: auto;
        }

        /* ============================================================
           SIDEBAR
           ============================================================ */
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
            box-shadow: 4px 0 40px rgba(0,0,0,0.15);
            overflow-y: auto;
            padding: 24px 16px;
            transition: transform 0.25s ease;
        }

        .sidebar.sidebar-hidden {
            transform: translateX(-100%);
        }

        .logo {
            padding: 0 4px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .logo a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 30px;
            color: #0E7A4E;
            filter: drop-shadow(0 2px 6px rgba(14,122,78,0.3));
        }

        .logo-text {
            font-size: 29px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            position: relative;
        }

        .logo-text::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 45px;
            height: 3px;
            background: #0E7A4E;
            border-radius: 10px;
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

        /* ============================================================
           MAIN CONTENT
           ============================================================ */
        .main-content {
            margin-left: 280px;
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .topbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-hamburger {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            border: none;
            background: transparent;
            color: var(--text-primary);
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .topbar-hamburger:hover {
            background: #f1f5f9;
        }

        .topbar h1 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-primary);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .topbar-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            text-decoration: none;
            transition: var(--transition);
        }

        .topbar-icon-btn:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }

        .topbar-icon-btn .badge-count {
            position: absolute;
            top: 2px;
            right: 2px;
            background: var(--red);
            color: white;
            font-size: 9px;
            font-weight: 700;
            min-width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
        }

        .topbar-icon-btn .badge-count.green {
            background: var(--blue);
        }

        .topbar-divider {
            width: 1px;
            height: 26px;
            background: var(--border);
            margin: 0 4px;
        }

        .topbar-profile-pic {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--amber), var(--red));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid var(--border);
            flex-shrink: 0;
        }

        .topbar-profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .topbar-admin-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 4px 8px 4px 4px;
            border-radius: 50px;
            transition: var(--transition);
        }

        .topbar-admin-chip:hover {
            background: #f1f5f9;
        }

        .topbar-admin-chip .who h4 {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .topbar-admin-chip .who span {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .topbar-admin-chip .fa-chevron-down {
            font-size: 11px;
            color: var(--text-muted);
            margin-left: 2px;
        }

        /* ============================================================
           CONTENT AREA
           ============================================================ */
        .content-area {
            flex: 1;
            padding: 24px 32px;
            width: 100%;
            overflow-y: auto;
        }

        .alert {
            padding: 14px 20px;
            border-radius: 14px;
            margin-bottom: 16px;
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

        /* ============================================================
           PROFILE PAGE GRID
           ============================================================ */
        .profile-page-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* ============================================================
           LEFT PROFILE CARD
           ============================================================ */
        .profile-side-card {
            background: var(--card-bg);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .profile-side-banner {
            height: 110px;
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            position: relative;
        }

        .profile-side-avatar-wrap {
            display: flex;
            justify-content: center;
            margin-top: -65px;
            position: relative;
        }

        .profile-side-avatar {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            overflow: hidden;
            margin: auto;
            border: 5px solid #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .profile-side-avatar:hover {
            transform: scale(1.03);
        }

        .profile-side-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .default-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: #0E7A4E;
        }

        .profile-side-body {
            text-align: center;
            padding: 12px 24px 24px;
        }

        .profile-side-body h2 {
            font-size: 19px;
            font-weight: 800;
            color: var(--text-primary);
            margin-top: 4px;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            color: #059669;
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 700;
            margin: 8px 0 16px;
        }

        .profile-side-contact {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding-top: 6px;
        }

        .contact-item {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .contact-item i {
            color: var(--text-muted);
            width: 16px;
            text-align: center;
        }

        .profile-side-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            padding: 20px 24px 12px;
            border-top: 1px solid var(--border);
            margin-top: 6px;
        }

        .stat-box {
            background: transparent;
            border-radius: 14px;
            padding: 6px 8px;
            text-align: center;
        }

        .stat-box i {
            color: var(--text-secondary);
            font-size: 17px;
            margin-bottom: 8px;
            display: block;
        }

        .stat-box.views i {
            color: var(--blue);
        }

        .stat-box .num {
            font-size: 19px;
            font-weight: 800;
            color: var(--text-primary);
            display: block;
        }

        .stat-box .lbl {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .profile-side-completion {
            padding: 4px 24px 20px;
        }

        .profile-side-completion .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .profile-side-completion .row span:first-child {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .profile-side-completion .row span:last-child {
            font-size: 13px;
            font-weight: 800;
            color: var(--blue);
        }

        .completion-bar {
            height: 7px;
            background: var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .completion-bar .fill {
            height: 100%;
            background: linear-gradient(90deg, var(--blue), #34d399);
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        .photo-actions {
            margin-top: 20px;
            padding: 0 24px 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .change-photo-btn {
            width: 100%;
            height: 45px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: #fff;
            border: none;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(14,122,78,0.2);
        }

        .change-photo-btn:hover {
            background: linear-gradient(135deg, #0a5c3a, #0E7A4E);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(14,122,78,0.3);
        }

        .delete-photo-btn {
            width: 100%;
            height: 45px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            background: #fef2f2;
            color: #dc2626;
            border: 1.5px solid #fecaca;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            text-decoration: none; /* for <a> tag */
        }

        .delete-photo-btn:hover {
            background: #fee2e2;
            border-color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239,68,68,0.15);
        }

        .delete-photo-btn i {
            color: #dc2626;
        }

        /* ============================================================
           RIGHT COLUMN
           ============================================================ */
        .profile-right-col {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .bottom-settings-row {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 24px;
            width: 100%;
            align-items: stretch;
        }

        .settings-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all .3s ease;
        }

        .settings-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        .settings-card .card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 18px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eef2f7;
        }

        .settings-card .card-header .icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #ecfdf5;
            color: #0E7A4E;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .settings-card .card-header.icon-danger .icon {
            background: #fef2f2;
            color: #ef4444;
        }

        .settings-card .card-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .settings-card .card-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        /* ============================================================
           FORMS
           ============================================================ */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .form-group label i {
            color: #4B5563;
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            height: 50px;
            padding: 0 16px;
            border: 1.5px solid #dbe2ea;
            border-radius: 12px;
            font-size: 14px;
            background: #fff;
            transition: .3s;
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
        }

        .form-group textarea {
            height: auto;
            min-height: 80px;
            padding: 12px 16px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0E7A4E;
            box-shadow: 0 0 0 4px rgba(14,122,78,.10);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .email-display {
            padding: 11px 16px;
            background: #f1f5f9;
            border: 2px solid var(--border);
            border-radius: 12px;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
            line-height: 1.5;
            height: 50px;
            display: flex;
            align-items: center;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 44px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 15px;
            padding: 4px;
        }

        .password-toggle:hover {
            color: var(--text-secondary);
        }

        /* ============================================================
           BUTTONS
           ============================================================ */
        .btn {
            padding: 12px 26px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0E7A4E, #16a34a);
            color: #fff;
            border: none;
            padding: 10px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(14,122,78,.25);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0a5c3a, #0E7A4E);
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(14,122,78,.35);
        }

        .btn-block {
            width: auto;
            min-width: 170px;
        }

        /* ============================================================
           ACCOUNT ZONE
           ============================================================ */
        .account-zone-notice {
            background: #fffaf0;
            border: 1px solid #fde68a;
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            gap: 12px;
            width: 100%;
            align-items: flex-start;
            margin: 0 auto 24px;
        }

        .account-zone-notice i {
            color: #f59e0b;
            font-size: 16px;
            width: 20px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .account-zone-notice h4 {
            font-size: 13px;
            font-weight: 700;
            color: #92400e;
            margin: 0 0 4px;
        }

        .account-zone-notice p {
            font-size: 12px;
            color: #92400e;
            line-height: 1.5;
            margin: 0;
        }

        .zone-btn {
            width: 100%;
            height: 50px;
            margin: 0 0 12px 0;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            border: 1.5px solid var(--border);
            background: #fff;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .zone-btn:hover {
            transform: translateY(-2px);
        }

        .logout-zone-btn {
            color: #ef4444;
            border-color: #fecaca;
            margin-top: 40px;
        }

        .logout-zone-btn:hover {
            background: #fef2f2;
            border-color: #ef4444;
        }

        .delete-zone-btn {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
            margin-bottom: 0;
        }

        .delete-zone-btn:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        /* ============================================================
           FOOTER
           ============================================================ */
        .page-footer {
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
            padding: 24px 0 4px;
        }

        /* ============================================================
           RESPONSIVE
           ============================================================ */
        @media (max-width: 1024px) {
            .sidebar {
                display: none;
            }

            .main-content {
                margin-left: 0;
            }

            .profile-page-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991px) {
            .profile-page-grid {
                grid-template-columns: 1fr;
            }

            .bottom-settings-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .content-area {
                padding: 16px;
            }

            .settings-card {
                padding: 20px;
            }

            .topbar {
                padding: 12px 16px;
            }

            .topbar h1 {
                font-size: 18px;
            }

            .profile-side-avatar {
                width: 100px;
                height: 100px;
            }

            .profile-side-banner {
                height: 80px;
            }

            .profile-side-avatar-wrap {
                margin-top: -50px;
            }
        }

        @media (max-width: 500px) {
            .profile-side-avatar {
                width: 80px;
                height: 80px;
            }

            .profile-side-banner {
                height: 60px;
            }

            .profile-side-avatar-wrap {
                margin-top: -40px;
            }

            .photo-actions {
                padding: 0 16px 16px;
            }

            .change-photo-btn,
            .delete-photo-btn {
                height: 40px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<!-- ================================================================
   SIDEBAR
   ================================================================ -->
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

<!-- ================================================================
   MAIN CONTENT
   ================================================================ -->
<main class="main-content">

    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="topbar-hamburger" onclick="document.querySelector('.sidebar').classList.toggle('sidebar-hidden')">
                <i class="fas fa-bars"></i>
            </button>
            <h1>Profile Settings</h1>
        </div>

        <div class="topbar-right">
            <a href="manage-messages.php" class="topbar-icon-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($stats['unread'] > 0): ?>
                    <span class="badge-count"><?php echo $stats['unread'] > 9 ? '9+' : $stats['unread']; ?></span>
                <?php endif; ?>
            </a>

            <div class="topbar-divider"></div>

            <div class="topbar-admin-chip" onclick="window.location.href='profile.php'">
                <div class="topbar-profile-pic">
                    <?php if (!empty($profile_pic_path)): ?>
                        <img src="<?php echo $profile_pic_path; ?>" alt="Admin" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                        <i class="fas fa-user-shield" style="display:none;"></i>
                    <?php else: ?>
                        <i class="fas fa-user-shield"></i>
                    <?php endif; ?>
                </div>
                <div class="who">
                    <h4><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></h4>
                    <span>Admin</span>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </header>

    <div class="content-area">

        <!-- ALERTS -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (!$has_pic_column): ?>
            <div class="alert alert-warning">
                <i class="fas fa-database"></i>
                Run SQL:
                <code style="background: #fef3c7; padding: 2px 6px; border-radius: 4px;">
                    ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER phone;
                </code>
            </div>
        <?php endif; ?>

        <!-- Hidden upload form -->
        <form method="POST" enctype="multipart/form-data" id="picForm" style="display: none;">
            <input type="file" id="picInput" name="profile_pic" accept="image/*" onchange="this.form.submit()">
        </form>

        <!-- PROFILE GRID -->
        <div class="profile-page-grid">

            <!-- LEFT PROFILE CARD -->
            <div class="profile-side-card">

                <div class="profile-side-banner"></div>

                <div class="profile-side-avatar-wrap">
                    <div class="profile-side-avatar" onclick="document.getElementById('picInput').click()" title="Click to change photo">
                        <?php if (!empty($profile_pic_path)): ?>
                            <img src="<?php echo $profile_pic_path; ?>" alt="Profile" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                            <div class="default-avatar" style="display:none;">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        <?php else: ?>
                            <div class="default-avatar">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-side-body">
                    <h2><?php echo htmlspecialchars($user['full_name'] ?? $_SESSION['user_name'] ?? 'Admin'); ?></h2>
                    <span class="verified-badge"><i class="fas fa-check-circle"></i> Super Admin</span>

                    <div class="profile-side-contact">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span>
                        </div>
                    </div>
                </div>

                <div class="profile-side-stats">
                    <div class="stat-box">
                        <i class="fas fa-home"></i>
                        <span class="num"><?php echo (int)$my_properties_count; ?></span>
                        <span class="lbl">Properties</span>
                    </div>
                    <div class="stat-box views">
                        <i class="fas fa-eye"></i>
                        <span class="num"><?php echo number_format((float)$total_views / 1000, 1); ?>K</span>
                        <span class="lbl">Views</span>
                    </div>
                </div>

                <div class="profile-side-completion">
                    <div class="row">
                        <span>Profile Completion</span>
                        <span>85%</span>
                    </div>
                    <div class="completion-bar">
                        <div class="fill" style="width: 85%;"></div>
                    </div>
                </div>

                <div class="photo-actions">
                    <button type="button" class="change-photo-btn" onclick="document.getElementById('picInput').click()">
                        <i class="fas fa-image"></i> Change Photo
                    </button>

                    <?php if (!empty($profile_pic_path)): ?>
                        <a href="profile.php?delete_photo=1"
                           class="delete-photo-btn"
                           onclick="return confirm('Are you sure you want to remove your profile picture?');">
                            <i class="fas fa-trash-alt"></i> Delete Photo
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="profile-right-col">

                <!-- Personal Information -->
                <div class="settings-card">
                    <div class="card-header">
                        <span class="icon"><i class="fas fa-user"></i></span>
                        <h2>Personal Information</h2>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email Address</label>
                                <div class="email-display"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i> Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? '+92 300 1234567'); ?>">
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-map-marker-alt"></i> City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? 'Lahore'); ?>">
                            </div>
                        </div>

                        <?php if ($has_bio_column): ?>
                            <div class="form-group">
                                <label><i class="fas fa-file-alt"></i> Bio (Optional)</label>
                                <textarea name="bio" rows="5" placeholder="Write a short bio or additional information about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>

                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <!-- Change Password + Account Zone -->
                <div class="bottom-settings-row">

                    <!-- Change Password -->
                    <div class="settings-card">
                        <div class="card-header">
                            <span class="icon"><i class="fas fa-lock"></i></span>
                            <h2>Change Password</h2>
                        </div>

                        <form method="POST">
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> Current Password</label>
                                <div class="password-field">
                                    <input type="password" name="current_password" placeholder="Enter current password" required>
                                    <button type="button" class="password-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> New Password</label>
                                <div class="password-field">
                                    <input type="password" name="new_password" placeholder="Enter new password" required>
                                    <button type="button" class="password-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label><i class="fas fa-check-circle"></i> Confirm Password</label>
                                <div class="password-field">
                                    <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                                    <button type="button" class="password-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                <i class="fas fa-lock"></i> Update Password
                            </button>
                        </form>
                    </div>

                    <!-- Account Zone -->
                    <div class="settings-card">
                        <div class="card-header icon-danger">
                            <span class="icon"><i class="fas fa-triangle-exclamation"></i></span>
                            <h2>Account Zone</h2>
                        </div>

                        <p class="card-desc">Manage your account actions.</p>

                        <div class="account-zone-notice">
                            <i class="fas fa-shield-halved"></i>
                            <div>
                                <h4>Delete your account permanently.</h4>
                                <p>All your data, properties and messages will be removed.</p>
                            </div>
                        </div>

                        <a href="../logout.php" class="zone-btn logout-zone-btn">
                            <i class="fas fa-right-from-bracket"></i> Logout
                        </a>

                        <button type="button"
                                class="zone-btn delete-zone-btn"
                                onclick="if(confirm('Are you sure you want to delete your account? This cannot be undone.')){ alert('Account deletion coming soon!'); }">
                            <i class="fas fa-trash"></i> Delete Account
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="page-footer">&copy; <?php echo date('Y'); ?> EstateHub. All rights reserved.</div>

    </div>
</main>

<!-- ================================================================
   JAVASCRIPT
   ================================================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-dismiss alerts after 4 seconds
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

    function togglePw(btn) {
        var input = btn.previousElementSibling;
        var icon  = btn.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

</body>
</html>