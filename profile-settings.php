<?php
$page_title = 'Profile Settings';
include 'includes/config.php';
include 'includes/header.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

$success = '';
$error = '';

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = isset($_POST['full_name']) ? mysqli_real_escape_string($conn, trim($_POST['full_name'])) : '';
    $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, trim($_POST['phone'])) : '';
    $bio = isset($_POST['bio']) ? mysqli_real_escape_string($conn, trim($_POST['bio'])) : '';
    
    if(!empty($full_name)) {
        mysqli_query($conn, "UPDATE users SET full_name='$full_name', phone='$phone', bio='$bio' WHERE id=$user_id");
        $_SESSION['user_name'] = $full_name;
        $success = "Profile updated successfully!";
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
        $user['bio'] = $bio;
    } else {
        $error = "Full name is required!";
    }
}

// Handle profile picture upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_dp'])) {
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $upload_dir = 'uploads/profiles/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_dir . $new_filename)) {
                if(!empty($user['profile_image']) && $user['profile_image'] != 'default-avatar.png') {
                    @unlink($upload_dir . $user['profile_image']);
                }
                mysqli_query($conn, "UPDATE users SET profile_image='$new_filename' WHERE id=$user_id");
                $success = "Profile picture updated successfully!";
                $user['profile_image'] = $new_filename;
            } else {
                $error = "Failed to upload image. Please try again.";
            }
        } else {
            $error = "Only JPG, PNG, GIF, and WEBP files are allowed!";
        }
    } else {
        $error = "Please select an image file.";
    }
}

// Handle password change
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if(password_verify($current, $user['password'])) {
        if($new == $confirm && strlen($new) >= 6) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id");
            $success = "Password changed successfully! Please login again.";
            session_destroy();
            header("Location: login.php");
            exit();
        } else {
            $error = "New password doesn't match or is too short (min 6 characters)!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}

// Handle delete account
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_account'])) {
    mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
    session_destroy();
    header("Location: index.php");
    exit();
}

$profile_image = !empty($user['profile_image']) && $user['profile_image'] != 'default-avatar.png' 
    ? 'uploads/profiles/' . $user['profile_image'] 
    : 'https://ui-avatars.com/api/?background=0E7A4E&color=fff&size=200&name=' . urlencode($user['full_name']);

