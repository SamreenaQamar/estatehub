<?php
session_start();
include 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'admin') {
        header("Location: admin/index.php");
        exit();
    } elseif ($_SESSION['user_type'] == 'seller') {
        header("Location: seller-dashboard.php");
        exit();
    } else {
        header("Location: buyer-dashboard.php");
        exit();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $user_role = mysqli_real_escape_string($conn, $_POST['user_role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill all required fields!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");

        if (mysqli_num_rows($check) > 0) {
            $error = "Email already registered! Please login.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO users (full_name, email, phone, password, user_type, status, created_at) 
                      VALUES ('$full_name', '$email', '$phone', '$hashed_password', '$user_role', 'active', NOW())";

            if (mysqli_query($conn, $query)) {
                $user_id = mysqli_insert_id($conn);

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_type'] = $user_role;

                if ($user_role == 'seller') {
                    header("Location: seller-dashboard.php");
                } else {
                    header("Location: buyer-dashboard.php");
                }
                exit();
            } else {
                $error = "Something went wrong. Please try again!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #123524 0%, #0A2318 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 560px;
        }

        .auth-card {
            background: white;
            border-radius: 24px;
            padding: 40px 48px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .logo {
            text-align: center;
            margin-bottom: 28px;
        }

        .logo a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo svg {
            width: 34px;
            height: 34px;
            stroke: #0E7A4E;
            stroke-width: 1.8;
            fill: none;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
        }

        .auth-card h2 {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #6B7280;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .role-section {
            margin-bottom: 24px;
        }

        .role-label {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
            display: block;
        }

        .role-options {
            display: flex;
            gap: 12px;
        }

        .role-option {
            flex: 1;
            position: relative;
        }

        .role-option input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .role-card {
            border: 2px solid #E5E7EB;
            border-radius: 16px;
            padding: 16px 12px;
            text-align: center;
            transition: all 0.3s;
            background: white;
        }

        .role-option input:checked + .role-card {
            border-color: #0E7A4E;
            background: #E7F5EC;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }

        .role-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .role-title {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .role-desc {
            font-size: 11px;
            color: #6B7280;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #9CA3AF;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: #F9FAFB;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0E7A4E;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: white;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 13px;
            color: #6B7280;
        }

        .checkbox-label input {
            width: 16px;
            height: 16px;
            accent-color: #0E7A4E;
        }

        .signup-btn {
            width: 100%;
            padding: 14px;
            background: #0E7A4E;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .signup-btn:hover {
            background: #0A5C3A;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }

        .login-link {
            text-align: center;
            color: #6B7280;
            font-size: 14px;
            margin-top: 20px;
        }

        .login-link a {
            color: #0E7A4E;
            text-decoration: none;
            font-weight: 600;
        }

        .error-msg {
            background: #FEE2E2;
            color: #DC2626;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 30px 24px;
            }
            .form-row {
                grid-template-columns: 1fr;
            }
            .role-options {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="auth-card">
        <div class="logo">
            <a href="index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>

        <h2>Create Account</h2>
        <p class="subtitle">Sign up to get started!</p>

        <?php if ($error): ?>
            <div class="error-msg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="role-section">
                <label class="role-label">Register as</label>
                <div class="role-options">
                    <label class="role-option">
                        <input type="radio" name="user_role" value="user" checked>
                        <div class="role-card">
                            <div class="role-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M5 20v-2a7 7 0 0 1 14 0v2"/>
                                </svg>
                            </div>
                            <div class="role-title">Buyer</div>
                            <div class="role-desc">Find & buy properties</div>
                        </div>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="user_role" value="seller">
                        <div class="role-card">
                            <div class="role-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#0E7A4E" stroke-width="1.8">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                            </div>
                            <div class="role-title">Seller</div>
                            <div class="role-desc">List & sell properties</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M5 20v-2a7 7 0 0 1 14 0v2"/>
                        </svg>
                        <input type="text" name="full_name" placeholder="Enter your full name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/>
                        </svg>
                        <input type="tel" name="phone" placeholder="03XX-XXXXXXX">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <div class="input-wrapper">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" name="password" placeholder="Min. 6 characters" required minlength="6">
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" name="confirm_password" placeholder="Re-enter password" required minlength="6">
                    </div>
                </div>
            </div>

            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" required> I agree to the Terms & Conditions
                </label>
            </div>

            <button type="submit" class="signup-btn">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</div>
</body>
</html>