<?php
session_start();
require_once __DIR__ . '/includes/config.php';
// ================================================================
// TIME AGO FUNCTION
// ================================================================
function time_ago($datetime) {
    if (empty($datetime)) return 'Just now';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 172800) return 'Yesterday';
    return floor($diff / 86400) . ' days ago';
}
// ================================================================

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'seller') {
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION['user_id'];
$seller_name = $_SESSION['user_name'] ?? 'Seller';

// Unread messages count
$unread_count = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) as total FROM messages WHERE receiver_id = $seller_id AND is_read = 0");
if ($result) $unread_count = (int)mysqli_fetch_assoc($result)['total'];

// Fetch conversations
$conversations = [];
$query = "
    SELECT 
        m.sender_id,
        m.property_id,
        u.id as user_id,
        u.full_name,
        u.email,
        u.phone,
        u.profile_pic,
        p.id as prop_id,
        p.title as prop_title,
        p.location as prop_location,
        p.price as prop_price,
        (SELECT COUNT(*) FROM messages WHERE sender_id = m.sender_id AND receiver_id = $seller_id AND property_id = m.property_id AND is_read = 0) as unread_count,
        (SELECT message FROM messages WHERE sender_id = m.sender_id AND receiver_id = $seller_id AND property_id = m.property_id ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages WHERE sender_id = m.sender_id AND receiver_id = $seller_id AND property_id = m.property_id ORDER BY created_at DESC LIMIT 1) as last_time
    FROM messages m
    JOIN users u ON u.id = m.sender_id
    LEFT JOIN properties p ON p.id = m.property_id
    WHERE m.receiver_id = $seller_id
    GROUP BY m.sender_id, m.property_id
    ORDER BY last_time DESC
";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $conversations[] = $row;
    }
}

// Selected conversation
$selected_conversation = null;
$selected_sender_id = isset($_GET['sender']) ? (int)$_GET['sender'] : 0;
$selected_property_id = isset($_GET['property']) ? (int)$_GET['property'] : 0;
$messages_thread = [];

if ($selected_sender_id && $selected_property_id) {
    $msg_query = "
        SELECT m.*, u.full_name as sender_name, u.profile_pic as sender_pic
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id = $selected_sender_id AND m.receiver_id = $seller_id AND m.property_id = $selected_property_id)
           OR (m.sender_id = $seller_id AND m.receiver_id = $selected_sender_id AND m.property_id = $selected_property_id)
        ORDER BY m.created_at ASC
    ";
    $msg_result = mysqli_query($conn, $msg_query);
    if ($msg_result) {
        while ($row = mysqli_fetch_assoc($msg_result)) {
            $messages_thread[] = $row;
        }
    }
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE sender_id = $selected_sender_id AND receiver_id = $seller_id AND property_id = $selected_property_id");
    
    $conv_query = "
        SELECT u.id, u.full_name, u.email, u.phone, u.profile_pic,
               p.id as prop_id, p.title as prop_title, p.location as prop_location, p.price as prop_price,
               (SELECT COUNT(*) FROM messages WHERE sender_id = $selected_sender_id AND receiver_id = $seller_id AND property_id = $selected_property_id AND is_read = 0) as unread_count
        FROM users u
        LEFT JOIN properties p ON p.id = $selected_property_id
        WHERE u.id = $selected_sender_id
    ";
    $conv_result = mysqli_query($conn, $conv_query);
    if ($conv_result) {
        $selected_conversation = mysqli_fetch_assoc($conv_result);
    }
}

