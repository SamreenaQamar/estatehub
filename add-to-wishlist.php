<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Is this an AJAX call? (used by the heart icons so the page doesn't reload/redirect)
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

function respond($is_ajax, $status, $message, $redirect) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message]);
        exit();
    }
    header("Location: $redirect");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please login to add items to wishlist";
    respond($is_ajax, 'login_required', 'Please login to use wishlist.', 'login.php');
}

$user_id = (int)$_SESSION['user_id'];

// Verify user exists
$user_check = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id");
if (mysqli_num_rows($user_check) == 0) {
    session_destroy();
    respond($is_ajax, 'login_required', 'Invalid user session. Please login again.', 'login.php');
}

$fallback_redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "listings.php";

// Handle ADD to wishlist
if (isset($_GET['add'])) {
    $property_id = (int)$_GET['add'];

    // Validate property exists
    $check_prop = mysqli_query($conn, "SELECT id, title, price, purpose, city FROM properties WHERE id = $property_id AND status = 'Active'");

    if (mysqli_num_rows($check_prop) == 0) {
        respond($is_ajax, 'error', "Property ID $property_id not found!", $fallback_redirect);
    }

    $property_data = mysqli_fetch_assoc($check_prop);

    // Check if already in wishlist
    $check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $user_id AND property_id = $property_id");

    if (mysqli_num_rows($check) == 0) {
        // Insert into wishlist
        $insert = mysqli_query($conn, "INSERT INTO wishlist (user_id, property_id, created_at) VALUES ($user_id, $property_id, NOW())");

        if (!$insert) {
            respond($is_ajax, 'error', 'Database error: ' . mysqli_error($conn), $fallback_redirect);
        } else {
            respond($is_ajax, 'added', htmlspecialchars($property_data['title']) . ' added to your wishlist!', $fallback_redirect);
        }
    } else {
        respond($is_ajax, 'info', 'Property already in your wishlist!', $fallback_redirect);
    }
}

// Handle REMOVE from wishlist
if (isset($_GET['remove'])) {
    $property_id = (int)$_GET['remove'];

    $delete = mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = $user_id AND property_id = $property_id");

    if ($delete) {
        respond($is_ajax, 'removed', 'Property removed from your wishlist!', 'wishlist.php');
    } else {
        respond($is_ajax, 'error', 'Failed to remove from wishlist', 'wishlist.php');
    }
}

respond($is_ajax, 'error', 'No action specified.', 'listings.php');
?>