// Calculate profile completion
$fields = ['full_name', 'email', 'phone', 'bio'];
$filled = 0;
foreach($fields as $field) {
    if(!empty($user[$field])) $filled++;
}
$completion = round(($filled / count($fields)) * 100);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        :root {
            --primary: #0E7A4E;
            --primary-dark: #09663f;
            --primary-light: #EAF8F1;
            --primary-gradient: linear-gradient(135deg, #0E7A4E, #16A34A);
            --white: #ffffff;
            --bg: #F0F4F8;
            --border: #E5E7EB;
            --text: #0B1A2E;
            --text-light: #64748B;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 16px 48px rgba(0, 0, 0, 0.08);
            --radius: 20px;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            background: var(--bg);
            color: var(--text);
        }

        .profile-section {
            height: calc(100vh - 70px);
            padding: 28px 32px 24px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 22px;
            flex-shrink: 0;
        }
        .page-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .page-header h1 i {
            color: var(--primary);
            margin-right: 10px;
        }
        .page-header p {
            color: var(--text-light);
            font-weight: 500;
            font-size: 14px;
            margin-top: 2px;
            letter-spacing: 0.3px;
        }

        /* Main Grid - 3 columns */
        .profile-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr 300px;
            gap: 22px;
            flex: 1;
            min-height: 0;
            height: 100%;
        }

        /* ============================================================ */
        /* COLUMN 1: PROFILE CARD + ACCOUNT ZONE */
        /* ============================================================ */
        .profile-col {
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .profile-col::-webkit-scrollbar {
            width: 3px;
        }
        .profile-col::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }

        .profile-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 30px 24px 24px;
            text-align: center;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .profile-avatar-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--white);
            box-shadow: 0 4px 20px rgba(14, 122, 78, 0.15);
            transition: all 0.3s ease;
            display: block;
        }
        .profile-avatar:hover {
            transform: scale(1.02);
        }

        .avatar-upload-overlay {
            position: absolute;
            bottom: 0px;
            right: 0px;
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid var(--white);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(14, 122, 78, 0.3);
        }
        .avatar-upload-overlay:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }
        .avatar-upload-overlay i {
            font-size: 12px;
        }

        .profile-name {
            margin-top: 12px;
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -0.3px;
        }

        .profile-role {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 11px;
            font-weight: 700;
            padding: 3px 14px;
            border-radius: 20px;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .profile-email {
            margin-top: 6px;
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
            word-break: break-all;
        }
        .profile-email i {
            margin-right: 4px;
            color: var(--primary);
        }

        .dp-form input {
            display: none;
        }

        /* Completion Bar */
        .completion-section {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }
        .completion-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 4px;
        }
        .completion-bar {
            width: 100%;
            height: 5px;
            background: var(--border);
            border-radius: 10px;
            overflow: hidden;
        }
        .completion-bar .fill {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        /* ============================================================ */
        /* ACCOUNT ZONE - Now in Left Column */
        /* ============================================================ */
        .account-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 22px 22px 24px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .account-card .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-bottom: 2px solid #FEE2E2;
        }
        .account-card .card-header .icon-box {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #FEE2E2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #DC2626;
            font-size: 16px;
        }
        .account-card .card-header h2 {
            font-size: 14px;
            font-weight: 700;
            color: #991B1B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .danger-card {
            background: #FEF2F2;
            border: 2px solid #FEE2E2;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .danger-card .info h4 {
            font-size: 13px;
            font-weight: 700;
            color: #991B1B;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .danger-card .info p {
            font-size: 12px;
            color: #7F1D1D;
            font-weight: 500;
        }
        .danger-card .info p i {
            margin-right: 4px;
        }

        /* ============================================================ */
        /* COLUMN 2: PERSONAL INFORMATION */
        /* ============================================================ */
        .info-col {
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .info-col::-webkit-scrollbar {
            width: 3px;
        }
        .info-col::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }

        .settings-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px 28px 26px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .settings-card .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 14px;
            margin-bottom: 18px;
            border-bottom: 2px solid #F1F5F9;
        }
        .settings-card .card-header .icon-box {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 16px;
        }
        .settings-card .card-header h2 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .form-group label i {
            color: var(--text-light);
            margin-right: 4px;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 10px;
            border: 2px solid #EEF2F6;
            transition: all 0.25s ease;
            background: #FAFBFC;
            color: var(--text);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
            font-weight: 400;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            background: var(--white);
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14, 122, 78, 0.08);
        }
        .form-group input:disabled {
            background: #F1F5F9;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 3px 12px rgba(14, 122, 78, 0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 122, 78, 0.3);
        }
        .btn-danger {
            background: #EF4444;
            color: white;
        }
        .btn-danger:hover {
            background: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
        }
        .btn-sm {
            padding: 8px 18px;
            font-size: 12px;
        }
        .btn-block {
            width: 100%;
            justify-content: center;
        }

        /* Alerts */
        .alert {
            padding: 10px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        .alert-success {
            background: #DCFCE7;
            color: #166534;
            border-left: 3px solid #22C55E;
        }
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 3px solid #EF4444;
        }
        .alert i {
            font-size: 16px;
        }

        /* ============================================================ */
        /* COLUMN 3: UPDATE PASSWORD ONLY */
        /* ============================================================ */
        .right-col {
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            padding-left: 4px;
        }
        .right-col::-webkit-scrollbar {
            width: 3px;
        }
        .right-col::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }

        .right-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 22px 22px 24px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }

        .right-card .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 12px;
            margin-bottom: 16px;
            border-bottom: 2px solid #F1F5F9;
        }
        .right-card .card-header .icon-box {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 16px;
        }
        .right-card .card-header h2 {
            font-size: 14px;
            font-weight: 700;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .right-card .form-group {
            margin-bottom: 12px;
        }
        .right-card .form-group:last-child {
            margin-bottom: 0;
        }
        .right-card .form-group input {
            font-size: 13px;
            padding: 9px 14px;
        }

        /* Footer */
        .profile-footer {
            text-align: center;
            padding-top: 8px;
            color: var(--text-light);
            font-size: 12px;
            font-weight: 500;
            flex-shrink: 0;
        }

        /* ============================================================ */
        /* RESPONSIVE */
        /* ============================================================ */
        @media (max-width: 1200px) {
            .profile-wrapper {
                grid-template-columns: 240px 1fr 260px;
                gap: 18px;
            }
        }

        @media (max-width: 992px) {
            .profile-wrapper {
                grid-template-columns: 1fr 1fr;
                grid-template-rows: auto auto;
                overflow-y: auto;
                height: auto;
            }
            .profile-col {
                grid-column: 1;
                grid-row: 1;
                overflow-y: visible;
                padding-right: 0;
            }
            .info-col {
                grid-column: 2;
                grid-row: 1;
                overflow-y: visible;
                padding-right: 0;
            }
            .right-col {
                grid-column: 1 / -1;
                grid-row: 2;
                flex-direction: row;
                flex-wrap: wrap;
                overflow-y: visible;
                padding-left: 0;
            }
            .right-col .right-card {
                flex: 1;
                min-width: 200px;
            }
            .profile-section {
                height: auto;
                overflow-y: auto;
                padding: 16px;
            }
            html, body {
                overflow: auto;
            }
        }

        @media (max-width: 700px) {
            .profile-wrapper {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto;
            }
            .profile-col {
                grid-column: 1;
                grid-row: 1;
            }
            .info-col {
                grid-column: 1;
                grid-row: 2;
            }
            .right-col {
                grid-column: 1;
                grid-row: 3;
                flex-direction: column;
            }
            .right-col .right-card {
                flex: none;
            }
            .profile-avatar-wrapper {
                width: 80px;
                height: 80px;
            }
            .profile-avatar {
                width: 80px;
                height: 80px;
            }
            .settings-card {
                padding: 18px 16px 20px;
            }
            .profile-card {
                padding: 20px 16px 18px;
            }
            .account-card {
                padding: 18px 16px 20px;
            }
            .right-card {
                padding: 18px 16px 20px;
            }
            .danger-card {
                flex-direction: column;
                text-align: center;
            }
            .page-header h1 {
                font-size: 20px;
            }
            .profile-section {
                padding: 12px;
            }
        }
    </style>
</head>
<body>

<section class="profile-section">
    <div class="container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-user-cog"></i> PROFILE SETTINGS</h1>
        </div>

        <div class="profile-wrapper">
            
            <!-- ============================================================ -->
            <!-- COLUMN 1: PROFILE CARD + ACCOUNT ZONE -->
            <!-- ============================================================ -->
            <div class="profile-col">
                <!-- Profile Card -->
                <div class="profile-card">
                    <div class="profile-avatar-wrapper">
                        <img src="<?php echo $profile_image; ?>" alt="Profile" class="profile-avatar" id="profilePreview">
                        <form method="POST" enctype="multipart/form-data" class="dp-form">
                            <label for="profile_image" class="avatar-upload-overlay" title="Change Photo">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" name="profile_image" id="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" onchange="this.form.submit()">
                            <input type="hidden" name="upload_dp" value="1">
                        </form>
                    </div>
                    
                    <h3 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <span class="profile-role">Verified Member</span>
                    <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    
                    <div class="completion-section">
                        <div class="completion-label">
                            <span>Profile Completion</span>
                            <span><?php echo $completion; ?>%</span>
                        </div>
                        <div class="completion-bar">
                            <div class="fill" style="width: <?php echo $completion; ?>%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Account Zone - Now Below Profile Card -->
                <div class="account-card">
                    <div class="card-header">
                        <div class="icon-box"><i class="fas fa-shield-alt"></i></div>
                        <h2>Account Zone</h2>
                    </div>
                    
                    <div class="danger-card">
                        <div class="info">
                            <h4><i class="fas fa-exclamation-triangle"></i> Delete Account</h4>
                            <p><i class="fas fa-info-circle"></i> All your data, properties and messages will be permanently removed.</p>
                        </div>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone!');">
                            <button type="submit" name="delete_account" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Delete Account
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- ============================================================ -->
            <!-- COLUMN 2: PERSONAL INFORMATION -->
            <!-- ============================================================ -->
            <div class="info-col">
                <div class="settings-card">
                    <div class="card-header">
                        <div class="icon-box"><i class="fas fa-user"></i></div>
                        <h2>Personal Information</h2>
                    </div>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+92 300 1234567">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-info-circle"></i> Bio / About</label>
                            <input type="text" name="bio" value="<?php echo htmlspecialchars($user['bio'] ?? ''); ?>" placeholder="Real estate agent...">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary btn-sm">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- ============================================================ -->
            <!-- COLUMN 3: UPDATE PASSWORD ONLY -->
            <!-- ============================================================ -->
            <div class="right-col">
                
                <!-- Change Password -->
                <div class="right-card">
                    <div class="card-header">
                        <div class="icon-box"><i class="fas fa-lock"></i></div>
                        <h2>Update Password</h2>
                    </div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label><i class="fas fa-key"></i> Current Password</label>
                            <input type="password" name="current_password" placeholder="Enter current password" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> New Password</label>
                            <input type="password" name="new_password" placeholder="Min 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> Confirm Password</label>
                            <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary btn-sm btn-block">
                            <i class="fas fa-sync-alt"></i> Update Password
                        </button>
                    </form>
                </div>
                
                <!-- Footer -->
                <div class="profile-footer">
                    <p>&copy; <?php echo date('Y'); ?> EstateHub. All rights reserved.</p>
                </div>
                
            </div>
            
        </div>
    </div>
</section>

<script>
    // Preview image before upload
    document.getElementById('profile_image')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('profilePreview').src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>