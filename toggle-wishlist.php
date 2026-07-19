<?php
session_start();
include 'includes/config.php';

// Is this an AJAX call? (used by the homepage heart icons / dashboard toggle)
$is_ajax = (isset($_POST['ajax']) && $_POST['ajax'] == '1')
    || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

function respond($is_ajax, $status, $message, $extra = [], $redirect = 'listings.php') {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
        exit();
    }
    $_SESSION[$status === 'error' ? 'error' : 'success'] = $message;
    header("Location: $redirect");
    exit();
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    respond($is_ajax, 'login_required', 'Please login to use wishlist.', [], 'login.php');
}

$user_id = (int)$_SESSION['user_id'];

// Get property ID from POST or GET
$property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
if($property_id == 0 && isset($_GET['property_id'])) {
    $property_id = (int)$_GET['property_id'];
}

// Fallback: some pages (e.g. static homepage cards) only know the property
// title, not its real database id. Look it up by exact title match.
if($property_id == 0 && !empty($_POST['property_title'])) {
    $title = mysqli_real_escape_string($conn, $_POST['property_title']);
    $lookup = mysqli_query($conn, "SELECT id FROM properties WHERE title = '$title' AND status = 'Active' LIMIT 1");
    if($lookup && mysqli_num_rows($lookup) > 0) {
        $property_id = (int) mysqli_fetch_assoc($lookup)['id'];
    } else {
        respond($is_ajax, 'error', 'This is a demo listing and is not linked to a real property yet.');
    }
}

if($property_id == 0) {
    respond($is_ajax, 'error', 'No property specified.');
}

// Make sure the property actually exists
$exists = mysqli_query($conn, "SELECT id FROM properties WHERE id = $property_id AND status = 'Active'");
if(!$exists || mysqli_num_rows($exists) == 0) {
    respond($is_ajax, 'error', 'Property not found.');
}

// Check if already in wishlist
$check_query = "SELECT id FROM wishlist WHERE user_id = $user_id AND property_id = $property_id";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) > 0) {
    // Remove from wishlist
    $delete_query = "DELETE FROM wishlist WHERE user_id = $user_id AND property_id = $property_id";
    mysqli_query($conn, $delete_query);
    respond($is_ajax, 'removed', 'Property removed from wishlist!', ['property_id' => $property_id], "property-detail.php?id=$property_id");
} else {
    // Add to wishlist
    $insert_query = "INSERT INTO wishlist (user_id, property_id, created_at) VALUES ($user_id, $property_id, NOW())";
    mysqli_query($conn, $insert_query);
    respond($is_ajax, 'added', 'Property added to wishlist!', ['property_id' => $property_id], "property-detail.php?id=$property_id");
}
?>