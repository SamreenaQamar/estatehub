<?php
$page_title = 'Message Center';
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['user_type'] != 'seller') {
    if ($_SESSION['user_type'] == 'user' || $_SESSION['user_type'] == 'buyer') {
        header("Location: buyer-dashboard.php");
    } elseif ($_SESSION['user_type'] == 'admin') {
        header("Location: admin/index.php");
    }
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Seller';

// Unread message count for the sidebar badge (matches seller-dashboard.php's own count)
$total_messages = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM messages WHERE receiver_id = $user_id AND is_read = 0");
if ($r) $total_messages = (int) mysqli_fetch_assoc($r)['c'];

// Handle delete
if (isset($_GET['delete_msg']) && is_numeric($_GET['delete_msg'])) {
    $msg_id = (int) $_GET['delete_msg'];
    mysqli_query($conn, "DELETE FROM messages WHERE id = $msg_id AND (sender_id = $user_id OR receiver_id = $user_id)");
    header("Location: seller-messages.php" . (isset($_GET['filter']) ? "?filter=" . urlencode($_GET['filter']) : ""));
    exit();
}

// Handle mark-as-read (fired right before opening a reply)
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $msg_id = (int) $_GET['mark_read'];
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE id = $msg_id AND receiver_id = $user_id");
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Counts for the filter tabs
$count_all = 0;
$count_unread = 0;
$count_read = 0;

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM messages WHERE sender_id = $user_id OR receiver_id = $user_id");
if ($r) $count_all = (int) mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM messages WHERE receiver_id = $user_id AND is_read = 0");
if ($r) $count_unread = (int) mysqli_fetch_assoc($r)['c'];

$count_read = $count_all - $count_unread;

// Build the message feed query based on the active filter
$where = "(m.sender_id = $user_id OR m.receiver_id = $user_id)";
if ($filter === 'unread') {
    $where .= " AND m.receiver_id = $user_id AND m.is_read = 0";
} elseif ($filter === 'read') {
    $where .= " AND NOT (m.receiver_id = $user_id AND m.is_read = 0)";
}

$messages_query = "
    SELECT m.*,
           u.full_name as sender_name,
           u.email as sender_email,
           u.profile_pic as sender_pic,
           p.title as property_title,
           p.id as prop_id
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    LEFT JOIN properties p ON p.id = m.property_id
    WHERE $where
    ORDER BY m.created_at DESC
";
$messages_result = mysqli_query($conn, $messages_query);

function safe_profile_pic($filename) {
    if (empty($filename)) return null;
    $path = 'uploads/profiles/' . $filename;
    return file_exists($path) ? $path : null;
}

$avatar_palette = ['#0E7A4E', '#0B5E3B', '#15803D', '#065F46', '#166534', '#14532D'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Center | EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }

    body {
        background: #0A1A12;
        min-height: 100vh;
    }

    .dashboard-container {
        display: flex;
        min-height: 100vh;
        background: #f8f9fa;
    }

    /* ========== SIDEBAR (green EstateHub theme) ========== */
    .sidebar {
        width: 280px;
        min-width: 280px;
        background: #0B1220;
        padding: 24px 20px;
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 100;
        box-shadow: 4px 0 24px rgba(0,0,0,0.1);
    }
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

    .sidebar-logo {
        display: flex;
        align-items: center;
        text-decoration: none;
        gap: 12px;
        padding: 12px 16px 24px;
        margin-bottom: 24px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .logo-icon {
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, #0E7A4E, #16A34A);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .logo-icon svg { width: 24px; height: 24px; color: white; }
    .logo-text { font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: -0.5px; }

    .sidebar-section { margin-bottom: 20px; }
    .sidebar-label {
        font-size: 11px;
        font-weight: 600;
        color: rgba(255,255,255,0.3);
        text-transform: uppercase;
        letter-spacing: 2px;
        padding: 8px 16px;
        margin-bottom: 8px;
    }
    .sidebar-menu { list-style: none; padding: 0; }
    .sidebar-menu li { margin-bottom: 2px; }
    .sidebar-menu a {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 13px 16px;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 500;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        letter-spacing: 0.3px;
    }
    .sidebar-menu a:hover { background: rgba(255,255,255,0.08); color: #ffffff; transform: translateX(4px); }
    .sidebar-menu a.active {
        background: linear-gradient(135deg, #0E7A4E, #16A34A);
        color: white;
        font-weight: 600;
        box-shadow: 0 8px 16px rgba(14, 122, 78, 0.3);
    }
    .sidebar-menu svg { width: 20px; height: 20px; flex-shrink: 0; }

    .badge {
        background: #DC2626;
        color: #fff;
        text-decoration: none;
        border-radius: 20px;
        padding: 3px 12px;
        font-size: 11px;
        font-weight: 600;
        margin-left: auto;
        display: inline-block;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .sidebar-menu a.active .badge {
        background: rgba(255,255,255,0.25);
    }

    .logout-link { color: rgba(255,255,255,0.4) !important; margin-top: 8px; }
    .logout-link:hover { background: rgba(255,71,87,0.1) !important; color: #ff4757 !important; }

    /* ========== MAIN CONTENT ========== */
    .main-content {
        flex: 1;
        padding: 32px;
        overflow-y: auto;
        background: #f8f9fa;
    }

    /* ========== MESSAGE CENTER ========== */
    .mc-header h1 { font-size: 28px; font-weight: 800; color: #0B1A2E; }
    .mc-header p { color: #6B7280; font-size: 14px; margin-top: 4px; margin-bottom: 22px; }

    .mc-tabs { display: flex; gap: 10px; margin-bottom: 24px; }
    .mc-tab {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        color: #374151;
        background: white;
        border: 1px solid #E5E7EB;
        transition: all 0.2s;
    }
    .mc-tab.active { background: #0E7A4E; color: white; border-color: #0E7A4E; }
    .mc-tab .count { background: #F3F4F6; color: #374151; padding: 1px 9px; border-radius: 20px; font-size: 12px; }
    .mc-tab.active .count { background: rgba(255,255,255,0.25); color: white; }

    .mc-card {
        background: white;
        border-radius: 18px;
        padding: 22px 24px;
        margin-bottom: 18px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        border: 1px solid #F1F5F9;
        position: relative;
    }
    .mc-card.unread { border-left: 4px solid #0E7A4E; }
    .mc-card-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 14px; }
    .mc-sender { display: flex; align-items: center; gap: 12px; }
    .mc-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 800;
        font-size: 17px;
        overflow: hidden;
        flex-shrink: 0;
    }
    .mc-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .mc-sender-info h4 { font-size: 15px; font-weight: 700; color: #0B1A2E; }
    .mc-sender-info p { font-size: 13px; color: #6B7280; }
    .mc-time { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #9CA3AF; white-space: nowrap; }

    .mc-body { font-size: 14.5px; color: #1F2937; line-height: 1.6; margin-bottom: 14px; white-space: pre-line; }

    .mc-property-tag {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #0E7A4E;
        margin-bottom: 16px;
    }

    .mc-actions { display: flex; gap: 10px; }
    .mc-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 20px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none;
        border: none;
        cursor: pointer;
    }
    .mc-btn-reply { background: #F1F5F9; color: #0B1A2E; }
    .mc-btn-reply:hover { background: #E5E7EB; }
    .mc-btn-delete { background: #EF4444; color: white; }
    .mc-btn-delete:hover { background: #DC2626; }

    .mc-empty { text-align: center; background: white; border-radius: 18px; padding: 60px 30px; color: #6B7280; }
    .mc-empty i { font-size: 40px; color: #D1D5DB; margin-bottom: 14px; }

    @media (max-width: 900px) {
        .sidebar { display: none; }
        .main-content { padding: 20px; }
    }
    @media (max-width: 600px) {
        .mc-card-top { flex-direction: column; }
        .mc-time { align-self: flex-start; }
    }
</style>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <a href="index.php" class="navbar-brand" title="Go to Homepage">
                <span class="logo-text">EstateHub</span>
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-label">Main Menu</div>
            <ul class="sidebar-menu">
                <li><a href="seller-dashboard.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                    Dashboard
                </a></li>
                <li><a href="my-properties.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    My Properties
                </a></li>
                <li><a href="add-property.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    Add Property
                </a></li>
                <li><a href="messages.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Messages
                    <?php if ($total_messages > 0): ?><span class="badge"><?php echo $total_messages; ?></span><?php endif; ?>
                </a></li>
                <li><a href="seller-messages.php" class="active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>
                    Message Center
                </a></li>
            </ul>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-label">Account</div>
            <ul class="sidebar-menu">
                <li><a href="profile-settings.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg>
                    Profile Settings
                </a></li>
                <li><a href="settings.php">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Settings
                </a></li>
                <li><a href="logout.php" class="logout-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="mc-header">
            <h1>Message Center</h1>
            <p>View and manage all your property inquiries and messages</p>
        </div>

        <div class="mc-tabs">
            <a href="?filter=all" class="mc-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                All <span class="count"><?php echo $count_all; ?></span>
            </a>
            <a href="?filter=unread" class="mc-tab <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                Unread <span class="count"><?php echo $count_unread; ?></span>
            </a>
            <a href="?filter=read" class="mc-tab <?php echo $filter === 'read' ? 'active' : ''; ?>">
                Read <span class="count"><?php echo $count_read; ?></span>
            </a>
        </div>

        <?php if ($messages_result && mysqli_num_rows($messages_result) > 0): $i = 0; ?>
            <?php while ($msg = mysqli_fetch_assoc($messages_result)):
                $i++;
                $avatar_color = $avatar_palette[$i % count($avatar_palette)];
                $pic = safe_profile_pic($msg['sender_pic']);
                $is_unread = ($msg['receiver_id'] == $user_id && $msg['is_read'] == 0);
                $other_party_id = ($msg['sender_id'] == $user_id) ? $msg['receiver_id'] : $msg['sender_id'];
            ?>
                <div class="mc-card <?php echo $is_unread ? 'unread' : ''; ?>">
                    <div class="mc-card-top">
                        <div class="mc-sender">
                            <div class="mc-avatar" style="background: <?php echo $avatar_color; ?>;">
                                <?php if ($pic): ?>
                                    <img src="<?php echo htmlspecialchars($pic); ?>" alt="">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($msg['sender_name'], 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="mc-sender-info">
                                <h4><?php echo htmlspecialchars($msg['sender_name']); ?></h4>
                                <p><?php echo htmlspecialchars($msg['sender_email']); ?></p>
                            </div>
                        </div>
                        <div class="mc-time">
                            <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($msg['created_at'])); ?>
                        </div>
                    </div>

                    <div class="mc-body"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>

                    <?php if (!empty($msg['property_title'])): ?>
                        <div class="mc-property-tag">
                            <i class="fas fa-home"></i> Regarding: <?php echo htmlspecialchars($msg['property_title']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="mc-actions">
                        <a href="messages.php?conversation=<?php echo $other_party_id; ?><?php echo !empty($msg['prop_id']) ? '&property_id=' . $msg['prop_id'] : ''; ?>&mark_read=<?php echo $msg['id']; ?>"
                           onclick="fetch('seller-messages.php?mark_read=<?php echo $msg['id']; ?>')"
                           class="mc-btn mc-btn-reply">
                            <i class="fas fa-reply"></i> Reply
                        </a>
                        <a href="?delete_msg=<?php echo $msg['id']; ?>&filter=<?php echo htmlspecialchars($filter); ?>"
                           onclick="return confirm('Delete this message?');"
                           class="mc-btn mc-btn-delete">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="mc-empty">
                <i class="fas fa-inbox"></i>
                <p style="font-weight: 700; color: #0B1A2E; font-size: 16px;">No messages here</p>
                <p style="margin-top: 6px;">Messages from buyers about your properties will show up here.</p>
            </div>
        <?php endif; ?>

    </main>

</div>

</body>
</html>
