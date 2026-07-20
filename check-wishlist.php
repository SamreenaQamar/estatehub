<?php
require_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple debug/diagnostic page for checking wishlist data.
// NOTE: for internal/admin use only - do not leave open on a live site.

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 4; // default test user

function formatPrice($price) {
    $price = (float) $price;
    if ($price <= 0) {
        return 'Contact for Price';
    }
    return 'PKR ' . number_format($price / 1000000, 1) . 'M';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Wishlist Debug</title>
<style>
    body { font-family: 'Inter', Arial, sans-serif; padding: 30px; background: #f8fafc; color: #111827; }
    h2 { color: #0E7A4E; }
    h3 { margin-top: 30px; margin-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; background: white; margin-bottom: 20px; }
    table, th, td { border: 1px solid #e5e7eb; }
    th, td { padding: 8px 12px; text-align: left; font-size: 14px; }
    th { background: #0E7A4E; color: white; }
    pre { background: #111827; color: #d1fae5; padding: 14px; border-radius: 8px; overflow-x: auto; }
    .note { color: #6B7280; font-size: 13px; }
</style>
</head>
<body>

<h2>Current Wishlist Data Debug</h2>
<p class="note">Checking data for user_id = <?php echo $user_id; ?></p>

<h3>Wishlist Table Content</h3>
<?php
$query = "SELECT * FROM wishlist WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<table>
    <tr><th>ID</th><th>User ID</th><th>Property ID</th><th>Created At</th></tr>
    <?php if ($result && mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['user_id']; ?></td>
                <td><?php echo $row['property_id']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4">No wishlist entries found for this user.</td></tr>
    <?php endif; ?>
</table>

<h3>Properties in Wishlist (with formatted price)</h3>
<?php
$query2 = "SELECT w.property_id, p.id, p.title, p.price, p.purpose, p.city, p.status
           FROM wishlist w
           JOIN properties p ON w.property_id = p.id
           WHERE w.user_id = $user_id";
$result2 = mysqli_query($conn, $query2);
?>
<table>
    <tr>
        <th>Wishlist Property ID</th>
        <th>DB Property ID</th>
        <th>Title</th>
        <th>Price (raw)</th>
        <th>Price (formatted)</th>
        <th>Purpose</th>
        <th>City</th>
        <th>Status</th>
    </tr>
    <?php if ($result2 && mysqli_num_rows($result2) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result2)): ?>
            <tr>
                <td><?php echo $row['property_id']; ?></td>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo $row['price']; ?></td>
                <td><?php echo formatPrice($row['price']); ?><?php echo ($row['purpose'] == 'Rent') ? ' / Month' : ''; ?></td>
                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                <td><?php echo htmlspecialchars($row['city']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="8">No matching properties found (possible orphaned wishlist rows).</td></tr>
    <?php endif; ?>
</table>

<h3>Cleanup: Orphaned Wishlist Rows</h3>
<?php
// Remove wishlist rows pointing to properties that no longer exist
$fix_query = "DELETE FROM wishlist WHERE property_id NOT IN (SELECT id FROM properties)";
$fixed = mysqli_query($conn, $fix_query);
$affected = mysqli_affected_rows($conn);
?>
<p>Cleaned <?php echo $affected; ?> orphaned wishlist entr<?php echo $affected == 1 ? 'y' : 'ies'; ?> (rows whose property no longer exists).</p>

<h3>Manual Reference Commands</h3>
<pre>
DELETE FROM wishlist WHERE user_id = <?php echo $user_id; ?>;
INSERT INTO wishlist (user_id, property_id, created_at) VALUES
    (<?php echo $user_id; ?>, 1, NOW()),
    (<?php echo $user_id; ?>, 3, NOW()),
    (<?php echo $user_id; ?>, 5, NOW());
</pre>

</body>
</html>