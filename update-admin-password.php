<?php
include 'includes/config.php';

$email = 'admin@estatehub.com';
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$query = "UPDATE users SET password = '$hashed_password' WHERE email = '$email'";

if (mysqli_query($conn, $query)) {
    $check = mysqli_query($conn, "SELECT password FROM users WHERE email = '$email'");
    $user = mysqli_fetch_assoc($check);

    if ($user && password_verify($new_password, $user['password'])) {
        echo "Password updated and verified. You can now <a href='login.php'>login</a>.";
    } else {
        echo "Update ran but verification failed.";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}