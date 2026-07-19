<?php
session_start();
include 'includes/config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($property_id > 0) {
    $user_id = $_SESSION['user_id'];
    $query = "DELETE FROM wishlist WHERE user_id = $user_id AND property_id = $property_id";
    mysqli_query($conn, $query);
    $_SESSION['success'] = "Property removed from wishlist!";
}

header("Location: wishlist.php");
exit();
?>