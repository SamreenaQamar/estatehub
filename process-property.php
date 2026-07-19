<?php
session_start();
include 'includes/config.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? '';
$is_admin = ($user_type == 'admin');

// ============================================ //
// ADD NEW PROPERTY
// ============================================ //
if(isset($_POST['add_property'])) {

    // Only sellers and admins can list properties — buyers have limited access
    if($user_type != 'seller' && !$is_admin) {
        $_SESSION['error'] = "Only sellers can add properties. Please contact support if you'd like to become a seller.";
        header("Location: buyer-dashboard.php");
        exit();
    }
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $price = (int)$_POST['price'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $bedrooms = (int)$_POST['bedrooms'];
    $bathrooms = (int)$_POST['bathrooms'];
    $area_size = mysqli_real_escape_string($conn, $_POST['area']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Handle image upload
    $main_image = 'default-property.jpg';
    if(isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $upload_dir = 'uploads/properties/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $main_image = time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $main_image);
        }
    }
    
    $query = "INSERT INTO properties (user_id, title, property_type, purpose, price, location, city, bedrooms, bathrooms, area_size, description, image_main, status, created_at) 
              VALUES ($user_id, '$title', '$property_type', '$purpose', $price, '$location', '$city', $bedrooms, $bathrooms, '$area_size', '$description', '$main_image', 'Pending', NOW())";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Property added successfully! It will be reviewed by admin.";
        header("Location: my-properties.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to add property. Please try again.";
        header("Location: add-property.php");
        exit();
    }
}

// ============================================ //
// EDIT PROPERTY
// ============================================ //
if(isset($_POST['edit_property'])) {
    $property_id = (int)$_POST['property_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);
    $purpose = mysqli_real_escape_string($conn, $_POST['purpose']);
    $price = (int)$_POST['price'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $bedrooms = (int)$_POST['bedrooms'];
    $bathrooms = (int)$_POST['bathrooms'];
    $area_size = mysqli_real_escape_string($conn, $_POST['area']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    
    // Verify ownership — admins have full access to any property, others only their own
    $ownership_clause = $is_admin ? "id = $property_id" : "id = $property_id AND user_id = $user_id";
    $check_query = "SELECT id FROM properties WHERE $ownership_clause";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) == 0) {
        $_SESSION['error'] = "You don't have permission to edit this property.";
        header("Location: my-properties.php");
        exit();
    }
    
    // Handle image upload for edit
    $image_update = "";
    if(isset($_FILES['main_image']) && $_FILES['main_image']['error'] == 0) {
        $upload_dir = 'uploads/properties/';
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $main_image = time() . '_' . rand(1000, 9999) . '.' . $ext;
            move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_dir . $main_image);
            $image_update = ", image_main = '$main_image'";
        }
    }
    
    $query = "UPDATE properties SET 
              title = '$title',
              property_type = '$property_type',
              purpose = '$purpose',
              price = $price,
              location = '$location',
              city = '$city',
              bedrooms = $bedrooms,
              bathrooms = $bathrooms,
              area_size = '$area_size',
              description = '$description'
              $image_update
              WHERE $ownership_clause";
    
    if(mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Property updated successfully!";
        header("Location: my-properties.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update property.";
        header("Location: edit-property.php?id=$property_id");
        exit();
    }
}

// ============================================ //
// DELETE PROPERTY
// ============================================ //
if(isset($_GET['delete'])) {
    $property_id = (int)$_GET['delete'];
    
    // Verify ownership — admins have full access to any property, others only their own
    $delete_ownership_clause = $is_admin ? "id = $property_id" : "id = $property_id AND user_id = $user_id";
    $check_query = "SELECT image_main FROM properties WHERE $delete_ownership_clause";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $property = mysqli_fetch_assoc($check_result);
        
        // Delete property image if exists
        if($property['image_main'] != 'default-property.jpg') {
            $image_path = 'uploads/properties/' . $property['image_main'];
            if(file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Delete from wishlist first (foreign key constraint)
        mysqli_query($conn, "DELETE FROM wishlist WHERE property_id = $property_id");
        
        // Delete property
        $query = "DELETE FROM properties WHERE $delete_ownership_clause";
        if(mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Property deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete property.";
        }
    } else {
        $_SESSION['error'] = "Property not found or you don't have permission.";
    }
    
    header("Location: my-properties.php");
    exit();
}

// ============================================ //
// ADD TO WISHLIST
// ============================================ //
if(isset($_GET['add_to_wishlist'])) {
    $property_id = (int)$_GET['add_to_wishlist'];
    
    // Check if already in wishlist
    $check_query = "SELECT id FROM wishlist WHERE user_id = $user_id AND property_id = $property_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) == 0) {
        $query = "INSERT INTO wishlist (user_id, property_id) VALUES ($user_id, $property_id)";
        if(mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Property added to wishlist!";
        } else {
            $_SESSION['error'] = "Failed to add to wishlist.";
        }
    } else {
        $_SESSION['error'] = "Property already in wishlist.";
    }
    
    header("Location: property-detail.php?id=$property_id");
    exit();
}

// ============================================ //
// REMOVE FROM WISHLIST
// ============================================ //
if(isset($_GET['remove_from_wishlist'])) {
    $property_id = (int)$_GET['remove_from_wishlist'];
    
    $query = "DELETE FROM wishlist WHERE user_id = $user_id AND property_id = $property_id";
    mysqli_query($conn, $query);
    
    $_SESSION['success'] = "Property removed from wishlist.";
    header("Location: wishlist.php");
    exit();
}

// ============================================ //
// REDIRECT IF NO ACTION
// ============================================ //
header("Location: index.php");
exit();
?>