$page_title = 'Messages';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ===== RESET & BASE ===== */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter', sans-serif; }
        body { background:#f4f6f5; min-height:100vh; }

        .dashboard-wrapper { display:flex; min-height:100vh; background:#f4f6f5; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 250px;
            min-width: 250px;
            background: #000000;
            padding: 24px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
            border-right: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar::-webkit-scrollbar { width:4px; }
        .sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.12); border-radius:10px; }

        .logo {
            padding:0 4px 20px;
            margin-bottom:20px;
            border-bottom:1px solid rgba(255,255,255,0.06);
        }
        .logo a {
            display:flex;
            align-items:center;
            gap:10px;
            text-decoration:none;
        }
        .logo-icon {
            font-size:30px;
            color:#0E7A4E;
            filter:drop-shadow(0 2px 6px rgba(14,122,78,0.3));
        }
        .logo-text {
            font-size:29px;
            font-weight:800;
            letter-spacing:-0.5px;
            color:#ffffff;
            position:relative;
        }
        .logo-text::after {
            content:'';
            position:absolute;
            left:0;
            bottom:-10px;
            width:45px;
            height:3px;
            background:#0E7A4E;
            border-radius:10px;
        }

        .sidebar-label {
            font-size: 11px;
            font-weight: 600;
            color: rgba(255,255,255,0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 8px 12px;
            margin-bottom: 6px;
        }
        .sidebar-menu { list-style:none; padding:0; margin-bottom:12px; }
        .sidebar-menu li { margin-bottom:6px; }

        .sidebar-menu a {
            display:flex;
            align-items:center;
            gap:12px;
            padding:12px 16px;
            width:fit-content;
            min-width:165px;    
            color:#b8c2cc;
            text-decoration:none;
            border-radius:12px;
            font-size:14px;
            font-weight:600;
            transition:all .3s ease;
        }

        .sidebar-menu a:hover {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            transform:translateX(6px);
            box-shadow:0 8px 20px rgba(14,122,78,.35);
        }

        .sidebar-menu a svg {
            width:20px;
            height:20px;
            transition:.3s;
        }

        .sidebar-menu a:hover svg {
            transform:scale(1.15);
        }

        .sidebar-menu a.active {
            background:linear-gradient(135deg,#0E7A4E,#16a34a);
            color:#fff;
            box-shadow:0 8px 20px rgba(14,122,78,.35);
        }
        .sidebar-menu a.active svg {
            transform:scale(1.1);
        }

        .badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            border-radius: 50px;
            padding: 2px 10px;
            font-size: 10px;
            font-weight: 700;
            min-width: 22px;
            text-align: center;
        }

        .logout-link {
            color:#b8c2cc !important;
        }
        .logout-link:hover {
            background:linear-gradient(135deg,#dc2626,#ef4444) !important;
            color:#fff !important;
            box-shadow:0 8px 20px rgba(220,38,38,.35) !important;
        }

        /* ===== TOP BAR ===== */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 14px 32px;
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            position: sticky;
            top:0;
            z-index:50;
        }
        .topbar-menu-btn { display:none; background:none; border:none; cursor:pointer; padding:6px; }
        .topbar-search { flex:1; max-width:500px; position:relative; }
        .topbar-search input {
            width:100%;
            padding:9px 40px 9px 16px;
            border-radius:10px;
            border:1px solid #e9ecef;
            background:#f8fafc;
            font-size:14px;
            outline:none;
            transition:0.2s;
        }
        .topbar-search input:focus { border-color:#0E7A4E; background:#fff; }
        .topbar-search svg { position:absolute; right:14px; top:50%; transform:translateY(-50%); width:18px; height:18px; color:#adb5bd; }

        .topbar-actions { display:flex; align-items:center; gap:16px; }
        .icon-btn {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #f8fafc;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition:0.2s;
            color:#1e293b;
        }
        .icon-btn:hover { background:#e9ecef; }
        .icon-btn svg { width:20px; height:20px; stroke:currentColor; fill:none; stroke-width:2; }
        .icon-btn .count {
            position:absolute; top:-4px; right:-4px;
            background:#ef4444; color:#fff;
            font-size:10px; font-weight:700;
            min-width:18px; height:18px; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            border:2px solid #fff;
        }
        .user-chip { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .user-avatar {
            width:36px; height:36px; border-radius:10px;
            background:#0E7A4E; color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-weight:700; font-size:15px; overflow:hidden;
            flex-shrink:0;
        }
        .user-avatar img { width:100%; height:100%; object-fit:cover; }
        .user-info { display:flex; flex-direction:column; line-height:1.2; }
        .user-name { font-size:14px; font-weight:700; color:#0b1a2e; }
        .user-role { font-size:12px; color:#6b7a8f; }

        /* ===== MAIN CONTENT ===== */
        .main-content { flex:1; overflow:hidden; display:flex; flex-direction:column; }
        .content-inner { flex:1; padding:24px 32px 32px; overflow:hidden; display:flex; flex-direction:column; }

        /* ===== MESSAGES LAYOUT ===== */
        .messages-container {
            display:flex;
            flex:1;
            gap:20px;
            min-height:0;
            height:100%;
        }

        /* Left Panel - Conversation List */
        .conversation-list {
            width:360px;
            min-width:360px;
            background:#fff;
            border-radius:18px;
            border:1px solid #edf2f7;
            display:flex;
            flex-direction:column;
            overflow:hidden;
        }
        .conversation-list .header {
            padding:16px 20px;
            border-bottom:1px solid #edf2f7;
        }
        .conversation-list .header h2 {
            font-size:18px;
            font-weight:800;
        }
        .conversation-list .search-box {
            padding:12px 16px;
            border-bottom:1px solid #edf2f7;
        }
        .conversation-list .search-box input {
            width:100%;
            padding:8px 14px;
            border:1px solid #e9ecef;
            border-radius:8px;
            font-size:13px;
            outline:none;
        }
        .conversation-list .search-box input:focus {
            border-color:#0E7A4E;
        }
        .conversation-items {
            flex:1;
            overflow-y:auto;
            padding:4px 0;
        }
        .conversation-items::-webkit-scrollbar { width:4px; }
        .conversation-items::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:10px; }

        .conversation-item {
            display:flex;
            align-items:center;
            padding:12px 16px;
            cursor:pointer;
            transition:0.2s;
            border-left:3px solid transparent;
            gap:12px;
        }
        .conversation-item:hover {
            background:#f8fafc;
        }
        .conversation-item.active {
            background:#ecfdf5;
            border-left-color:#0E7A4E;
        }
        .conversation-item .avatar {
            width:44px;
            height:44px;
            border-radius:50%;
            background:#e9ecef;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:16px;
            color:#0E7A4E;
            flex-shrink:0;
            overflow:hidden;
        }
        .conversation-item .avatar img {
            width:100%; height:100%; object-fit:cover;
        }
        .conversation-item .details {
            flex:1;
            min-width:0;
        }
        .conversation-item .details .name {
            font-size:14px;
            font-weight:700;
            color:#0b1a2e;
        }
        .conversation-item .details .last-msg {
            font-size:13px;
            color:#64748b;
            white-space:nowrap;
            overflow:hidden;
            text-overflow:ellipsis;
        }
        .conversation-item .details .property-title {
            font-size:11px;
            color:#94a3b8;
            margin-top:2px;
        }
        .conversation-item .right {
            text-align:right;
            flex-shrink:0;
        }
        .conversation-item .right .time {
            font-size:11px;
            color:#94a3b8;
        }
        .conversation-item .right .unread {
            display:inline-block;
            background:#ef4444;
            color:#fff;
            font-size:10px;
            font-weight:700;
            min-width:20px;
            height:20px;
            border-radius:50%;
            text-align:center;
            line-height:20px;
            padding:0 6px;
            margin-top:4px;
        }

        /* Right Panel - Conversation View */
        .conversation-view {
            flex:1;
            background:#fff;
            border-radius:18px;
            border:1px solid #edf2f7;
            display:flex;
            flex-direction:column;
            overflow:hidden;
            min-width:0;
        }
        .conversation-view .view-header {
            padding:16px 24px;
            border-bottom:1px solid #edf2f7;
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:12px;
        }
        .view-header .contact-info {
            display:flex;
            align-items:center;
            gap:12px;
        }
        .view-header .contact-info .avatar {
            width:40px;
            height:40px;
            border-radius:50%;
            background:#e9ecef;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:700;
            font-size:16px;
            color:#0E7A4E;
            overflow:hidden;
            flex-shrink:0;
        }
        .view-header .contact-info .avatar img {
            width:100%; height:100%; object-fit:cover;
        }
        .view-header .contact-info .name {
            font-size:16px;
            font-weight:700;
        }
        .view-header .contact-info .email {
            font-size:12px;
            color:#64748b;
        }
        .view-header .actions {
            display:flex;
            gap:8px;
        }
        .view-header .actions button {
            background:none;
            border:none;
            cursor:pointer;
            font-size:16px;
            padding:6px 8px;
            border-radius:8px;
            transition:0.2s;
            color:#64748b;
        }
        .view-header .actions button:hover {
            background:#f1f5f9;
            color:#0b1a2e;
        }
        .view-header .actions .delete-conv {
            color:#ef4444;
        }
        .view-header .actions .delete-conv:hover {
            background:#fee2e2;
        }

        /* Property Info Bar */
        .property-info-bar {
            padding:12px 24px;
            background:#f8fafc;
            border-bottom:1px solid #edf2f7;
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:8px;
        }
        .property-info-bar .prop-details {
            display:flex;
            align-items:center;
            gap:12px;
            font-size:13px;
        }
        .property-info-bar .prop-details strong {
            color:#0b1a2e;
        }
        .property-info-bar .prop-details .price {
            color:#0E7A4E;
            font-weight:700;
        }

        /* Messages Thread */
        .messages-thread {
            flex:1;
            overflow-y:auto;
            padding:16px 24px;
            display:flex;
            flex-direction:column;
            gap:8px;
        }
        .messages-thread::-webkit-scrollbar { width:4px; }
        .messages-thread::-webkit-scrollbar-thumb { background:#d1d5db; border-radius:10px; }

        .message-item {
            display:flex;
            flex-direction:column;
            max-width:75%;
            padding:10px 16px;
            border-radius:12px;
            background:#f1f5f9;
            align-self:flex-start;
            position:relative;
        }
        .message-item.sent {
            background:#0E7A4E;
            color:#fff;
            align-self:flex-end;
        }
        .message-item .msg-text {
            font-size:14px;
            line-height:1.5;
            word-wrap:break-word;
        }
        .message-item .msg-time {
            font-size:10px;
            color:#94a3b8;
            margin-top:4px;
            align-self:flex-end;
        }
        .message-item.sent .msg-time {
            color:#d1fae5;
        }
        .message-item .msg-actions {
            position:absolute;
            top:4px;
            right:4px;
            display:none;
            gap:4px;
        }
        .message-item:hover .msg-actions {
            display:flex;
        }
        .message-item .msg-actions button {
            background:rgba(255,255,255,0.8);
            border:none;
            border-radius:4px;
            padding:2px 6px;
            cursor:pointer;
            font-size:10px;
            color:#475569;
        }
        .message-item .msg-actions button:hover {
            background:#fff;
            color:#0b1a2e;
        }
        .message-item.sent .msg-actions button {
            background:rgba(0,0,0,0.2);
            color:#fff;
        }
        .message-item.sent .msg-actions button:hover {
            background:rgba(0,0,0,0.4);
        }

        /* Message Input */
        .message-input {
            padding:12px 24px 20px;
            border-top:1px solid #edf2f7;
            display:flex;
            gap:12px;
            align-items:center;
        }
        .message-input textarea {
            flex:1;
            padding:10px 14px;
            border:1px solid #e9ecef;
            border-radius:10px;
            font-size:14px;
            resize:none;
            height:44px;
            outline:none;
            transition:0.2s;
        }
        .message-input textarea:focus {
            border-color:#0E7A4E;
            box-shadow:0 0 0 3px rgba(14,122,78,0.08);
        }
        .message-input button {
            background:#0E7A4E;
            color:#fff;
            border:none;
            border-radius:10px;
            padding:10px 20px;
            font-weight:700;
            cursor:pointer;
            transition:0.2s;
            height:44px;
            display:flex;
            align-items:center;
            gap:8px;
        }
        .message-input button:hover {
            background:#0a5c3a;
            transform:translateY(-2px);
            box-shadow:0 6px 20px rgba(14,122,78,0.25);
        }

        /* Empty state */
        .empty-state {
            flex:1;
            display:flex;
            flex-direction:column;
            align-items:center;
            justify-content:center;
            color:#94a3b8;
            text-align:center;
            padding:40px;
        }
        .empty-state i {
            font-size:48px;
            margin-bottom:16px;
            color:#cbd5e1;
        }
        .empty-state h3 {
            font-size:18px;
            color:#475569;
            margin-bottom:8px;
        }
        .empty-state p {
            font-size:14px;
        }

        /* Modal for delete confirmation */
        .modal-overlay {
            position:fixed;
            top:0; left:0; right:0; bottom:0;
            background:rgba(0,0,0,0.5);
            backdrop-filter:blur(4px);
            z-index:1000;
            display:none;
            align-items:center;
            justify-content:center;
        }
        .modal-overlay.active { display:flex; }
        .modal {
            background:#fff;
            border-radius:20px;
            padding:28px 32px;
            max-width:420px;
            width:90%;
            text-align:center;
        }
        .modal h3 {
            font-size:20px;
            font-weight:800;
            margin-bottom:12px;
        }
        .modal p {
            color:#64748b;
            margin-bottom:24px;
        }
        .modal .actions {
            display:flex;
            gap:12px;
            justify-content:center;
        }
        .modal .actions button {
            padding:10px 24px;
            border:none;
            border-radius:10px;
            font-weight:700;
            cursor:pointer;
            transition:0.2s;
        }
        .modal .actions .btn-cancel {
            background:#f1f5f9;
            color:#475569;
        }
        .modal .actions .btn-cancel:hover {
            background:#e2e8f0;
        }
        .modal .actions .btn-danger {
            background:#ef4444;
            color:#fff;
        }
        .modal .actions .btn-danger:hover {
            background:#dc2626;
        }

        @media (max-width:1100px) {
            .sidebar { position:fixed; left:-280px; transition:left 0.3s; }
            .sidebar.open { left:0; }
            .topbar-menu-btn { display:flex; }
            .conversation-list { width:300px; min-width:300px; }
        }
        @media (max-width:768px) {
            .messages-container { flex-direction:column; }
            .conversation-list { width:100%; min-width:0; height:300px; }
            .conversation-view { flex:1; min-height:400px; }
            .content-inner { padding:12px; }
            .topbar { padding:12px 18px; }
            .user-info { display:none; }
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sellerSidebar">
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-home logo-icon"></i>
                <span class="logo-text">EstateHub</span>
            </a>
        </div>
        <div class="sidebar-label">Main Menu</div>
        <ul class="sidebar-menu">
            <li><a href="seller-dashboard.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg> Dashboard</a></li>
            <li><a href="my-properties.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg> My Properties</a></li>
            <li><a href="add-property.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg> Add Property</a></li>
            <li><a href="messages.php" class="active"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Messages <?php if($unread_count > 0): ?><span class="badge"><?php echo $unread_count; ?></span><?php endif; ?></a></li>
            <li><a href="wishlist.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg> Wishlist</a></li>
        </ul>
        <div class="sidebar-label">Account</div>
        <ul class="sidebar-menu">
            <li><a href="profile-settings.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M5 20v-2a7 7 0 0 1 14 0v2"/></svg> Profile Settings</a></li>
            <li><a href="settings.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> Settings</a></li>
            <li><a href="logout.php" class="logout-link"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Logout</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <button class="topbar-menu-btn" onclick="document.getElementById('sellerSidebar').classList.toggle('open')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="topbar-search">
                <input type="text" placeholder="Search anything...">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
            <div class="topbar-actions">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if($unread_count > 0): ?>
                        <span class="count"><?php echo $unread_count > 9 ? '9+' : $unread_count; ?></span>
                    <?php endif; ?>
                </button>
                <a href="profile-settings.php" class="user-chip">
                    <div class="user-avatar">
                        <?php
                        $seller_avatar = '';
                        $pic_query = mysqli_query($conn, "SELECT profile_pic FROM users WHERE id = $seller_id");
                        if ($pic_query && $row = mysqli_fetch_assoc($pic_query)) {
                            if (!empty($row['profile_pic']) && file_exists("uploads/profiles/".$row['profile_pic'])) {
                                $seller_avatar = "uploads/profiles/".$row['profile_pic'];
                            }
                        }
                        if(!empty($seller_avatar)): ?>
                            <img src="<?php echo $seller_avatar; ?>" alt="<?php echo htmlspecialchars($seller_name); ?>">
                        <?php else: ?>
                            <?php echo strtoupper(substr($seller_name, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($seller_name); ?></span>
                        <span class="user-role">Seller</span>
                    </div>
                </a>
            </div>
        </header>

        <div class="content-inner">

            <!-- ===== MESSAGES CONTAINER ===== -->
            <div class="messages-container">

                <!-- LEFT PANEL - Conversation List -->
                <div class="conversation-list">
                    <div class="header">
                        <h2>Messages</h2>
                    </div>
                    <div class="search-box">
                        <input type="text" id="searchConv" placeholder="Search messages..." onkeyup="filterConversations()">
                    </div>
                    <div class="conversation-items" id="convList">
                        <?php if (empty($conversations)): ?>
                            <div style="padding:40px 20px; text-align:center; color:#94a3b8;">
                                <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:12px;"></i>
                                <p>No conversations yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $conv):
                                $is_active = ($selected_sender_id == $conv['sender_id'] && $selected_property_id == $conv['property_id']);
                                $avatar = !empty($conv['profile_pic']) && file_exists("uploads/profiles/".$conv['profile_pic']) ? "uploads/profiles/".$conv['profile_pic'] : '';
                                $initials = strtoupper(substr($conv['full_name'], 0, 1));
                                $time = time_ago($conv['last_time']);
                                $unread = (int)$conv['unread_count'];
                            ?>
                            <div class="conversation-item <?php echo $is_active ? 'active' : ''; ?>" 
                                 data-sender="<?php echo $conv['sender_id']; ?>" 
                                 data-property="<?php echo $conv['property_id']; ?>"
                                 onclick="selectConversation(<?php echo $conv['sender_id']; ?>, <?php echo $conv['property_id']; ?>)">
                                <div class="avatar">
                                    <?php if ($avatar): ?>
                                        <img src="<?php echo $avatar; ?>" alt="">
                                    <?php else: ?>
                                        <?php echo $initials; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="details">
                                    <div class="name"><?php echo htmlspecialchars($conv['full_name']); ?></div>
                                    <div class="last-msg"><?php echo htmlspecialchars(substr($conv['last_message'], 0, 60)); ?></div>
                                    <div class="property-title"><?php echo htmlspecialchars($conv['prop_title'] ?? 'Property'); ?></div>
                                </div>
                                <div class="right">
                                    <div class="time"><?php echo $time; ?></div>
                                    <?php if ($unread > 0): ?>
                                        <div class="unread"><?php echo $unread; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT PANEL - Conversation View -->
                <div class="conversation-view" id="conversationView">
                    <?php if ($selected_conversation && !empty($messages_thread)): ?>
                        <!-- Header with contact info -->
                        <div class="view-header">
                            <div class="contact-info">
                                <div class="avatar">
                                    <?php 
                                        $avatar = !empty($selected_conversation['profile_pic']) && file_exists("uploads/profiles/".$selected_conversation['profile_pic']) 
                                            ? "uploads/profiles/".$selected_conversation['profile_pic'] 
                                            : '';
                                        $initials = strtoupper(substr($selected_conversation['full_name'], 0, 1));
                                    ?>
                                    <?php if ($avatar): ?>
                                        <img src="<?php echo $avatar; ?>" alt="">
                                    <?php else: ?>
                                        <?php echo $initials; ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="name"><?php echo htmlspecialchars($selected_conversation['full_name']); ?></div>
                                    <div class="email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($selected_conversation['email']); ?></div>
                                </div>
                            </div>
                            <div class="actions">
                                <button onclick="deleteConversation(<?php echo $selected_sender_id; ?>, <?php echo $selected_property_id; ?>)" title="Delete Conversation" class="delete-conv">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Property Info -->
                        <?php if ($selected_conversation['prop_id']): ?>
                        <div class="property-info-bar">
                            <div class="prop-details">
                                <i class="fas fa-home" style="color:#0E7A4E;"></i>
                                <strong><?php echo htmlspecialchars($selected_conversation['prop_title']); ?></strong>
                                <span><?php echo htmlspecialchars($selected_conversation['prop_location']); ?></span>
                            </div>
                            <div class="price">PKR <?php echo number_format($selected_conversation['prop_price']); ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Messages Thread -->
                        <div class="messages-thread" id="messageThread">
                            <?php foreach ($messages_thread as $msg):
                                $is_sent = ($msg['sender_id'] == $seller_id);
                                $time = date('g:i A', strtotime($msg['created_at']));
                            ?>
                            <div class="message-item <?php echo $is_sent ? 'sent' : ''; ?>" data-msgid="<?php echo $msg['id']; ?>">
                                <div class="msg-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                <div class="msg-time"><?php echo $time; ?></div>
                                <?php if ($is_sent): ?>
                                <div class="msg-actions">
                                    <button onclick="editMessage(<?php echo $msg['id']; ?>, this)" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button onclick="deleteMessage(<?php echo $msg['id']; ?>)" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <div id="scrollAnchor"></div>
                        </div>

                        <!-- Message Input -->
                        <div class="message-input">
                            <textarea id="msgInput" rows="1" placeholder="Type a message..." onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); sendMessage();}"></textarea>
                            <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i> Send</button>
                        </div>
                    <?php else: ?>
                        <!-- Empty state -->
                        <div class="empty-state">
                            <i class="fas fa-comment-dots"></i>
                            <h3>Select a conversation</h3>
                            <p>Choose a conversation from the left to view messages</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Confirm Delete</h3>
        <p id="deleteMessage">Are you sure you want to delete this conversation? All messages will be permanently removed.</p>
        <div class="actions">
            <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-danger" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<script>
// ===== CONVERSATION SELECTION =====
function selectConversation(senderId, propertyId) {
    window.location.href = 'messages.php?sender=' + senderId + '&property=' + propertyId;
}

// ===== FILTER CONVERSATIONS =====
function filterConversations() {
    const search = document.getElementById('searchConv').value.toLowerCase();
    const items = document.querySelectorAll('.conversation-item');
    items.forEach(item => {
        const name = item.querySelector('.name').textContent.toLowerCase();
        const lastMsg = item.querySelector('.last-msg').textContent.toLowerCase();
        if (name.includes(search) || lastMsg.includes(search)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// ===== SEND MESSAGE =====
function sendMessage() {
    const input = document.getElementById('msgInput');
    const msg = input.value.trim();
    if (!msg) {
        alert('Please type a message.');
        return;
    }

    const urlParams = new URLSearchParams(window.location.search);
    const senderId = urlParams.get('sender');
    const propertyId = urlParams.get('property');

    if (!senderId || !propertyId) {
        alert('Please select a conversation first.');
        return;
    }

    const formData = new FormData();
    formData.append('send_message', '1');
    formData.append('receiver_id', senderId);
    formData.append('property_id', propertyId);
    formData.append('message', msg);

    fetch('ajax-messages.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const thread = document.getElementById('messageThread');
            const msgDiv = document.createElement('div');
            msgDiv.className = 'message-item sent';
            msgDiv.dataset.msgid = data.msg_id;
            const now = new Date();
            const time = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            msgDiv.innerHTML = `
                <div class="msg-text">${escapeHtml(msg)}</div>
                <div class="msg-time">${time}</div>
                <div class="msg-actions">
                    <button onclick="editMessage(${data.msg_id}, this)"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteMessage(${data.msg_id})"><i class="fas fa-trash-alt"></i></button>
                </div>
            `;
            thread.appendChild(msgDiv);
            document.getElementById('scrollAnchor').scrollIntoView({ behavior: 'smooth' });
            input.value = '';
            input.style.height = '44px';
            updateConversationLastMsg(senderId, propertyId, msg);
        } else {
            alert(data.error || 'Failed to send message.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while sending the message.');
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateConversationLastMsg(senderId, propertyId, msg) {
    const items = document.querySelectorAll('.conversation-item');
    items.forEach(item => {
        if (item.dataset.sender == senderId && item.dataset.property == propertyId) {
            const lastMsg = item.querySelector('.last-msg');
            if (lastMsg) lastMsg.textContent = msg.substring(0, 60);
            const time = item.querySelector('.time');
            if (time) time.textContent = 'Just now';
        }
    });
}

// ===== DELETE CONVERSATION =====
let deleteTarget = null;
function deleteConversation(senderId, propertyId) {
    deleteTarget = { sender: senderId, property: propertyId };
    document.getElementById('deleteMessage').textContent = 'Are you sure you want to delete this entire conversation? All messages will be permanently removed.';
    document.getElementById('confirmDeleteBtn').onclick = function() {
        performDeleteConversation(senderId, propertyId);
    };
    document.getElementById('deleteModal').classList.add('active');
}

function performDeleteConversation(senderId, propertyId) {
    fetch('ajax-messages.php?delete_conversation=1&sender=' + senderId + '&property=' + propertyId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove from list
            const items = document.querySelectorAll('.conversation-item');
            items.forEach(item => {
                if (item.dataset.sender == senderId && item.dataset.property == propertyId) {
                    item.remove();
                }
            });
            // If no items left, show empty state
            const list = document.getElementById('convList');
            if (list.querySelectorAll('.conversation-item').length === 0) {
                list.innerHTML = `
                    <div style="padding:40px 20px; text-align:center; color:#94a3b8;">
                        <i class="fas fa-inbox" style="font-size:32px; display:block; margin-bottom:12px;"></i>
                        <p>No conversations yet</p>
                    </div>
                `;
            }
            // Redirect to clear selected conversation
            window.location.href = 'messages.php';
        } else {
            alert(data.error || 'Failed to delete conversation.');
        }
        closeDeleteModal();
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred.');
        closeDeleteModal();
    });
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteTarget = null;
}

// ===== DELETE INDIVIDUAL MESSAGE =====
function deleteMessage(msgId) {
    if (!confirm('Delete this message?')) return;
    fetch('ajax-messages.php?delete_message=1&msg_id=' + msgId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const msgEl = document.querySelector(`.message-item[data-msgid="${msgId}"]`);
            if (msgEl) msgEl.remove();
        } else {
            alert(data.error || 'Failed to delete message.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred.');
    });
}

// ===== EDIT MESSAGE =====
function editMessage(msgId, btn) {
    const msgItem = btn.closest('.message-item');
    const msgText = msgItem.querySelector('.msg-text');
    const currentText = msgText.textContent;
    const input = document.createElement('textarea');
    input.value = currentText;
    input.style.width = '100%';
    input.style.padding = '8px';
    input.style.border = '1px solid #0E7A4E';
    input.style.borderRadius = '8px';
    input.style.fontSize = '14px';
    input.style.resize = 'vertical';
    msgText.innerHTML = '';
    msgText.appendChild(input);

    const actions = msgItem.querySelector('.msg-actions');
    actions.innerHTML = `
        <button onclick="saveEditMessage(${msgId}, this)" style="background:#0E7A4E;color:#fff;"><i class="fas fa-save"></i></button>
        <button onclick="cancelEditMessage(this)" style="background:#e9ecef;"><i class="fas fa-times"></i></button>
    `;
    input.focus();
}

function saveEditMessage(msgId, btn) {
    const msgItem = btn.closest('.message-item');
    const input = msgItem.querySelector('textarea');
    const newText = input.value.trim();
    if (!newText) return;

    fetch('ajax-messages.php?edit_message=1&msg_id=' + msgId + '&message=' + encodeURIComponent(newText), {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const msgText = msgItem.querySelector('.msg-text');
            msgText.textContent = newText;
            msgItem.querySelector('.msg-actions').innerHTML = `
                <button onclick="editMessage(${msgId}, this)"><i class="fas fa-edit"></i></button>
                <button onclick="deleteMessage(${msgId})"><i class="fas fa-trash-alt"></i></button>
            `;
        } else {
            alert(data.error || 'Failed to edit message.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred.');
    });
}

function cancelEditMessage(btn) {
    const msgItem = btn.closest('.message-item');
    const msgText = msgItem.querySelector('.msg-text');
    const originalText = msgText.textContent;
    msgText.textContent = originalText;
    const msgId = msgItem.dataset.msgid;
    msgItem.querySelector('.msg-actions').innerHTML = `
        <button onclick="editMessage(${msgId}, this)"><i class="fas fa-edit"></i></button>
        <button onclick="deleteMessage(${msgId})"><i class="fas fa-trash-alt"></i></button>
    `;
}

document.addEventListener('input', function(e) {
    if (e.target.id === 'msgInput') {
        e.target.style.height = '44px';
        e.target.style.height = e.target.scrollHeight + 'px';
    }
});

window.addEventListener('load', function() {
    const anchor = document.getElementById('scrollAnchor');
    if (anchor) anchor.scrollIntoView();
});
</script>

</body>
</html>