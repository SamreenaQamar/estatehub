<?php
session_start();
include 'includes/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email = '$email' AND status = 'active'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if(password_verify($password, $user['password']) || $user['password'] == $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_role'] = $user['user_type'];
            
            // If they were sent here from another page (e.g. to send a property message), send them back
            if (!empty($_POST['redirect'])) {
                $redirect_path = $_POST['redirect'];
                // Only allow safe, relative in-site paths
                if (preg_match('/^[a-zA-Z0-9_\-\.\/]+\.php(\?[a-zA-Z0-9_=&%\-]*)?$/', $redirect_path)) {
                    header("Location: $redirect_path");
                    exit();
                }
            }
            
            if($user['user_type'] == 'admin') {
                header("Location: admin/index.php");
            } elseif($user['user_type'] == 'seller') {
                header("Location: seller-dashboard.php");
            } else {
                header("Location: buyer-dashboard.php");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid password!";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Email not found or account inactive!";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>