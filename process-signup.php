<?php
session_start();
include 'includes/config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $user_role = mysqli_real_escape_string($conn, $_POST['user_role']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($full_name) || empty($email) || empty($password)) {
        $_SESSION['signup_error'] = "Please fill all required fields!";
        header("Location: signup.php");
        exit();
    }
    
    if($password !== $confirm_password) {
        $_SESSION['signup_error'] = "Passwords do not match!";
        header("Location: signup.php");
        exit();
    }
    
    if(strlen($password) < 6) {
        $_SESSION['signup_error'] = "Password must be at least 6 characters!";
        header("Location: signup.php");
        exit();
    }
    
    // Check if email exists
    $check_query = "SELECT * FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $_SESSION['signup_error'] = "Email already registered! Please login.";
        header("Location: signup.php");
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $query = "INSERT INTO users (full_name, email, phone, password, user_type, status) 
              VALUES ('$full_name', '$email', '$phone', '$hashed_password', '$user_role', 'active')";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['signup_success'] = "Account created successfully! Please login.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['signup_error'] = "Something went wrong. Please try again!";
        header("Location: signup.php");
        exit();
    }
}
?>