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
    $city = isset($_POST['city']) ? mysqli_real_escape_string($conn, trim($_POST['city'])) : '';
    $bio = isset($_POST['bio']) ? mysqli_real_escape_string($conn, trim($_POST['bio'])) : '';

    if(!empty($full_name)) {
        mysqli_query($conn, "UPDATE users SET full_name='$full_name', phone='$phone', city='$city', bio='$bio' WHERE id=$user_id");
        $_SESSION['user_name'] = $full_name;
        $success = "Profile updated successfully!";
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
        $user['city'] = $city;
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
                if(!empty($user['profile_pic']) && $user['profile_pic'] != 'default-avatar.png') {
                    @unlink($upload_dir . $user['profile_pic']);
                }
                mysqli_query($conn, "UPDATE users SET profile_pic='$new_filename' WHERE id=$user_id");
                $success = "Profile picture updated successfully!";
                $user['profile_pic'] = $new_filename;
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

$profile_image = !empty($user['profile_pic']) && $user['profile_pic'] != 'default-avatar.png'
    ? 'uploads/profiles/' . $user['profile_pic']
    : 'https://ui-avatars.com/api/?background=0E7A4E&color=fff&size=200&name=' . urlencode($user['full_name']);

// Calculate profile completion
$fields = ['full_name', 'email', 'phone', 'bio', 'city'];
$filled = 0;
foreach($fields as $field) {
    if(!empty($user[$field])) $filled++;
}
$completion = round(($filled / count($fields)) * 100);

// Seller stats: number of properties listed + total views across them
$properties_count = 0;
$total_views = 0;
$stats_result = mysqli_query($conn, "SELECT COUNT(*) as cnt, COALESCE(SUM(views),0) as total_views FROM properties WHERE user_id = $user_id");
if ($stats_result) {
    $stats_row = mysqli_fetch_assoc($stats_result);
    $properties_count = (int) $stats_row['cnt'];
    $total_views = (int) $stats_row['total_views'];
}
$total_views_display = $total_views >= 1000 ? number_format($total_views / 1000, 1) . 'K' : (string) $total_views;

$role_label = 'User';
if ($user['user_type'] == 'admin') $role_label = 'Admin';
elseif ($user['user_type'] == 'seller') $role_label = 'Seller';
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
            --radius: 20px;
        }

        body {
            background: var(--bg);
            color: var(--text);
        }

        .profile-section {
            padding: 28px 32px 40px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .page-header {
            margin-bottom: 22px;
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

        .profile-wrapper {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 22px;
        }

        /* LEFT COLUMN */
        .profile-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0,0,0,0.04);
        }
        .profile-card-top {
            background: var(--primary-gradient);
            height: 90px;
        }
        .profile-card-body {
            padding: 0 24px 24px;
            text-align: center;
            margin-top: -50px;
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
            display: block;
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
        }
        .dp-form input { display: none; }

        .profile-name {
            margin-top: 12px;
            font-size: 20px;
            font-weight: 800;
        }
        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 20px;
            margin-top: 6px;
        }
        .profile-contact {
            margin-top: 14px;
            text-align: left;
            font-size: 13px;
            color: var(--text-light);
            font-weight: 500;
        }
        .profile-contact div {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 0;
        }
        .profile-contact i {
            color: var(--primary);
            width: 16px;
        }

        .stats-row {
            display: flex;
            justify-content: space-around;
            padding: 18px 0;
            margin-top: 12px;
            border-top: 1px solid var(--border);
        }
        .stat-item {
            text-align: center;
        }
        .stat-item i {
            color: var(--primary);
            font-size: 18px;
            margin-bottom: 6px;
        }
        .stat-item .num {
            font-size: 18px;
            font-weight: 800;
            display: block;
        }
        .stat-item .lbl {
            font-size: 11px;
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
        }

        .completion-section {
            padding-top: 14px;
            border-top: 1px solid var(--border);
            text-align: left;
        }
        .completion-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .completion-bar {
            width: 100%;
            height: 6px;
            background: var(--border);
            border-radius: 10px;
            overflow: hidden;
        }
        .completion-bar .fill {
            height: 100%;
            background: var(--primary-gradient);
            border-radius: 10px;
        }

       .completion-section {
    display: block;
    overflow: hidden;
}
.btn-change-photo {
    display: block;
    position: relative;
    clear: both;
    width: 100%;
    margin-top: 20px;
    padding: 10px;
    border: 2px solid var(--border);
    background: white;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    color: var(--primary);
    cursor: pointer;
    text-decoration: none;
}
        .btn-change-photo:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }

        /* RIGHT COLUMN */
        .right-grid {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }
        .bottom-row {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 22px;
        }

        .settings-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px 28px 26px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        .settings-card .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 14px;
            margin-bottom: 18px;
            border-bottom: 2px solid #F1F5F9;
        }
        .card-header .icon-box {
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
        .card-header h2 {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group { margin-bottom: 14px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 11px 14px;
            font-size: 14px;
            border-radius: 10px;
            border: 2px solid #EEF2F6;
            background: #FAFBFC;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            background: white;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14,122,78,0.08);
        }
        .form-group input:disabled {
            background: #F1F5F9;
            color: var(--text-light);
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .toggle-eye {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-light);
        }

        .btn {
            padding: 11px 24px;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
        }
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }
        .btn-block { width: 100%; justify-content: center; }

        .alert {
            padding: 10px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 13px;
        }
        .alert-success { background: #DCFCE7; color: #166534; }
        .alert-error { background: #FEE2E2; color: #991B1B; }

        /* Account Zone - amber styled */
        .account-card .card-header .icon-box {
            background: #FEF3C7;
            color: #D97706;
        }
        .account-card p.sub {
            font-size: 13px;
            color: var(--text-light);
            margin-bottom: 16px;
        }
        .danger-card {
            background: #FFFBEB;
            border: 2px solid #FEF3C7;
            border-radius: 12px;
            padding: 16px;
        }
        .danger-card .info {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
        }
        .danger-card .info i {
            color: #D97706;
            font-size: 18px;
            margin-top: 2px;
        }
        .danger-card .info h4 {
            font-size: 13px;
            font-weight: 700;
            color: #92400E;
        }
        .danger-card .info p {
            font-size: 12px;
            color: #92400E;
            margin-top: 2px;
        }
        .btn-danger {
            background: #EF4444;
            color: white;
            width: 100%;
            justify-content: center;
        }

        @media (max-width: 1000px) {
            .profile-wrapper { grid-template-columns: 1fr; }
            .bottom-row { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<section class="profile-section">
    <div class="container">

        <div class="page-header">
            <h1><i class="fas fa-user-cog"></i> Profile Settings</h1>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="profile-wrapper">

            <!-- LEFT: PROFILE CARD -->
            <div class="profile-card">
                <div class="profile-card-top"></div>
                <div class="profile-card-body">
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
                    <span class="profile-role"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($role_label); ?></span>

                    <div class="profile-contact">
                        <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                        <?php if (!empty($user['phone'])): ?>
                            <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="stats-row">
                        <div class="stat-item">
                            <i class="fas fa-home"></i>
                            <span class="num"><?php echo $properties_count; ?></span>
                            <span class="lbl">Properties</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-eye"></i>
                            <span class="num"><?php echo $total_views_display; ?></span>
                            <span class="lbl">Total Views</span>
                        </div>
                    </div>

                    <div class="completion-section">
                        <div class="completion-label">
                            <span>Profile Completion</span>
                            <span><?php echo $completion; ?>%</span>
                        </div>
                        <div class="completion-bar">
                            <div class="fill" style="width: <?php echo $completion; ?>%;"></div>
                        </div>
                    </div>

                    <label for="profile_image" class="btn-change-photo">
                        <i class="fas fa-camera"></i> Change Photo
                    </label>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="right-grid">

                <!-- Personal Information -->
                <div class="settings-card">
                    <div class="card-header">
                        <div class="icon-box"><i class="fas fa-user"></i></div>
                        <h2>Personal Information</h2>
                    </div>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
<input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" pattern="[A-Za-z\s]+" title="Only letters and spaces are allowed" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" required>                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
<input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. +92 300 1234567" pattern="[0-9+\-\s]+" title="Only numbers are allowed" oninput="this.value = this.value.replace(/[^0-9+\-\s]/g, '')">                            </div>
                            <div class="form-group">
                                <label>City</label>
<input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="e.g. Lahore" pattern="[A-Za-z\s]+" title="Only letters are allowed" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')">                            </div>
                            <div class="form-group full">
                                <label>Bio (Optional)</label>
                                <input type="text" name="bio" value="<?php echo htmlspecialchars($user['bio'] ?? ''); ?>" placeholder="Short bio or extra note">
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>

                <div class="bottom-row">
                    <!-- Change Password -->
                    <div class="settings-card">
                        <div class="card-header">
                            <div class="icon-box"><i class="fas fa-lock"></i></div>
                            <h2>Change Password</h2>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="current_password" id="current_password" placeholder="Enter current password" required>
                                    <i class="fas fa-eye toggle-eye" onclick="togglePwd('current_password', this)"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="new_password" id="new_password" placeholder="Min 6 characters" required>
                                    <i class="fas fa-eye toggle-eye" onclick="togglePwd('new_password', this)"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="password-wrapper">
                                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                                    <i class="fas fa-eye toggle-eye" onclick="togglePwd('confirm_password', this)"></i>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary btn-block">
                                <i class="fas fa-sync-alt"></i> Update Password
                            </button>
                        </form>
                    </div>

                    <!-- Account Zone -->
                    <div class="settings-card account-card">
                        <div class="card-header">
                            <div class="icon-box"><i class="fas fa-exclamation-triangle"></i></div>
                            <h2>Account Zone</h2>
                        </div>
                        <p class="sub">Manage your account actions.</p>
                        <div class="danger-card">
                            <div class="info">
                                <i class="fas fa-shield-alt"></i>
                                <div>
                                    <h4>Delete your account permanently.</h4>
                                    <p>All your data, properties and messages will be removed.</p>
                                </div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone!');">
                                <button type="submit" name="delete_account" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> Delete Account
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
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

    function togglePwd(id, icon) {
        const input = document.getElementById(id);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
