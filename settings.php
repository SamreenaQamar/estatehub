<?php
$page_title = 'Settings';
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id"));

$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $marketing_emails = isset($_POST['marketing_emails']) ? 1 : 0;

    $query = "UPDATE users SET 
              email_notifications = $email_notifications,
              sms_notifications = $sms_notifications,
              marketing_emails = $marketing_emails
              WHERE id = $user_id";
    mysqli_query($conn, $query);

    $success = "Settings saved successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - EstateHub</title>
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .settings-section {
            padding: 40px 0;
            background: #F8FAFC;
            min-height: calc(100vh - 200px);
        }

        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .settings-card h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #F3F4F6;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
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
            background-color: #2563EB;
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
            margin-bottom: 8px;
            color: #111827;
        }

        .language-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            background: white;
        }

        .save-settings-btn {
            background: #2563EB;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            font-size: 14px;
            transition: background 0.2s;
        }

        .save-settings-btn:hover {
            background: #1D4ED8;
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
            transition: background 0.2s;
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

        @media (max-width: 768px) {
            .settings-container {
                padding: 0;
            }
            .settings-card {
                padding: 20px;
            }
            .setting-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>

<section class="settings-section">
    <div class="container">
        <div class="settings-container">

            <?php if ($success): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="settings-card">
                <h2>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2">
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
                            <input type="checkbox" name="email_notifications" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>SMS Alerts</h4>
                            <p>Get instant alerts on your phone</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="sms_notifications">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div class="setting-info">
                            <h4>Marketing Emails</h4>
                            <p>Receive promotional offers and newsletters</p>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" name="marketing_emails" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <button type="submit" name="save_settings" class="save-settings-btn">Save Preferences</button>
                </form>
            </div>

            <div class="settings-card">
                <h2>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15 15 0 0 0 0 20 15 15 0 0 0 0-20z"/>
                    </svg>
                    Language & Region
                </h2>
                <div class="form-group">
                    <label>Language</label>
                    <select class="language-select">
                        <option value="en">English (United States)</option>
                        <option value="ur">Urdu (Pakistan)</option>
                        <option value="ar">Arabic</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Currency Display</label>
                    <select class="language-select">
                        <option value="pkr">PKR - Pakistani Rupee</option>
                        <option value="usd">USD - US Dollar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Time Zone</label>
                    <select class="language-select">
                        <option value="asia/karachi">Asia/Karachi (PKT)</option>
                        <option value="asia/dubai">Asia/Dubai (GST)</option>
                    </select>
                </div>
            </div>

            <div class="settings-card">
                <h2>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#2563EB" stroke-width="2">
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
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                <div class="setting-item">
                    <div class="setting-info">
                        <h4>Show Profile in Search</h4>
                        <p>Allow others to find your profile</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="settings-card danger-zone">
                <h2>
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    Danger Zone
                </h2>
                <div class="setting-item">
                    <div class="setting-info">
                        <h4 style="color: #DC2626;">Delete Account</h4>
                        <p>Permanently delete your account and all data</p>
                    </div>
                    <button class="delete-account-btn" onclick="confirmDelete()">Delete Account</button>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
function confirmDelete() {
    if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
        window.location.href = "delete-account.php";
    }
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>