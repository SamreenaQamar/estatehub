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
    // Delete profile picture files
    $found_files = glob("uploads/profiles/seller_" . $seller_id . "_*");
    if ($found_files) {
        foreach ($found_files as $file) {
            if (file_exists($file)) unlink($file);
        }
    }
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

// Extra stats for left card
$my_properties_count = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as total FROM properties WHERE user_id = $seller_id");
if ($res && mysqli_num_rows($res) > 0) {
    $my_properties_count = mysqli_fetch_assoc($res)['total'] ?? 0;
}

$total_views = 0;
$res = mysqli_query($conn, "SHOW COLUMNS FROM properties LIKE 'views'");
if ($res && mysqli_num_rows($res) > 0) {
    $res2 = mysqli_query($conn, "SELECT SUM(views) as total FROM properties WHERE user_id = $seller_id");
    if ($res2 && mysqli_num_rows($res2) > 0) {
        $total_views = mysqli_fetch_assoc($res2)['total'] ?? 0;
    }
}

// Profile completion
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

        /* ===== SIDEBAR (seller version, unchanged) ===== */
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
            justify-content: flex-end;
            gap: 20px;
            padding: 14px 32px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top:0;
            z-index:50;
        }
        .topbar-menu-btn { display:none; background:none; border:none; cursor:pointer; padding:6px; margin-right:auto; }

        .topbar-actions { display:flex; align-items:center; gap:16px; }
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

        /* ===== PAGE HEADER ===== */
        .page-header {
            margin-bottom:28px;
        }
        .page-header h1 {
            font-size:24px;
            font-weight:800;
            color:#0b1a2e;
        }
        .page-header p {
            font-size:14px;
            color:#64748b;
            margin-top:4px;
        }

        /* ===== ALERTS ===== */
        .alert {
            padding:14px 20px;
            border-radius:14px;
            margin-bottom:20px;
            font-size:14px;
            font-weight:600;
            display:flex;
            align-items:center;
            gap:10px;
        }
        .alert-success { background:#dcfce7; color:#15803d; border:1px solid #86efac; }
        .alert-error { background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; }
        .alert-warning { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }

        /* ===== PROFILE GRID ===== */
        .profile-page-grid { display:grid; grid-template-columns:300px 1fr; gap:24px; align-items:start; }

        /* ===== LEFT PROFILE CARD ===== */
        .profile-side-card {
            background:#fff; border-radius:18px; overflow:hidden;
            border:1px solid #edf2f7; 
        }
        .profile-side-banner { height:110px; background:linear-gradient(135deg, #0E7A4E, #16a34a); position:relative; }
        .profile-side-avatar-wrap { display:flex; justify-content:center; margin-top:-65px; position:relative; }
        .profile-side-avatar {
            width:130px; height:130px; border-radius:50%; overflow:hidden; margin:auto;
            border:5px solid #fff; box-shadow:0 10px 25px rgba(0,0,0,0.15);
            background:#f5f5f5; display:flex; align-items:center; justify-content:center;
            cursor:pointer; transition:all .3s ease;
        }
        .profile-side-avatar:hover { transform:scale(1.03); }
        .profile-side-avatar img { width:100%; height:100%; object-fit:cover; }
        .default-avatar { width:100%; height:100%; display:flex; align-items:center; justify-content:center; font-size:50px; color:#0E7A4E; }

        .profile-side-body { text-align:center; padding:12px 24px 24px; }
        .profile-side-body h2 { font-size:19px; font-weight:800; color:#0b1a2e; margin-top:4px; }
        .verified-badge {
            display:inline-flex; align-items:center; gap:6px; background:#ecfdf5;
            color:#059669; padding:4px 14px; border-radius:50px; font-size:12px; font-weight:700; margin:8px 0 16px;
        }
        .profile-side-contact { display:flex; flex-direction:column; align-items:center; gap:8px; padding-top:6px; }
        .contact-item { display:flex; justify-content:center; align-items:center; gap:8px; font-size:13px; color:#475569; font-weight:500; }
        .contact-item i { color:#94a3b8; width:16px; text-align:center; }

        .profile-side-stats {
            display:grid; grid-template-columns:1fr 1fr; gap:12px;
            padding:20px 24px 12px; border-top:1px solid #edf2f7; margin-top:6px;
        }
        .stat-box { background:transparent; border-radius:14px; padding:6px 8px; text-align:center; }
        .stat-box i { color:#475569; font-size:17px; margin-bottom:8px; display:block; }
        .stat-box.views i { color:#0E7A4E; }
        .stat-box .num { font-size:19px; font-weight:800; color:#0b1a2e; display:block; }
        .stat-box .lbl { font-size:11px; color:#94a3b8; font-weight:600; }

        .profile-side-completion { padding:4px 24px 20px; }
        .profile-side-completion .row { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .profile-side-completion .row span:first-child { font-size:13px; font-weight:700; color:#0b1a2e; }
        .profile-side-completion .row span:last-child { font-size:13px; font-weight:800; color:#0E7A4E; }
        .completion-bar { height:7px; background:#edf2f7; border-radius:10px; overflow:hidden; }
        .completion-bar .fill { height:100%; background:linear-gradient(90deg, #0E7A4E, #16a34a); border-radius:10px; transition:width 0.6s ease; }

        .photo-actions { margin-top:20px; padding:0 24px 20px; display:flex; flex-direction:column; gap:10px; }

        .change-photo-btn {
            width:100%; height:45px; border-radius:12px; font-weight:700; cursor:pointer;
            background:#0E7A4E; color:#fff;
            border:none; font-family:'Inter',sans-serif; font-size:13px;
            display:flex; align-items:center; justify-content:center; gap:8px;
            transition:background 0.3s ease;
        }
        .change-photo-btn:hover {
            background:#0a5c3a;
        }

        .delete-photo-btn {
            width:100%; height:45px; border-radius:12px; font-weight:700; cursor:pointer;
            background:#fef2f2; color:#dc2626; border:1.5px solid #fecaca;
            font-family:'Inter',sans-serif; font-size:13px;
            display:flex; align-items:center; justify-content:center; gap:8px;
            transition:background 0.3s ease, border-color 0.3s ease;
            text-decoration:none;
        }
        .delete-photo-btn:hover {
            background:#fee2e2; border-color:#dc2626;
        }
        .delete-photo-btn i { color:#dc2626; }

        /* ===== RIGHT COLUMN ===== */
        .profile-right-col { display:flex; flex-direction:column; gap:24px; }
        .bottom-settings-row { display:grid; grid-template-columns:1.6fr 1fr; gap:24px; width:100%; align-items:stretch; }

        .settings-card {
            background:#fff; border:1px solid #edf2f7; border-radius:18px;
            padding:24px; box-shadow:0 3px 10px rgba(15,23,42,.03); transition:all .3s ease;
        }
        .settings-card:hover { box-shadow:0 10px 30px rgba(0,0,0,0.06); }
        .settings-card .card-header {
            display:flex; align-items:center; gap:14px; padding-bottom:16px;
            margin-bottom:20px; border-bottom:1px solid #edf2f7;
        }
        .settings-card .card-header .icon {
            width:42px; height:42px; border-radius:12px; background:#dcfce7;
            color:#0E7A4E; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;
        }
        .settings-card .card-header.icon-danger .icon { background:#fee2e2; color:#dc2626; }
        .settings-card .card-header h2 { font-size:20px; font-weight:700; color:#0b1a2e; margin:0; }
        .settings-card .card-desc { font-size:14px; color:#94a3b8; margin-bottom:20px; }

        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-size:12px; font-weight:700; color:#0b1a2e; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.3px; }
        .form-group input, .form-group textarea {
            width:100%; padding:11px 14px; border:2px solid #e9ecef;
            border-radius:12px; font-size:14px; transition:.3s;
            font-family:'Inter',sans-serif; color:#0b1a2e;
        }
        .form-group textarea { height:auto; min-height:80px; padding:12px 14px; resize:vertical; }
        .form-group input:focus, .form-group textarea:focus {
            outline:none; border-color:#0E7A4E; box-shadow:0 0 0 4px rgba(14,122,78,.10);
        }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .email-display {
            padding:11px 16px; background:#f8fafc; border:2px solid #e9ecef;
            border-radius:12px; color:#64748b; font-weight:500;
            font-size:14px; line-height:1.5; height:50px; display:flex; align-items:center;
        }

        .password-field { position:relative; }
        .password-field input { padding-right:44px; }
        .password-toggle {
            position:absolute; right:14px; top:50%; transform:translateY(-50%);
            background:none; border:none; color:#94a3b8; cursor:pointer; font-size:15px; padding:4px;
        }
        .password-toggle:hover { color:#475569; }

        /* ===== BUTTONS – fit text, green theme ===== */
        .btn {
            padding: 10px 22px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            width: auto;
        }
        .btn-primary {
            background: #0E7A4E;
            color: #fff;
        }
        .btn-primary:hover {
            background: #0a5c3a;
        }
        .btn-block {
            width: 100%;
        }

        /* ===== ACCOUNT ZONE ===== */
        .account-zone-notice {
            background:#fffbeb; border:1px solid #fde68a; border-radius:14px;
            padding:14px 16px; display:flex; gap:12px; width:100%; align-items:flex-start; margin:0 auto 24px;
        }
        .account-zone-notice i { color:#f59e0b; font-size:16px; width:20px; margin-top:2px; flex-shrink:0; }
        .account-zone-notice h4 { font-size:13px; font-weight:700; color:#92400e; margin:0 0 4px; }
        .account-zone-notice p { font-size:12px; color:#92400e; line-height:1.5; margin:0; }

        .zone-btn {
            width:100%; height:50px; margin:0 0 12px 0; border-radius:12px;
            font-size:14px; font-weight:600; border:1.5px solid #e9ecef;
            background:#fff; color:#475569; cursor:pointer;
            transition:background 0.3s ease, border-color 0.3s ease;
            display:flex; align-items:center; justify-content:center; gap:8px;
            text-decoration:none; font-family:'Inter',sans-serif;
        }
        .logout-zone-btn { color:#dc2626; border-color:#fecaca; }
        .logout-zone-btn:hover { background:#fef2f2; border-color:#dc2626; }
        .delete-zone-btn { background:#dc2626; color:#fff; border-color:#dc2626; margin-bottom:0; }
        .delete-zone-btn:hover { background:#b91c1c; border-color:#b91c1c; }

        .page-footer { text-align:center; font-size:12px; color:#94a3b8; padding:24px 0 4px; }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
            .profile-page-grid { grid-template-columns:1fr; }
        }
        @media (max-width:991px) {
            .bottom-settings-row { grid-template-columns:1fr; }
        }
        @media (max-width:768px) {
            .content-inner { padding:16px; }
            .topbar { padding:12px 16px; flex-wrap:wrap; }
            .form-row { grid-template-columns:1fr; }
            .settings-card { padding:20px; }
            .profile-side-avatar { width:100px; height:100px; }
            .profile-side-banner { height:80px; }
            .profile-side-avatar-wrap { margin-top:-50px; }
        }
        @media (max-width:500px) {
            .profile-side-avatar { width:80px; height:80px; }
            .profile-side-banner { height:60px; }
            .profile-side-avatar-wrap { margin-top:-40px; }
            .photo-actions { padding:0 16px 16px; }
            .change-photo-btn, .delete-photo-btn { height:40px; font-size:12px; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- ===== SIDEBAR (seller version, unchanged) ===== -->
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

        <!-- TOP BAR -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('sellerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>

            <div class="topbar-actions">
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
            <div class="page-header">
                <h1>Profile Settings</h1>
                <p>Manage your personal information and account settings</p>
            </div>

            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType === 'error' ? 'error' : 'success'; ?>">
                    <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (!$has_pic_column): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-database"></i>
                    Run SQL: <code style="background:#fef3c7; padding:2px 6px; border-radius:4px; font-size:12px;">ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER phone;</code>
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
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php else: ?>
                                <div class="default-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="profile-side-body">
                        <h2><?php echo htmlspecialchars($user['full_name'] ?? $user_name); ?></h2>
                        <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified Seller</span>

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
                            <span><?php echo $completion; ?>%</span>
                        </div>
                        <div class="completion-bar">
                            <div class="fill" style="width: <?php echo $completion; ?>%;"></div>
                        </div>
                    </div>

                    <div class="photo-actions">
                        <button type="button" class="change-photo-btn" onclick="document.getElementById('picInput').click()">
                            <i class="fas fa-image"></i> Change Photo
                        </button>

                        <?php if (!empty($profile_pic_path)): ?>
                            <a href="profile-settings.php?delete_photo=1"
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

                        <form method="POST">
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
                                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>

                                <?php if ($has_city_column): ?>
                                    <div class="form-group">
                                        <label><i class="fas fa-map-marker-alt"></i> City</label>
                                        <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($has_bio_column): ?>
                                <div class="form-group">
                                    <label><i class="fas fa-file-alt"></i> Bio (Optional)</label>
                                    <textarea name="bio" rows="4" placeholder="Write a short bio..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
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

                                <button type="submit" name="change_password" class="btn btn-primary">
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

                            <a href="logout.php" class="zone-btn logout-zone-btn">
                                <i class="fas fa-right-from-bracket"></i> Logout
                            </a>

                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone!');">
                                <button type="submit" name="delete_account" class="zone-btn delete-zone-btn">
                                    <i class="fas fa-trash"></i> Delete Account
                                </button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

            <div class="page-footer">&copy; <?php echo date('Y'); ?> EstateHub. All rights reserved.</div>

        </div>
    </div>
</div>

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

function togglePw(btn) {
    var input = btn.previousElementSibling;
    var icon = btn.querySelector('i');

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