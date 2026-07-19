<?php
session_start();
include 'includes/config.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        echo "<strong>User found:</strong><br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "User Type: " . $user['user_type'] . "<br>";
        echo "Status: " . $user['status'] . "<br>";
        echo "Password Hash: " . $user['password'] . "<br><br>";
        
        if(password_verify($password, $user['password'])) {
            echo "<span style='color:green;font-size:18px;'>✓ PASSWORD CORRECT! Login successful.</span><br>";
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_type'] = $user['user_type'];
            echo '<meta http-equiv="refresh" content="2;url=admin/index.php">';
            echo "Redirecting to admin panel...";
        } else {
            echo "<span style='color:red;font-size:18px;'>✗ PASSWORD INCORRECT!</span><br>";
            echo "The hash in database doesn't match the password you entered.<br><br>";
            echo "Try this exact password: <strong>admin123</strong>";
        }
    } else {
        echo "<span style='color:red;'>User not found: $email</span><br>";
    }
}
?>

<style>
    body { font-family: Arial; padding: 50px; background: #f5f5f5; }
    .container { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
    input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
    button { background: #2563EB; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
</style>

<div class="container">
    <h2>Direct Login Test</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="admin@estatehub.com" value="admin@estatehub.com" required>
        <input type="password" name="password" placeholder="admin123" value="admin123" required>
        <button type="submit">Test Login</button>
    </form>
    <hr>
    <small>Use: admin@estatehub.com / admin123</small>
</div>