<?php
$page_title = 'Dashboard';
include 'includes/config.php';
include 'includes/header.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$properties_query = "SELECT COUNT(*) as total FROM properties WHERE user_id = $user_id";
$properties_result = mysqli_query($conn, $properties_query);
$total_properties = mysqli_fetch_assoc($properties_result)['total'];

$messages_query = "SELECT COUNT(*) as total FROM messages WHERE receiver_id = $user_id AND is_read = 0";
$messages_result = mysqli_query($conn, $messages_query);
$total_messages = mysqli_fetch_assoc($messages_result)['total'];

$wishlist_query = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = $user_id";
$wishlist_result = mysqli_query($conn, $wishlist_query);
$total_wishlist = mysqli_fetch_assoc($wishlist_result)['total'];

$profile_views = rand(1000, 5000);

$recent_properties = [];
$prop_query = "SELECT * FROM properties WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 3";
$prop_result = mysqli_query($conn, $prop_query);
while($row = mysqli_fetch_assoc($prop_result)) {
    $recent_properties[] = $row;
}

$recent_messages = [];
$msg_query = "SELECT m.*, u.full_name as sender_name 
               FROM messages m 
               JOIN users u ON m.sender_id = u.id 
               WHERE m.receiver_id = $user_id 
               ORDER BY m.created_at DESC LIMIT 3";
$msg_result = mysqli_query($conn, $msg_query);
while($row = mysqli_fetch_assoc($msg_result)) {
    $recent_messages[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EstateHub</title>
    <style>
        .dashboard-layout { display: flex; gap: 30px; padding: 40px 0; background: #F8FAFC; min-height: calc(100vh - 200px); }
        .dashboard-sidebar { width: 280px; background: white; border-radius: 20px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 100px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #4B5563; text-decoration: none; border-radius: 12px; transition: all 0.3s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: #E7F5EC; color: #0E7A4E; }
        .dashboard-main { flex: 1; }
        .welcome-card { background: linear-gradient(135deg, #123524 0%, #0A2318 100%); border-radius: 20px; padding: 30px; color: white; margin-bottom: 30px; }
        .welcome-card h2 { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; border-radius: 16px; padding: 25px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-number { font-size: 32px; font-weight: 800; color: #0E7A4E; }
        .stat-label { color: #6B7280; margin-top: 8px; }
        .section-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; }
        .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .recent-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .property-item, .message-item { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #E5E7EB; }
        .property-item:last-child, .message-item:last-child { border-bottom: none; }
        .property-title, .message-sender { font-weight: 600; }
        .property-price { color: #0E7A4E; font-weight: 600; }
        .property-date, .message-time { font-size: 12px; color: #9CA3AF; }
        .message-text { font-size: 13px; color: #6B7280; }
        @media (max-width: 900px) { .dashboard-layout { flex-direction: column; } .dashboard-sidebar { width: 100%; position: static; } .stats-grid { grid-template-columns: repeat(2, 1fr); } .two-columns { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<section class="dashboard-layout">
    <div class="container" style="display: flex; gap: 30px; max-width: 1280px; margin: 0 auto; padding: 0 20px; width: 100%; flex-wrap: wrap;">
        
        <aside class="dashboard-sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>Dashboard</a></li>
                <li><a href="my-properties.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>My Properties</a></li>
                <li><a href="add-property.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>Add Property</a></li>
                <li><a href="wishlist.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>Wishlist</a></li>
                <li><a href="messages.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>Messages</a></li>
                <li><a href="profile-settings.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg>Profile</a></li>
                <li><a href="settings.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>Settings</a></li>
                <li><a href="logout.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a></li>
            </ul>
        </aside>
        
        <div class="dashboard-main">
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
                <p>Here's what's happening with your account today.</p>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-number"><?php echo $total_properties; ?></div><div class="stat-label">Total Properties</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $total_messages; ?></div><div class="stat-label">Messages</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $total_wishlist; ?></div><div class="stat-label">Wishlist</div></div>
                <div class="stat-card"><div class="stat-number"><?php echo $profile_views; ?></div><div class="stat-label">Profile Views</div></div>
            </div>
            
            <div class="two-columns">
                <div class="recent-card">
                    <h3 class="section-title">Recent Properties</h3>
                    <?php if(empty($recent_properties)): ?>
                        <p>No properties yet. <a href="add-property.php">Add your first property</a></p>
                    <?php else: ?>
                        <?php foreach($recent_properties as $prop): ?>
                        <div class="property-item">
                            <div><div class="property-title"><?php echo $prop['title']; ?></div><div class="property-date"><?php echo date('M d, Y', strtotime($prop['created_at'])); ?></div></div>
                            <div class="property-price">PKR <?php echo number_format($prop['price'] / 1000000, 1); ?>M</div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="recent-card">
                    <h3 class="section-title">Recent Messages</h3>
                    <?php if(empty($recent_messages)): ?>
                        <p>No messages yet.</p>
                    <?php else: ?>
                        <?php foreach($recent_messages as $msg): ?>
                        <div class="message-item">
                            <div><div class="message-sender"><?php echo $msg['sender_name']; ?></div><div class="message-text"><?php echo substr($msg['message'], 0, 40); ?>...</div></div>
                            <div class="message-time"><?php echo date('h:i A', strtotime($msg['created_at'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</section>

<?php include 'includes/footer.php'; ?>
</body>
</html>