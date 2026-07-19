<?php
require_once 'includes/config.php';
session_start();

echo "<h2>Current Wishlist Data Debug</h2>";

$user_id = $_SESSION['user_id'] ?? 4; // Default to user 4 (Sara Khan)

echo "<h3>Wishlist Table Content:</h3>";
$query = "SELECT * FROM wishlist WHERE user_id = $user_id";
$result = mysqli_query($conn, $query);
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>User ID</th><th>Property ID</th><th>Created At</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['user_id']}</td>";
    echo "<td>{$row['property_id']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Properties in Wishlist:</h3>";
$query2 = "SELECT w.property_id, p.id, p.title, p.price, p.city 
           FROM wishlist w 
           JOIN properties p ON w.property_id = p.id 
           WHERE w.user_id = $user_id";
$result2 = mysqli_query($conn, $query2);
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Wishlist Property ID</th><th>DB Property ID</th><th>Title</th><th>Price</th><th>City</th></tr>";
while ($row = mysqli_fetch_assoc($result2)) {
    echo "<tr>";
    echo "<td>{$row['property_id']}</td>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['title']}</td>";
    echo "<td>{$row['price']}</td>";
    echo "<td>{$row['city']}</td>";
    echo "</tr>";
}
echo "</table>";

// Fix any mismatched data
echo "<h3>Fixing mismatched data...</h3>";
$fix_query = "DELETE FROM wishlist WHERE property_id NOT IN (SELECT id FROM properties)";
mysqli_query($conn, $fix_query);
echo "Cleaned orphaned wishlist entries.<br>";

echo "<h3>To manually add correct properties:</h3>";
echo "<pre>";
echo "DELETE FROM wishlist WHERE user_id = $user_id;<br>";
echo "INSERT INTO wishlist (user_id, property_id) VALUES ($user_id, 1), ($user_id, 3), ($user_id, 5);";
echo "</pre>";
?>