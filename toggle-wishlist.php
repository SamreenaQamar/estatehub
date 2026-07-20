<?php
require_once __DIR__ . '/includes/config.php';

// Enable error reporting for debugging (remove/disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Is this an AJAX call? (used by the heart icons on home/listings/wishlist pages
// so the page doesn't reload/redirect)
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

$fallback_redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'listings.php';

// ------------------------------------------------------------------
// Must be logged in
// ------------------------------------------------------------------
if (!isset($_SESSION['user_id'])) {
    respond($is_ajax, 'login_required', 'Please login to use wishlist.', 'login.php');
}

$user_id = (int) $_SESSION['user_id'];

// Verify user still exists
$user_check = mysqli_query($conn, "SELECT id FROM users WHERE id = $user_id");
if (!$user_check || mysqli_num_rows($user_check) == 0) {
    session_destroy();
    respond($is_ajax, 'login_required', 'Invalid user session. Please login again.', 'login.php');
}

// ------------------------------------------------------------------
// MAIN PATH: heart icon AJAX toggle by property_title
// (used on index.php, listings.php, wishlist.php)
// ------------------------------------------------------------------
if (isset($_POST['property_title'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['property_title']));

    if ($title === '') {
        respond($is_ajax, 'error', 'No property specified.', $fallback_redirect);
    }

    $prop_result = mysqli_query($conn, "SELECT id FROM properties WHERE title = '$title' LIMIT 1");
    if (!$prop_result || mysqli_num_rows($prop_result) == 0) {
        respond($is_ajax, 'error', "Property \"$title\" not found!", $fallback_redirect);
    }
    $property = mysqli_fetch_assoc($prop_result);
    $property_id = (int) $property['id'];

    $check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $user_id AND property_id = $property_id");

    if ($check && mysqli_num_rows($check) > 0) {
        // Already wishlisted -> remove it (toggle off)
        $delete = mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = $user_id AND property_id = $property_id");
        if ($delete) {
            respond($is_ajax, 'removed', 'Removed from wishlist', $fallback_redirect);
        } else {
            respond($is_ajax, 'error', 'Database error: ' . mysqli_error($conn), $fallback_redirect);
        }
    } else {
        // Not wishlisted yet -> add it (toggle on)
        $insert = mysqli_query($conn, "INSERT INTO wishlist (user_id, property_id, created_at) VALUES ($user_id, $property_id, NOW())");
        if ($insert) {
            respond($is_ajax, 'added', 'Added to wishlist ❤️', $fallback_redirect);
        } else {
            respond($is_ajax, 'error', 'Database error: ' . mysqli_error($conn), $fallback_redirect);
        }
    }
}

// ------------------------------------------------------------------
// LEGACY PATH: explicit add by property id (?add=ID)
// ------------------------------------------------------------------
if (isset($_GET['add'])) {
    $property_id = (int) $_GET['add'];

    $check_prop = mysqli_query($conn, "SELECT id, title FROM properties WHERE id = $property_id");
    if (!$check_prop || mysqli_num_rows($check_prop) == 0) {
        respond($is_ajax, 'error', "Property ID $property_id not found!", $fallback_redirect);
    }
    $property_data = mysqli_fetch_assoc($check_prop);

    $check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = $user_id AND property_id = $property_id");

    if ($check && mysqli_num_rows($check) == 0) {
        $insert = mysqli_query($conn, "INSERT INTO wishlist (user_id, property_id, created_at) VALUES ($user_id, $property_id, NOW())");
        if ($insert) {
            respond($is_ajax, 'added', htmlspecialchars($property_data['title']) . ' added to your wishlist!', $fallback_redirect);
        } else {
            respond($is_ajax, 'error', 'Database error: ' . mysqli_error($conn), $fallback_redirect);
        }
    } else {
        respond($is_ajax, 'info', 'Property already in your wishlist!', $fallback_redirect);
    }
}

// ------------------------------------------------------------------
// LEGACY PATH: explicit remove by property id (?remove=ID)
// ------------------------------------------------------------------
if (isset($_GET['remove'])) {
    $property_id = (int) $_GET['remove'];

    $delete = mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = $user_id AND property_id = $property_id");
    if ($delete) {
        respond($is_ajax, 'removed', 'Property removed from your wishlist!', 'wishlist.php');
    } else {
        respond($is_ajax, 'error', 'Failed to remove from wishlist', 'wishlist.php');
    }
}

respond($is_ajax, 'error', 'No action specified.', 'listings.php');