<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'seller') {
    header("Location: login.php");
    exit();
}

$message        = '';
$messageType    = '';
$seller_id      = $_SESSION['user_id'];

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $seller_id");
$user       = mysqli_fetch_assoc($user_query);

// Check for optional columns
$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'profile_pic'");
$has_pic_column = mysqli_num_rows($result) > 0;

$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'bio'");
$has_bio_column = mysqli_num_rows($result) > 0;

$result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'city'");
$has_city_column = mysqli_num_rows($result) > 0;

// ----------------------------------------------------------------------
// UPLOAD PROFILE PICTURE
// ----------------------------------------------------------------------
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0 && $_FILES['profile_pic']['size'] > 0) {
    $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename  = $_FILES['profile_pic']['name'];
    $ext       = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (in_array($ext, $allowed)) {
        $upload_dir   = "uploads/profiles/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $new_filename = 'seller_' . $seller_id . '_' . time() . '.' . $ext;
        $target_file  = $upload_dir . $new_filename;

        $old_files = glob($upload_dir . 'seller_' . $seller_id . '_*');
        if ($old_files) {
            foreach ($old_files as $old_file) {
                if (file_exists($old_file)) unlink($old_file);
            }
        }

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            if ($has_pic_column) {
                $pic_path = mysqli_real_escape_string($conn, $new_filename);
                mysqli_query($conn, "UPDATE users SET profile_pic = '$pic_path' WHERE id = $seller_id");
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
// DELETE PROFILE PICTURE
// ----------------------------------------------------------------------
if (isset($_GET['delete_photo']) && $_GET['delete_photo'] == 1) {
    $has_pic        = false;
    $pic_to_delete  = '';

    if ($has_pic_column && !empty($user['profile_pic'])) {
        $pic_to_delete = $user['profile_pic'];
        $has_pic       = true;
    }

    if (!$has_pic && isset($_SESSION['profile_pic'])) {
        $pic_to_delete = $_SESSION['profile_pic'];
        $has_pic       = true;
    }

    if (!$has_pic) {
        $found_files = glob("uploads/profiles/seller_" . $seller_id . "_*");
        if (!empty($found_files)) {
            $pic_to_delete = basename($found_files[0]);
            $has_pic       = true;
        }
    }

    if ($has_pic && !empty($pic_to_delete)) {
        $pic_file = "uploads/profiles/" . $pic_to_delete;
        if (file_exists($pic_file)) {
            unlink($pic_file);
        }

        if ($has_pic_column) {
            mysqli_query($conn, "UPDATE users SET profile_pic = NULL WHERE id = $seller_id");
        }
        $_SESSION['profile_pic'] = NULL;

        $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $seller_id");
        $user       = mysqli_fetch_assoc($user_query);

        header("Location: profile-settings.php?deleted=1");
        exit();
    } else {
        header("Location: profile-settings.php?error=nopic");
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
        $pic_file = "uploads/profiles/" . $user['profile_pic'];
        if (file_exists($pic_file)) $has_pic = true;
    }

    if (!$has_pic && isset($_SESSION['profile_pic'])) {
        $pic_file = "uploads/profiles/" . $_SESSION['profile_pic'];
        if (file_exists($pic_file)) $has_pic = true;
    }

    if (!$has_pic) {
        $found_files = glob("uploads/profiles/seller_" . $seller_id . "_*");
        if (!empty($found_files)) $has_pic = true;
    }

    if (!$has_pic) {
        $message     = "Profile picture is required. Please upload a photo before saving.";
        $messageType = "error";
    } else {
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $phone     = mysqli_real_escape_string($conn, trim($_POST['phone']));
        $city      = $has_city_column ? mysqli_real_escape_string($conn, trim($_POST['city'] ?? '')) : '';
        $bio_text  = $has_bio_column ? mysqli_real_escape_string($conn, trim($_POST['bio'] ?? '')) : '';

        $sql = "UPDATE users SET full_name = '$full_name', phone = '$phone'";
        if ($has_city_column) $sql .= ", city = '$city'";
        if ($has_bio_column) $sql .= ", bio = '$bio_text'";
        $sql .= " WHERE id = '$seller_id'";

        if (mysqli_query($conn, $sql)) {
            $_SESSION['user_name'] = $full_name;
            $message     = "Profile updated successfully.";
            $messageType = "success";

            $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id='$seller_id'");
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
            mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $seller_id");
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
// HANDLE ACCOUNT DELETION
// ----------------------------------------------------------------------
if (isset($_POST['delete_account'])) {
    mysqli_query($conn, "DELETE FROM users WHERE id = $seller_id");
    session_destroy();
    header("Location: index.php");
    exit();
}

// ----------------------------------------------------------------------
// GET PROFILE PICTURE PATH
// ----------------------------------------------------------------------
$profile_pic_path = '';

if ($has_pic_column && !empty($user['profile_pic'])) {
    $pic_file = "uploads/profiles/" . $user['profile_pic'];
    if (file_exists($pic_file)) $profile_pic_path = $pic_file . '?t=' . time();
}

if (empty($profile_pic_path) && isset($_SESSION['profile_pic'])) {
    $pic_file = "uploads/profiles/" . $_SESSION['profile_pic'];
    if (file_exists($pic_file)) $profile_pic_path = $pic_file . '?t=' . time();
}

if (empty($profile_pic_path)) {
    $found_files = glob("uploads/profiles/seller_" . $seller_id . "_*");
    if (!empty($found_files)) {
        $profile_pic_path = $found_files[0] . '?t=' . time();
        $_SESSION['profile_pic'] = basename($found_files[0]);

        if ($has_pic_column) {
            $safe_filename = mysqli_real_escape_string($conn, basename($found_files[0]));
            mysqli_query($conn, "UPDATE users SET profile_pic = '$safe_filename' WHERE id = $seller_id");
        }
    }
}

// ----------------------------------------------------------------------
// SIDEBAR STATS (for unread messages badge)
// ----------------------------------------------------------------------
$total_messages = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE receiver_id = $seller_id AND is_read = 0");
if ($result && mysqli_num_rows($result) > 0) {
    $total_messages = mysqli_fetch_assoc($result)['total'] ?? 0;
}

// For profile completion
$fields = ['full_name', 'email', 'phone', 'bio'];
if ($has_city_column) $fields[] = 'city';
$filled = 0;
foreach ($fields as $f) {
    if (!empty($user[$f])) $filled++;
}
$completion = round(($filled / count($fields)) * 100);

$page_title = 'Profile Settings';
$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['user_name'] ?? 'Seller';
$avatar_path = $profile_pic_path; // use for topbar avatar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== RESET & BASE ===== */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        /* ===== SIDEBAR (exactly like seller dashboard) ===== */
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

        .sidebar-menu a svg {
            width:20px;
            height:20px;
            transition:.3s;
        }

        .sidebar-menu a:hover svg {
            transform:scale(1.15);
        }

        .sidebar-menu a.active {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            box-shadow:0 8px 20px rgba(14,122,78,.35);
        }

        .sidebar-menu a.active svg {
            transform:scale(1.1);
        }

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

        .logout-link {
            color:#b8c2cc !important;
        }
        .logout-link:hover {
            background:linear-gradient(135deg,#dc2626,#ef4444) !important;
            color:#fff !important;
            box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
        }

        /* ===== TOP BAR ===== */
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
        .topbar-search { flex:1; max-width:500px; position:relative; }
        .topbar-search input {
            width:100%;
            padding:9px 40px 9px 16px;
            border-radius:10px;
            border:1px solid #e9ecef;
            background:#f8fafc;
            font-size:14px;
            outline:none;
            transition:0.2s;
        }
        .topbar-search input:focus { border-color:#0E7A4E; background:#fff; }
        .topbar-search svg { position:absolute; right:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#adb5bd; }

        .topbar-actions { display:flex; align-items:center; gap:16px; }
        .icon-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f8fafc;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition:0.2s;
            color:#1e293b;
        }
        .icon-btn:hover { background:#e9ecef; }
        .icon-btn svg { width:20px; height:20px; stroke:currentColor; fill:none; stroke-width:2; }
        .icon-btn .count {
            position:absolute; top:-4px; right:-4px;
            background:#ef4444; color:#fff;
            font-size:10px; font-weight:700;
            min-width:18px; height:18px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            border:2px solid #fff;
        }
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

        /* ===== MAIN CONTENT ===== */
        .main-content { flex:1; overflow-y:auto; }
        .content-inner { padding:28px 32px 40px; }

        /* ===== PROFILE CONTENT ===== */
        .profile-header {
            margin-bottom:28px;
        }
        .profile-header h1 {
            font-size:24px;
            font-weight:800;
            color:#0b1a2e;
        }
        .profile-header p {
            color:#64748b;
            font-size:14px;
            margin-top:4px;
        }

        .profile-grid{
    display:grid;
    grid-template-columns:300px 1fr;
    gap:24px;
    align-items:start;
}
.profile-sidebar,
.profile-card,
.profile-side-card{
    height:fit-content;
    align-self:start;
}

/* Left Card */
        .profile-card {
            background:#fff;
            border-radius:20px;
            padding:24px;
            border:1px solid #edf2f7;
            text-align:center;
        }
        .profile-avatar-wrapper {
            position:relative;
            width:120px;
            height:120px;
            margin:0 auto;
        }
        .profile-avatar {
            width:120px;
            height:120px;
            border-radius:50%;
            object-fit:cover;
            border:4px solid #fff;
            box-shadow:0 4px 20px rgba(14,122,78,0.15);
        }
        .avatar-upload-overlay {
            position:absolute;
            bottom:0;
            right:0;
            background:#0E7A4E;
            color:#fff;
            width:34px;
            height:34px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            border:3px solid #fff;
            transition:0.3s;
        }
        .avatar-upload-overlay:hover { background:#0a5c3a; transform:scale(1.05); }
        .dp-form input { display:none; }

        .profile-name {
            font-size:20px;
            font-weight:800;
            margin-top:12px;
        }
        .profile-role {
            display:inline-block;
            background:#ecfdf5;
            color:#0E7A4E;
            font-size:12px;
            font-weight:700;
            padding:3px 14px;
            border-radius:20px;
            margin-top:4px;
        }
        .profile-email {
            font-size:13px;
            color:#64748b;
            margin-top:6px;
        }
        .profile-email i { margin-right:4px; color:#0E7A4E; }

        .completion-section {
            margin-top:16px;
            padding-top:14px;
            border-top:1px solid #edf2f7;
        }
        .completion-label {
            display:flex;
            justify-content:space-between;
            font-size:12px;
            font-weight:600;
            color:#64748b;
            margin-bottom:4px;
        }
        .completion-bar {
            height:5px;
            background:#edf2f7;
            border-radius:10px;
            overflow:hidden;
        }
        .completion-bar .fill {
            height:100%;
            background:linear-gradient(90deg,#0E7A4E,#16a34a);
            border-radius:10px;
            transition:width 0.6s ease;
        }

        .photo-actions {
            margin-top:20px;
            display:flex;
            flex-direction:column;
            gap:10px;
        }
        .btn-change {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            border:none;
            padding:10px;
            border-radius:10px;
            font-weight:700;
            cursor:pointer;
            transition:0.3s;
        }
        .btn-change:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(14,122,78,0.3); }
        .btn-delete {
            background:#fef2f2;
            color:#dc2626;
            border:1.5px solid #fecaca;
            padding:10px;
            border-radius:10px;
            font-weight:700;
            cursor:pointer;
            transition:0.3s;
            text-decoration:none;
            display:block;
            text-align:center;
        }
        .btn-delete:hover { background:#fee2e2; border-color:#dc2626; transform:translateY(-2px); }

        /* Right Column */
        .settings-card {
            background:#fff;
            border-radius:20px;
            padding:28px;
            border:1px solid #edf2f7;
            margin-bottom:24px;
        }
        .settings-card:last-child { margin-bottom:0; }
        .settings-card .card-header {
            display:flex;
            align-items:center;
            gap:12px;
            padding-bottom:14px;
            margin-bottom:20px;
            border-bottom:2px solid #f1f5f9;
        }
        .settings-card .card-header .icon-box {
            width:36px;
            height:36px;
            border-radius:10px;
            background:#ecfdf5;
            display:flex;
            align-items:center;
            justify-content:center;
            color:#0E7A4E;
            font-size:16px;
        }
        .settings-card .card-header h2 {
            font-size:16px;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }

        .form-group {
            margin-bottom:16px;
        }
        .form-group label {
            display:block;
            font-size:12px;
            font-weight:700;
            color:#0b1a2e;
            text-transform:uppercase;
            letter-spacing:0.3px;
            margin-bottom:4px;
        }
        .form-group input,
        .form-group textarea {
            width:100%;
            padding:10px 14px;
            border:2px solid #e9ecef;
            border-radius:10px;
            font-size:14px;
            transition:0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline:none;
            border-color:#0E7A4E;
            box-shadow:0 0 0 3px rgba(14,122,78,0.08);
        }
        .form-group input:disabled {
            background:#f1f5f9;
            cursor:not-allowed;
        }
        .form-row {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
        }

        .btn {
            padding:10px 22px;
            border:none;
            border-radius:10px;
            font-size:13px;
            font-weight:700;
            cursor:pointer;
            transition:0.3s;
            display:inline-flex;
            align-items:center;
            gap:8px;
            text-transform:uppercase;
            letter-spacing:0.5px;
        }
        .btn-primary {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            box-shadow:0 3px 12px rgba(14,122,78,0.2);
        }
        .btn-primary:hover {
            transform:translateY(-2px);
            box-shadow:0 6px 20px rgba(14,122,78,0.3);
        }
        .btn-danger {
            background:#ef4444;
            color:#fff;
        }
        .btn-danger:hover { background:#dc2626; transform:translateY(-2px); }
        .btn-block { width:100%; justify-content:center; }

        .alert {
            padding:10px 16px;
            border-radius:10px;
            margin-bottom:16px;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:10px;
            font-size:13px;
        }
        .alert-success { background:#dcfce7; color:#166534; border-left:3px solid #22c55e; }
        .alert-error { background:#fee2e2; color:#991b1b; border-left:3px solid #ef4444; }

        .password-field {
            position:relative;
        }
        .password-field input {
            padding-right:44px;
        }
        .password-toggle {
            position:absolute;
            right:14px;
            top:50%;
            transform:translateY(-50%);
            background:none;
            border:none;
            color:#94a3b8;
            cursor:pointer;
            font-size:16px;
        }
        .password-toggle:hover { color:#0b1a2e; }

        .account-zone-notice {
            background:#fffbeb;
            border:1px solid #fde68a;
            border-radius:12px;
            padding:14px 16px;
            display:flex;
            gap:12px;
            margin-bottom:20px;
        }
        .account-zone-notice i {
            color:#f59e0b;
            font-size:16px;
            margin-top:2px;
        }
        .account-zone-notice h4 {
            font-size:13px;
            font-weight:700;
            color:#92400e;
        }
        .account-zone-notice p {
            font-size:12px;
            color:#92400e;
            line-height:1.5;
        }

        .zone-btn {
            width:100%;
            padding:12px;
            border-radius:10px;
            font-size:14px;
            font-weight:700;
            border:1.5px solid #e9ecef;
            background:#fff;
            color:#475569;
            cursor:pointer;
            transition:0.3s;
            display:flex;
            align-items:center;
            justify-content:center;
            gap:4px;
            text-decoration:none;
        }
        .zone-btn:hover { transform:translateY(-2px); }
        .logout-zone-btn {
            color:#ef4444;
            border-color:#fecaca;
            margin-bottom:9px;
        }
        .logout-zone-btn:hover { background:#fef2f2; border-color:#ef4444; }
        .delete-zone-btn {
            background:#ef4444;
            color:#fff;
            border-color:#ef4444;
        }
        .delete-zone-btn:hover { background:#dc2626; border-color:#dc2626; }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
        }
        @media (max-width:768px) {
            .profile-grid { grid-template-columns:1fr; }
            .content-inner { padding:18px; }
            .topbar { padding:12px 18px; }
            .form-row { grid-template-columns:1fr; }
            .user-info { display:none; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR (exactly like seller dashboard) ===== -->
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
                <a href="add-property.php" class="<?php echo ($current_page == 'add-property.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Add Property
                </a>
            </li>
            <li>
                <a href="messages.php" class="<?php echo ($current_page == 'messages.php') ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Messages
                    <?php if($total_messages > 0): ?><span class="badge"><?php echo $total_messages; ?></span><?php endif; ?>
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
                <a href="profile-settings.php" class="active">
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

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">

        <!-- TOP BAR (same as dashboard) -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('sellerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-search">
                <input type="text" placeholder="Search anything...">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            <div class="topbar-actions">
                <button class="icon-btn" title="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if($total_messages > 0): ?>
                        <span class="count"><?php echo $total_messages > 9 ? '9+' : $total_messages; ?></span>
                    <?php endif; ?>
                </button>
                <a href="profile-settings.php" class="user-chip">
                    <div class="user-avatar">
                        <?php if(!empty($profile_pic_path)): ?>
                            <img src="<?php echo $profile_pic_path; ?>" alt="<?php echo htmlspecialchars($user_name); ?>">
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

            <!-- Page Header -->
            <div class="profile-header">
                <h1>Profile Settings</h1>
                <p>Update your personal information and account details</p>
            </div>

            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'error' ? 'alert-error' : 'alert-success'; ?>">
                    <i class="fas <?php echo $messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!$has_pic_column): ?>
                <div class="alert alert-error">
                    <i class="fas fa-database"></i>
                    Run SQL: <code>ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER phone;</code>
                </div>
            <?php endif; ?>

            <!-- Hidden upload form -->
            <form method="POST" enctype="multipart/form-data" id="picForm" style="display: none;">
                <input type="file" id="picInput" name="profile_pic" accept="image/*" onchange="this.form.submit()">
            </form>

            <!-- Profile Grid -->
            <div class="profile-grid">

                <!-- LEFT CARD -->
                <div class="profile-card">
                    <div class="profile-avatar-wrapper">
                        <img src="<?php echo !empty($profile_pic_path) ? $profile_pic_path : 'https://ui-avatars.com/api/?background=0E7A4E&color=fff&size=120&name=' . urlencode($user_name); ?>" alt="Profile" class="profile-avatar" id="profilePreview">
                        <label for="picInput" class="avatar-upload-overlay" title="Change Photo">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?? $user_name); ?></div>
                    <div class="profile-role">Verified Seller</div>
                    <div class="profile-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?? ''); ?></div>

                    <div class="completion-section">
                        <div class="completion-label">
                            <span>Profile Completion</span>
                            <span><?php echo $completion; ?>%</span>
                        </div>
                        <div class="completion-bar">
                            <div class="fill" style="width: <?php echo $completion; ?>%;"></div>
                        </div>
                    </div>

                    <div class="photo-actions">
                        <button type="button" class="btn-change" onclick="document.getElementById('picInput').click()">
                            <i class="fas fa-image"></i> Change Photo
                        </button>
                        <?php if (!empty($profile_pic_path)): ?>
                            <a href="profile-settings.php?delete_photo=1" class="btn-delete" onclick="return confirm('Are you sure you want to remove your profile picture?');">
                                <i class="fas fa-trash-alt"></i> Delete Photo
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>

                    <!-- Personal Information -->
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="icon-box"><i class="fas fa-user"></i></div>
                            <h2>Personal Information</h2>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Phone Number</label>
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+92 300 1234567">
                                </div>
                                <?php if ($has_city_column): ?>
                                    <div class="form-group">
                                        <label><i class="fas fa-map-marker-alt"></i> City</label>
                                        <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="e.g. Lahore">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($has_bio_column): ?>
                                <div class="form-group">
                                    <label><i class="fas fa-file-alt"></i> Bio</label>
                                    <textarea name="bio" rows="3" placeholder="Write a short bio..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                </div>
                            <?php endif; ?>
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Change Password + Account Zone in two columns -->
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                        <!-- Change Password -->
                        <div class="settings-card" style="margin-bottom:0;">
                            <div class="card-header">
                                <div class="icon-box"><i class="fas fa-lock"></i></div>
                                <h2>Change Password</h2>
                            </div>
                            <form method="POST">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <div class="password-field">
                                        <input type="password" name="current_password" placeholder="Enter current password" required>
                                        <button type="button" class="password-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <div class="password-field">
                                        <input type="password" name="new_password" placeholder="Min 6 characters" required>
                                        <button type="button" class="password-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <div class="password-field">
                                        <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                                        <button type="button" class="password-toggle" onclick="togglePw(this)"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                    <i class="fas fa-sync-alt"></i> Update Password
                                </button>
                            </form>
                        </div>

                        <!-- Account Zone -->
                        <div class="settings-card" style="margin-bottom:0;">
                            <div class="card-header">
                                <div class="icon-box" style="background:#fef2f2; color:#ef4444;"><i class="fas fa-exclamation-triangle"></i></div>
                                <h2 style="color:#991b1b;">Account Zone</h2>
                            </div>

                            <div class="account-zone-notice">
                                <i class="fas fa-shield-alt"></i>
                                <div>
                                    <h4>Delete your account permanently.</h4>
                                    <p>All your data, properties and messages will be removed.</p>
                                </div>
                            </div>

                            <a href="logout.php" class="zone-btn logout-zone-btn">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>

                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone!');">
                                <button type="submit" name="delete_account" class="zone-btn delete-zone-btn">
                                    <i class="fas fa-trash"></i> Delete Account
                                </button>
                            </form>
                        </div>

                    </div><!-- end password+account grid -->

                </div><!-- right column -->
            </div><!-- profile-grid -->

        </div><!-- content-inner -->
    </div><!-- main-content -->
</div><!-- dashboard-wrapper -->

<script>
    // Toggle password visibility
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

    // Auto-dismiss alerts
    document.addEventListener('DOMContentLoaded', function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    if (alert.parentNode) alert.remove();
                }, 500);
            }, 4000);
        });
    });
</script>

</body>
</html>