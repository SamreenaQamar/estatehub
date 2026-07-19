<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'estatehub_db';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('Asia/Karachi');

define('SITE_NAME', 'EstateHub');
define('SITE_URL', 'http://localhost/estatehub/');
define('SITE_EMAIL', 'info@estatehub.com');
define('ADMIN_EMAIL', 'admin@estatehub.com');
define('MAX_FILE_SIZE', 5242880);

$allowed_image_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$upload_paths = [
    'properties' => 'uploads/properties/',
    'profiles' => 'uploads/profiles/',
    'temp' => 'uploads/temp/'
];

foreach ($upload_paths as $path) {
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function sanitize($input) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($input)));
}

function formatPrice($price) {
    if ($price >= 10000000) {
        return 'PKR ' . number_format($price / 10000000, 1) . 'Cr';
    } elseif ($price >= 100000) {
        return 'PKR ' . number_format($price / 100000, 1) . 'Lac';
    }
    return 'PKR ' . number_format($price);
}

function formatPriceInM($price) {
    return 'PKR ' . number_format($price / 1000000, 1) . 'M';
}

function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $time_difference = time() - $time_ago;
    $seconds = $time_difference;

    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "Just Now";
    } elseif ($minutes <= 60) {
        return $minutes == 1 ? "1 minute ago" : "$minutes minutes ago";
    } elseif ($hours <= 24) {
        return $hours == 1 ? "1 hour ago" : "$hours hours ago";
    } elseif ($days <= 7) {
        return $days == 1 ? "Yesterday" : "$days days ago";
    } elseif ($weeks <= 4.3) {
        return $weeks == 1 ? "1 week ago" : "$weeks weeks ago";
    } elseif ($months <= 12) {
        return $months == 1 ? "1 month ago" : "$months months ago";
    }

    return $years == 1 ? "1 year ago" : "$years years ago";
}

function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Active':
            return 'badge-success';
        case 'Pending':
            return 'badge-warning';
        case 'Sold':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }
    header("Location: " . SITE_URL . $url);
    exit();
}

function displayMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $class = $type == 'success' ? 'alert-success' : 'alert-error';
        echo '<div class="' . $class . '">' . $_SESSION['message'] . '</div>';
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
}

function getUserById($id) {
    global $conn;
    $id = (int)$id;
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $id");
    return mysqli_fetch_assoc($result);
}

function getPropertyById($id) {
    global $conn;
    $id = (int)$id;
    $result = mysqli_query($conn, "SELECT * FROM properties WHERE id = $id");
    return mysqli_fetch_assoc($result);
}

function getAllCities() {
    global $conn;
    $cities = [];
    $result = mysqli_query($conn, "SELECT DISTINCT city FROM properties WHERE status = 'Active' ORDER BY city");
    while ($row = mysqli_fetch_assoc($result)) {
        $cities[] = $row['city'];
    }
    return $cities;
}

function getPropertyTypes() {
    return ['House', 'Apartment', 'Plot', 'Commercial', 'Farm House', 'Portion'];
}

function getPropertyPurposes() {
    return ['Sale', 'Rent'];
}

// Returns the actual uploaded image if one exists, otherwise a stock photo
// that matches the property's type (so a House never shows an Apartment photo, etc.)
function getPropertyImage($property) {
    if (!empty($property['image_main']) && $property['image_main'] != 'default-property.jpg') {
        return 'uploads/properties/' . $property['image_main'];
    }

    $type_images = [
        'House'       => 'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg',
        'Apartment'   => 'https://images.pexels.com/photos/2587054/pexels-photo-2587054.jpeg',
        'Villa'       => 'https://images.pexels.com/photos/1396122/pexels-photo-1396122.jpeg',
        'Plot'        => 'https://images.pexels.com/photos/280221/pexels-photo-280221.jpeg',
        'Commercial'  => 'https://images.pexels.com/photos/276724/pexels-photo-276724.jpeg',
        'Farm House'  => 'https://images.pexels.com/photos/208736/pexels-photo-208736.jpeg',
        'Penthouse'   => 'https://images.pexels.com/photos/258154/pexels-photo-258154.jpeg',
        'Portion'     => 'https://images.pexels.com/photos/1571460/pexels-photo-1571460.jpeg',
    ];

    $type = $property['property_type'] ?? '';
    return $type_images[$type] ?? 'https://images.pexels.com/photos/106399/pexels-photo-106399.jpeg';
}