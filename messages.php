<?php
$page_title = 'Messages';
include 'includes/config.php';
include 'includes/header.php';

// Set charset to utf8mb4 for emoji support
mysqli_set_charset($conn, "utf8mb4");

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$selected_property_id  = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;
$selected_conversation = isset($_GET['conversation']) ? (int)$_GET['conversation'] : 0;

// Handle conversation deletion
if (isset($_GET['delete_conv']) && is_numeric($_GET['delete_conv'])) {
    $delete_user_id = (int)$_GET['delete_conv'];
    $query = "DELETE FROM messages WHERE (sender_id = $user_id AND receiver_id = $delete_user_id) OR (sender_id = $delete_user_id AND receiver_id = $user_id)";
    mysqli_query($conn, $query);
    header("Location: messages.php");
    exit();
}

// Handle message edit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_message'])) {
    $msg_id = (int)$_POST['edit_msg_id'];
    $new_text = mysqli_real_escape_string($conn, trim($_POST['message']));
    if ($msg_id > 0 && $new_text !== '') {
        $check = mysqli_query($conn, "SELECT sender_id FROM messages WHERE id = $msg_id");
        $row = mysqli_fetch_assoc($check);
        if ($row && $row['sender_id'] == $user_id) {
            mysqli_query($conn, "UPDATE messages SET message = '$new_text' WHERE id = $msg_id");
        }
    }
    $redirect_url = "messages.php?conversation=$selected_conversation";
    if ($selected_property_id > 0) $redirect_url .= "&property_id=$selected_property_id";
    header("Location: $redirect_url");
    exit();
}

// Handle message deletion
if (isset($_GET['delete_msg']) && is_numeric($_GET['delete_msg'])) {
    $msg_id = (int)$_GET['delete_msg'];
    $check = mysqli_query($conn, "SELECT sender_id FROM messages WHERE id = $msg_id");
    $row = mysqli_fetch_assoc($check);
    if ($row && $row['sender_id'] == $user_id) {
        mysqli_query($conn, "DELETE FROM messages WHERE id = $msg_id");
    }
    $redirect_url = "messages.php?conversation=$selected_conversation";
    if ($selected_property_id > 0) $redirect_url .= "&property_id=$selected_property_id";
    header("Location: $redirect_url");
    exit();
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id  = (int)$_POST['receiver_id'];
    $property_id  = (int)$_POST['property_id'];
    $message_text = mysqli_real_escape_string($conn, trim($_POST['message']));

    if ($receiver_id > 0 && $message_text !== '') {
        $property_id_sql = $property_id > 0 ? $property_id : 'NULL';
        $query = "INSERT INTO messages (sender_id, receiver_id, property_id, message, created_at)
                  VALUES ($user_id, $receiver_id, $property_id_sql, '$message_text', NOW())";
        mysqli_query($conn, $query);

        $redirect_url = "messages.php?conversation=$receiver_id";
        if ($property_id > 0) $redirect_url .= "&property_id=$property_id";
        header("Location: $redirect_url");
        exit();
    }
}

// Get all conversations for the current user
$conversations_query = "
    SELECT
        u.id as user_id,
        MAX(u.full_name) as full_name,
        MAX(u.profile_pic) as profile_pic,
        MAX(m.created_at) as last_message_time,
        (SELECT message FROM messages WHERE ((sender_id = $user_id AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = $user_id)) ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = $user_id AND sender_id = u.id AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = $user_id OR m.receiver_id = $user_id) AND u.id != $user_id
    GROUP BY u.id
    ORDER BY last_message_time DESC
";
$conversations_result = mysqli_query($conn, $conversations_query);

// Get messages for selected conversation
$conversation_user     = null;
$conversation_messages = [];
$property_info         = null;

if ($selected_conversation > 0) {
    $user_query  = "SELECT id, full_name, profile_pic, email, phone FROM users WHERE id = $selected_conversation";
    $user_result = mysqli_query($conn, $user_query);
    $conversation_user = mysqli_fetch_assoc($user_result);

    $messages_query = "
        SELECT m.*,
               u.full_name as sender_name,
               u.profile_pic as sender_pic,
               p.title as property_title,
               p.id as property_id
        FROM messages m
        LEFT JOIN users u ON m.sender_id = u.id
        LEFT JOIN properties p ON m.property_id = p.id
        WHERE (m.sender_id = $user_id AND m.receiver_id = $selected_conversation)
           OR (m.sender_id = $selected_conversation AND m.receiver_id = $user_id)
        ORDER BY m.created_at ASC
    ";
    $messages_result = mysqli_query($conn, $messages_query);
    while ($row = mysqli_fetch_assoc($messages_result)) {
        $conversation_messages[] = $row;
    }

    // Mark messages as read
    mysqli_query($conn, "UPDATE messages SET is_read = 1 WHERE receiver_id = $user_id AND sender_id = $selected_conversation");

    if ($selected_property_id > 0) {
        $prop_query    = "SELECT id, title, price, purpose, location, city, image, bedrooms, bathrooms FROM properties WHERE id = $selected_property_id";
        $prop_result   = mysqli_query($conn, $prop_query);
        $property_info = mysqli_fetch_assoc($prop_result);
    }
}

function time_ago_short($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Now';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('M j', strtotime($datetime));
}

function day_label($datetime) {
    $d = date('Y-m-d', strtotime($datetime));
    if ($d === date('Y-m-d')) return 'Today';
    if ($d === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
    return date('F j, Y', strtotime($datetime));
}

// Avatar color palette
$avatar_palette = ['#0E7A4E', '#0B5E3B', '#15803D', '#065F46', '#166534', '#14532D'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - EstateHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1.18.2/index.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --brand: #0E7A4E;
            --brand-dark: #0a5c3a;
            --brand-light: #EAF8F1;
            --brand-lighter: #F0FDF4;
            --gray-50: #F8FAFC;
            --gray-100: #F1F5F9;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1E293B;
            --gray-900: #0B1A2E;
            --shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.12);
            --radius: 16px;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #EDF2F7;
            -webkit-font-smoothing: antialiased;
        }

        /* Main Layout */
        .messages-section {
            height: calc(100vh - 70px);
            display: flex;
            flex-direction: column;
            background: #EDF2F7;
            padding: 20px 24px 20px 24px;
            overflow: hidden;
        }

        .messages-container {
            flex: 1;
            display: flex;
            background: #FFFFFF;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.03);
            min-height: 0;
            height: 100%;
        }

        /* --- Conversations Sidebar --- */
        .conversations-sidebar {
            width: 380px;
            min-width: 340px;
            background: #FFFFFF;
            border-right: 1px solid #EEF2F6;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 24px 24px 16px 24px;
            border-bottom: 1px solid #EEF2F6;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .sidebar-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: #0B1A2E;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-header .badge {
            background: #0E7A4E;
            color: white;
            font-size: 12px;
            font-weight: 700;
            padding: 2px 12px;
            border-radius: 20px;
            margin-left: 4px;
        }
        .filter-btn {
            background: #F1F5F9;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            background: #0E7A4E;
            color: white;
            transform: scale(1.02);
        }

        .conv-search {
            padding: 14px 20px 16px 20px;
            border-bottom: 1px solid #EEF2F6;
            flex-shrink: 0;
        }
        .conv-search-wrap {
            position: relative;
        }
        .conv-search-wrap i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 15px;
            pointer-events: none;
        }
        .conv-search input {
            width: 100%;
            padding: 12px 44px 12px 18px;
            border-radius: 30px;
            border: 2px solid #EEF2F6;
            background: #F8FAFC;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
            color: #0B1A2E;
        }
        .conv-search input::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        .conv-search input:focus {
            border-color: #0E7A4E;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(14, 122, 78, 0.08);
        }

        .conversations-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 10px 8px 10px;
        }
        .conversations-list::-webkit-scrollbar {
            width: 4px;
        }
        .conversations-list::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }
        .conversations-list::-webkit-scrollbar-track {
            background: transparent;
        }

        .conversation-item-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            padding: 2px 0;
        }

        .conversation-item {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            min-width: 0;
            position: relative;
        }
        .conversation-item:hover {
            background: #F8FAFC;
        }
        .conversation-item.active {
            background: #EAF8F1;
        }
        .conversation-item.active .conv-name {
            color: #0E7A4E;
        }

        .conversation-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 22px;
            flex-shrink: 0;
            overflow: hidden;
            position: relative;
        }
        .conversation-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .online-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 13px;
            height: 13px;
            background: #22C55E;
            border-radius: 50%;
            border: 2.5px solid white;
        }

        .conversation-info {
            flex: 1;
            min-width: 0;
        }
        .conv-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }
        .conv-name {
            font-weight: 700;
            color: #0B1A2E;
            font-size: 16px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .conv-time {
            font-size: 12px;
            color: #94A3B8;
            font-weight: 600;
            flex-shrink: 0;
        }
        .conv-bottom-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
        }
        .conv-last-msg {
            font-size: 14px;
            font-weight: 500;
            color: #64748B;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .conv-unread {
            background: #0E7A4E;
            color: white;
            font-size: 12px;
            font-weight: 700;
            min-width: 24px;
            height: 24px;
            padding: 0 10px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Three-dot menu */
        .conv-menu-btn {
            background: none;
            border: none;
            color: #94A3B8;
            font-size: 16px;
            padding: 6px 8px;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
            flex-shrink: 0;
            position: relative;
        }
        .conversation-item-wrapper:hover .conv-menu-btn {
            opacity: 1;
        }
        .conv-menu-btn:hover {
            background: #F1F5F9;
            color: #0B1A2E;
        }

        .conv-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
            border: 1px solid #EEF2F6;
            padding: 8px 0;
            min-width: 210px;
            display: none;
            z-index: 50;
            margin-top: 6px;
        }
        .conv-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .conv-dropdown button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            width: 100%;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 600;
            color: #0B1A2E;
            cursor: pointer;
            transition: background 0.15s;
            text-align: left;
            font-family: 'Inter', sans-serif;
        }
        .conv-dropdown button i {
            width: 20px;
            color: #64748B;
            font-size: 15px;
        }
        .conv-dropdown button:hover {
            background: #F8FAFC;
        }
        .conv-dropdown .danger-btn {
            color: #DC2626;
        }
        .conv-dropdown .danger-btn i {
            color: #DC2626;
        }
        .conv-dropdown .danger-btn:hover {
            background: #FEF2F2;
        }

        /* Sidebar footer */
        .sidebar-footer {
            flex-shrink: 0;
            padding: 16px 18px 20px 18px;
            border-top: 1px solid #EEF2F6;
        }
        .start-conv-card {
            background: #F8FAFC;
            border-radius: 18px;
            padding: 20px 22px;
            text-align: left;
            border: 1px solid #EEF2F6;
        }
        .start-conv-card h4 {
            font-size: 15px;
            font-weight: 800;
            color: #0B1A2E;
            margin-bottom: 4px;
        }
        .start-conv-card p {
            font-size: 13px;
            font-weight: 500;
            color: #64748B;
            line-height: 1.4;
            margin-bottom: 14px;
        }
        .browse-btn-sm {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: #0E7A4E;
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: 0.2s;
        }
        .browse-btn-sm:hover {
            background: #0a5c3a;
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 12px rgba(14, 122, 78, 0.3);
        }

        /* --- Chat Area --- */
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #FFFFFF;
            min-width: 0;
            height: 100%;
            overflow: hidden;
            position: relative;
        }

        /* Chat Header */
        .chat-header {
            padding: 18px 28px;
            border-bottom: 1px solid #EEF2F6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            background: #FFFFFF;
            gap: 12px;
        }
        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .chat-header-left .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 20px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        .chat-header-left .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .chat-user-info h4 {
            font-size: 18px;
            font-weight: 800;
            color: #0B1A2E;
            letter-spacing: -0.3px;
        }
        .chat-user-info .status {
            font-size: 13px;
            font-weight: 600;
            color: #22C55E;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .chat-user-info .status .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22C55E;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .chat-header-right {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .chat-header-right button {
            background: none;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748B;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .chat-header-right button:hover {
            background: #F1F5F9;
            color: #0E7A4E;
        }

        /* Property Preview */
        .property-preview {
            background: #F8FAFC;
            margin: 12px 24px 0 24px;
            padding: 14px 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid #EEF2F6;
            flex-shrink: 0;
        }
        .property-preview img {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            background: #E5E7EB;
        }
        .property-preview-info {
            flex: 1;
            min-width: 0;
        }
        .property-preview-info h5 {
            font-size: 16px;
            font-weight: 700;
            color: #0B1A2E;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .property-preview-info .details {
            font-size: 13px;
            color: #64748B;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 4px;
            font-weight: 500;
        }
        .property-preview-info .details span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .property-preview-info .price {
            font-size: 15px;
            font-weight: 800;
            color: #0E7A4E;
        }
        .view-prop-btn {
            background: white;
            color: #0E7A4E;
            border: 2px solid #0E7A4E;
            padding: 8px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            transition: 0.2s;
            flex-shrink: 0;
        }
        .view-prop-btn:hover {
            background: #0E7A4E;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(14, 122, 78, 0.25);
        }

        /* Messages List */
        .messages-list {
            flex: 1;
            overflow-y: auto;
            padding: 20px 28px 10px;
            display: flex;
            flex-direction: column;
            background: #FFFFFF;
        }
        .messages-list::-webkit-scrollbar {
            width: 4px;
        }
        .messages-list::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }

        .day-divider {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            color: #64748B;
            margin: 8px 0 20px 0;
            padding: 6px 22px;
            background: #F1F5F9;
            border-radius: 20px;
            align-self: center;
        }

        .message-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            max-width: 75%;
            margin-bottom: 14px;
            position: relative;
        }
        .message-row.sent {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .message-row.received {
            align-self: flex-start;
        }

        .msg-avatar-sm {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #E5E7EB;
            color: #64748B;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
            overflow: hidden;
        }
        .msg-avatar-sm img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .message-content {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .message-row.sent .message-content {
            align-items: flex-end;
        }
        .message-row.received .message-content {
            align-items: flex-start;
        }

        .message-bubble-wrapper {
            position: relative;
            display: flex;
            align-items: flex-end;
            gap: 6px;
        }
        .message-bubble {
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 15.5px;
            line-height: 1.55;
            word-wrap: break-word;
            min-width: 44px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            font-weight: 500;
            max-width: 100%;
        }
        .message-row.sent .message-bubble {
            background: #0E7A4E;
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message-row.received .message-bubble {
            background: #F1F5F9;
            color: #0B1A2E;
            border-bottom-left-radius: 4px;
        }

        /* Message actions hover */
        .msg-actions-hover {
            display: none;
            gap: 2px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }
        .message-row.sent .msg-actions-hover {
            right: auto;
            left: -44px;
        }
        .message-row.received .msg-actions-hover {
            right: -44px;
        }
        .message-row:hover .msg-actions-hover {
            display: flex;
        }
        .msg-actions-hover button {
            background: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748B;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.2s;
            position: relative;
        }
        .msg-actions-hover button:hover {
            background: #F1F5F9;
            color: #0B1A2E;
        }
        .msg-actions-hover .delete-msg-btn:hover {
            background: #FEF2F2;
            color: #DC2626;
        }
        .msg-actions-hover .react-msg-btn:hover {
            background: #FEF3C7;
            color: #F59E0B;
        }

        .message-time {
            font-size: 12px;
            font-weight: 500;
            color: #94A3B8;
            margin-top: 4px;
            padding: 0 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .message-row.sent .message-time .read-status {
            font-size: 14px;
            color: #94A3B8;
        }
        .message-row.sent .message-time .read-status.read {
            color: #3B82F6;
        }

        /* Message Input */
        .message-input-area {
            padding: 14px 24px 18px;
            border-top: 1px solid #EEF2F6;
            display: flex;
            gap: 10px;
            align-items: center;
            background: #FFFFFF;
            flex-shrink: 0;
        }

        .input-tools {
            display: flex;
            gap: 2px;
            align-items: center;
        }
        .input-tools button {
            background: none;
            border: none;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748B;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .input-tools button:hover {
            background: #F1F5F9;
            color: #0E7A4E;
        }

        .message-input-area input[type="text"] {
            flex: 1;
            padding: 13px 22px;
            border: 2px solid #EEF2F6;
            border-radius: 30px;
            background: #F8FAFC;
            font-size: 15.5px;
            outline: none;
            font-weight: 500;
            transition: all 0.2s;
            height: 50px;
            font-family: 'Inter', sans-serif;
            color: #0B1A2E;
        }
        .message-input-area input[type="text"]::placeholder {
            color: #94A3B8;
            font-weight: 400;
        }
        .message-input-area input[type="text"]:focus {
            border-color: #0E7A4E;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(14, 122, 78, 0.06);
        }

        .send-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #0E7A4E;
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
            box-shadow: 0 4px 16px rgba(14, 122, 78, 0.3);
        }
        .send-btn:hover {
            background: #0a5c3a;
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(14, 122, 78, 0.35);
        }
        .send-btn svg {
            width: 22px;
            height: 22px;
        }

        .input-hint {
            font-size: 12px;
            color: #94A3B8;
            text-align: center;
            padding: 4px 0 0;
            font-weight: 500;
        }

        /* Emoji picker */
        .emoji-picker-container {
            position: relative;
        }
        .emoji-picker-container emoji-picker {
            position: absolute;
            bottom: 60px;
            left: 0;
            z-index: 20;
            --emoji-picker-background-color: white;
            --emoji-picker-border-radius: 16px;
            --emoji-picker-shadow: 0 8px 30px rgba(0,0,0,0.12);
            --emoji-picker-border: 1px solid #EEF2F6;
        }

        #file-input { display: none; }

        /* Typing indicator */
        .typing-indicator {
            display: none;
            align-self: flex-start;
            padding: 8px 18px;
            background: #F1F5F9;
            border-radius: 18px;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #64748B;
            gap: 6px;
        }
        .typing-indicator .dots {
            display: flex;
            gap: 4px;
        }
        .typing-indicator .dots span {
            width: 8px;
            height: 8px;
            background: #94A3B8;
            border-radius: 50%;
            animation: dot-bounce 1.4s infinite;
        }
        .typing-indicator .dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator .dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes dot-bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-8px); }
        }

        /* Empty states */
        .no-conversation {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #64748B;
            text-align: center;
            padding: 40px;
        }
        .no-conversation .icon {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #EAF8F1;
            color: #0E7A4E;
            font-size: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .no-conversation h3 {
            color: #0B1A2E;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
        }
        .no-conversation p {
            font-size: 15px;
            font-weight: 500;
            margin-bottom: 22px;
            max-width: 340px;
            color: #64748B;
        }
        .browse-btn {
            display: inline-block;
            padding: 14px 36px;
            background: #0E7A4E;
            color: white;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: 0.2s;
            box-shadow: 0 4px 16px rgba(14,122,78,0.25);
        }
        .browse-btn:hover {
            background: #0a5c3a;
            transform: translateY(-3px);
            color: white;
            box-shadow: 0 8px 24px rgba(14,122,78,0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #64748B;
        }
        .empty-state .icon {
            font-size: 40px;
            color: #CBD5E1;
            margin-bottom: 12px;
        }
        .empty-state p {
            font-size: 15px;
            font-weight: 500;
        }

        /* Delete Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(6px);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show {
            display: flex;
        }
        .modal-box {
            background: white;
            padding: 36px;
            border-radius: 24px;
            max-width: 440px;
            width: 90%;
            text-align: center;
            box-shadow: 0 24px 64px rgba(0,0,0,0.2);
        }
        .modal-box .icon {
            font-size: 48px;
            color: #DC2626;
            margin-bottom: 16px;
        }
        .modal-box h3 {
            font-size: 20px;
            font-weight: 800;
            color: #0B1A2E;
            margin-bottom: 8px;
        }
        .modal-box p {
            font-size: 15px;
            color: #64748B;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .modal-buttons button {
            padding: 12px 32px;
            border-radius: 30px;
            border: none;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .modal-buttons .cancel-btn {
            background: #F1F5F9;
            color: #0B1A2E;
        }
        .modal-buttons .cancel-btn:hover {
            background: #E5E7EB;
        }
        .modal-buttons .delete-btn {
            background: #DC2626;
            color: white;
        }
        .modal-buttons .delete-btn:hover {
            background: #B91C1C;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #0B1A2E;
            color: white;
            padding: 16px 28px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 200;
            font-family: 'Inter', sans-serif;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .messages-section {
                padding: 12px 12px 12px 12px;
                height: calc(100vh - 60px);
            }
            .messages-container {
                flex-direction: column;
                border-radius: 16px;
            }
            .conversations-sidebar {
                width: 100%;
                max-height: 260px;
                border-right: none;
                border-bottom: 1px solid #EEF2F6;
                min-width: unset;
            }
            .sidebar-footer { display: none; }
            .chat-area {
                height: 55vh;
                min-height: 300px;
            }
            .message-row {
                max-width: 88%;
            }
            .property-preview {
                flex-wrap: wrap;
                margin: 10px 16px 0 16px;
            }
            .property-preview img {
                width: 50px;
                height: 50px;
            }
            .chat-header {
                padding: 12px 16px;
                flex-wrap: wrap;
            }
            .chat-header-left .chat-avatar {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            .chat-user-info h4 {
                font-size: 16px;
            }
            .messages-list {
                padding: 14px 16px 8px;
            }
            .message-input-area {
                padding: 10px 14px 14px;
            }
        }

        @media (max-width: 500px) {
            .messages-section {
                padding: 6px 6px 6px 6px;
            }
            .conversations-sidebar {
                max-height: 180px;
            }
            .conversation-item {
                padding: 10px 12px;
            }
            .conversation-avatar {
                width: 44px;
                height: 44px;
                font-size: 18px;
            }
            .message-bubble {
                font-size: 14.5px;
                padding: 10px 15px;
            }
            .message-input-area {
                padding: 8px 10px 12px;
                gap: 6px;
                flex-wrap: wrap;
            }
            .message-input-area input[type="text"] {
                padding: 10px 14px;
                font-size: 14.5px;
                height: 42px;
                flex: 1 1 60%;
            }
            .input-tools button {
                width: 36px;
                height: 36px;
                font-size: 17px;
            }
            .send-btn {
                width: 42px;
                height: 42px;
            }
            .conv-menu-btn {
                opacity: 1;
            }
            .msg-actions-hover {
                display: flex !important;
                opacity: 0.5;
            }
            .property-preview {
                padding: 10px 14px;
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .property-preview img {
                width: 100%;
                height: 100px;
                object-fit: cover;
            }
            .view-prop-btn {
                text-align: center;
            }
            .chat-header-right button {
                width: 34px;
                height: 34px;
                font-size: 15px;
            }
            .messages-list {
                padding: 10px 12px 6px;
            }
            .no-conversation .icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            .no-conversation h3 {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>

<section class="messages-section">
    <div class="messages-container">

        <!-- Left Sidebar - Conversations -->
        <div class="conversations-sidebar">
            <div class="sidebar-header">
                <h3>Messages <span class="badge"><?php echo mysqli_num_rows($conversations_result); ?></span></h3>
                <button class="filter-btn" type="button" onclick="showToast('Filter feature coming soon!')" title="Filter">
                    <i class="fas fa-sliders-h"></i>
                </button>
            </div>
            <div class="conv-search">
                <div class="conv-search-wrap">
                    <input type="text" id="convSearchInput" placeholder="Search conversations...">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <div class="conversations-list" id="convList">
                <?php if ($conversations_result && mysqli_num_rows($conversations_result) > 0): $i = 0; ?>
                    <?php while ($conv = mysqli_fetch_assoc($conversations_result)): $avatar_color = $avatar_palette[$i % count($avatar_palette)]; $i++; ?>
                        <div class="conversation-item-wrapper">
                            <a href="messages.php?conversation=<?php echo $conv['user_id']; ?>"
                               class="conversation-item <?php echo ($selected_conversation == $conv['user_id']) ? 'active' : ''; ?>"
                               data-name="<?php echo htmlspecialchars(strtolower($conv['full_name'])); ?>">
                                <div class="conversation-avatar" style="background: <?php echo $avatar_color; ?>;">
                                    <?php if (!empty($conv['profile_pic'])): ?>
                                        <img src="<?php echo htmlspecialchars($conv['profile_pic']); ?>" alt="">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($conv['full_name'], 0, 1)); ?>
                                    <?php endif; ?>
                                    <span class="online-dot"></span>
                                </div>
                                <div class="conversation-info">
                                    <div class="conv-top-row">
                                        <span class="conv-name"><?php echo htmlspecialchars($conv['full_name']); ?></span>
                                        <span class="conv-time"><?php echo $conv['last_message_time'] ? time_ago_short($conv['last_message_time']) : ''; ?></span>
                                    </div>
                                    <div class="conv-bottom-row">
                                        <span class="conv-last-msg">
                                            <?php echo $conv['last_message'] ? htmlspecialchars(mb_substr($conv['last_message'], 0, 35)) : 'No messages yet'; ?>
                                        </span>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="conv-unread"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </a>
                            <button class="conv-menu-btn" onclick="toggleConvMenu(event, <?php echo $conv['user_id']; ?>, '<?php echo htmlspecialchars($conv['full_name']); ?>')">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="conv-dropdown" id="convMenu_<?php echo $conv['user_id']; ?>">
                                <button onclick="window.location.href='messages.php?conversation=<?php echo $conv['user_id']; ?>'">
                                    <i class="fas fa-comment"></i> Open Chat
                                </button>
                                <button onclick="showToast('Mute feature coming soon!')">
                                    <i class="fas fa-bell-slash"></i> Mute Notifications
                                </button>
                                <button onclick="showToast('Archive feature coming soon!')">
                                    <i class="fas fa-archive"></i> Archive
                                </button>
                                <button class="danger-btn" onclick="deleteConversation(<?php echo $conv['user_id']; ?>, '<?php echo htmlspecialchars($conv['full_name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete Conversation
                                </button>
                                <button class="danger-btn" onclick="showToast('Block user feature coming soon!')">
                                    <i class="fas fa-ban"></i> Block User
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="icon"><i class="fas fa-inbox"></i></div>
                        <p style="font-weight: 700; color: #0B1A2E; font-size: 16px;">No conversations yet</p>
                        <p style="margin-top: 4px; font-size: 14px; font-weight: 500;">Message a property seller to start chatting</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Start new conversation card -->
            <div class="sidebar-footer">
                <div class="start-conv-card">
                    <h4>Start new conversation</h4>
                    <p>Browse properties and start chatting with interested buyers.</p>
                    <a href="listings.php" class="browse-btn-sm">Browse Properties <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Right Side - Chat Area -->
        <div class="chat-area">
            <?php if ($selected_conversation > 0 && $conversation_user): ?>
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="chat-header-left">
                        <div class="chat-avatar" style="background: <?php echo $avatar_palette[0]; ?>;">
                            <?php if (!empty($conversation_user['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($conversation_user['profile_pic']); ?>" alt="">
                            <?php else: ?>
                                <?php echo strtoupper(substr($conversation_user['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                            <span class="online-dot" style="width:11px;height:11px;bottom:1px;right:1px;"></span>
                        </div>
                        <div class="chat-user-info">
                            <h4><?php echo htmlspecialchars($conversation_user['full_name']); ?></h4>
                            <div class="status">
                                <span class="dot"></span> Online
                            </div>
                        </div>
                    </div>
                    <div class="chat-header-right">
                        <button onclick="showToast('Voice call feature coming soon!')" title="Voice Call">
                            <i class="fas fa-phone"></i>
                        </button>
                        <button onclick="showToast('Video call feature coming soon!')" title="Video Call">
                            <i class="fas fa-video"></i>
                        </button>
                        <button onclick="showToast('Contact info coming soon!')" title="Info">
                            <i class="fas fa-info-circle"></i>
                        </button>
                        <button onclick="toggleHeaderMenu(event)" title="More">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="conv-dropdown" id="headerMenu" style="position:absolute;right:0;top:100%;margin-top:6px;min-width:190px;">
                            <button onclick="window.location.href='profile.php?id=<?php echo $selected_conversation; ?>'">
                                <i class="fas fa-user"></i> View Profile
                            </button>
                            <button onclick="showToast('Search in chat coming soon!')">
                                <i class="fas fa-search"></i> Search in Chat
                            </button>
                            <button onclick="showToast('Mute feature coming soon!')">
                                <i class="fas fa-bell-slash"></i> Mute Notifications
                            </button>
                            <button class="danger-btn" onclick="deleteConversation(<?php echo $selected_conversation; ?>, '<?php echo htmlspecialchars($conversation_user['full_name']); ?>')">
                                <i class="fas fa-trash"></i> Clear Conversation
                            </button>
                            <button class="danger-btn" onclick="showToast('Delete conversation feature coming soon!')">
                                <i class="fas fa-trash-alt"></i> Delete Conversation
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Property Preview Card -->
                <?php if ($property_info): ?>
                <div class="property-preview">
                    <?php if (!empty($property_info['image'])): ?>
                        <img src="<?php echo htmlspecialchars($property_info['image']); ?>" alt="">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/60x60/EAFAF1/0E7A4E?text=🏠" alt="">
                    <?php endif; ?>
                    <div class="property-preview-info">
                        <h5><?php echo htmlspecialchars($property_info['title']); ?></h5>
                        <div class="details">
                            <span><i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($property_info['location']); ?></span>
                            <span><i class="fas fa-bed"></i> <?php echo $property_info['bedrooms'] ?? '3'; ?></span>
                            <span><i class="fas fa-bath"></i> <?php echo $property_info['bathrooms'] ?? '2'; ?></span>
                            <span class="price">PKR <?php echo number_format($property_info['price'] / 1000000, 1); ?>M</span>
                        </div>
                    </div>
                    <a href="property-detail.php?id=<?php echo $property_info['id']; ?>" class="view-prop-btn">View Property</a>
                </div>
                <?php endif; ?>

                <!-- Messages List -->
                <div class="messages-list" id="messagesList">
                    <?php if (empty($conversation_messages)): ?>
                        <div class="empty-state">
                            <div class="icon"><i class="fas fa-comment-dots"></i></div>
                            <p style="font-weight: 700; color: #0B1A2E; font-size: 16px;">No messages yet</p>
                            <p style="margin-top: 4px; font-size: 14px; font-weight: 500;">Send a message to start the conversation</p>
                        </div>
                    <?php else:
                        $last_day = '';
                        foreach ($conversation_messages as $msg):
                            $this_day = date('Y-m-d', strtotime($msg['created_at']));
                            if ($this_day !== $last_day):
                                $last_day = $this_day;
                    ?>
                        <div class="day-divider"><?php echo day_label($msg['created_at']); ?></div>
                    <?php endif;
                            $is_sent = ($msg['sender_id'] == $user_id);
                    ?>
                        <div class="message-row <?php echo $is_sent ? 'sent' : 'received'; ?>" data-msg-id="<?php echo $msg['id']; ?>">
                            <div class="msg-avatar-sm">
                                <?php if (!empty($msg['sender_pic'])): ?>
                                    <img src="<?php echo htmlspecialchars($msg['sender_pic']); ?>" alt="">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($msg['sender_name'] ?? '?', 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <div class="message-content">
                                <div class="message-bubble-wrapper">
                                    <div class="message-bubble">
                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                    </div>
                                    <?php if ($is_sent): ?>
                                        <div class="msg-actions-hover">
                                            <button class="react-msg-btn" data-tip="React" onclick="reactMessage(<?php echo $msg['id']; ?>)">
                                                <i class="fa-regular fa-face-smile"></i>
                                            </button>
                                            <button data-tip="Reply" onclick="replyMessage(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['message']); ?>')">
                                                <i class="fa-regular fa-reply"></i>
                                            </button>
                                            <button data-tip="Copy" onclick="copyMessage('<?php echo addslashes($msg['message']); ?>')">
                                                <i class="fa-regular fa-copy"></i>
                                            </button>
                                            <button class="delete-msg-btn" data-tip="Delete" onclick="deleteMessage(<?php echo $msg['id']; ?>)">
                                                <i class="fa-regular fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                    <?php if ($is_sent): ?>
                                        <span class="read-status read"><i class="fas fa-check-double"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>

                    <!-- Typing Indicator -->
                    <div class="typing-indicator" id="typingIndicator">
                        <span><?php echo htmlspecialchars($conversation_user['full_name']); ?> is typing</span>
                        <div class="dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>

                <!-- Message Input Area -->
                <form method="POST" class="message-input-area" id="messageForm">
                    <input type="hidden" name="receiver_id" value="<?php echo $selected_conversation; ?>">
                    <input type="hidden" name="property_id" value="<?php echo $selected_property_id ?: 0; ?>">
                    <input type="hidden" name="edit_msg_id" id="editMsgId" value="">

                    <div class="input-tools">
                        <button type="button" class="emoji-toggle" onclick="toggleEmoji()" title="Emoji">
                            <i class="fa-regular fa-face-smile"></i>
                        </button>
                        <div class="emoji-picker-container">
                            <emoji-picker id="emojiPicker" style="display: none;"></emoji-picker>
                        </div>
                        <button type="button" onclick="document.getElementById('file-input').click();" title="Attach file">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <input type="file" id="file-input" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx">
                    </div>

                    <input type="text" name="message" id="messageInput" placeholder="Type your message..." autocomplete="off" required>

                    <button type="submit" name="send_message" class="send-btn" id="sendBtn" title="Send">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2v7z"/></svg>
                    </button>
                </form>
                <div class="input-hint">Press Enter to send &bull; Shift + Enter for new line</div>

            <?php else: ?>
                <!-- No Conversation Selected -->
                <div class="no-conversation">
                    <div class="icon"><i class="fas fa-comment-dots"></i></div>
                    <h3>Start new conversation</h3>
                    <p>Browse properties and start chatting with interested buyers.</p>
                    <a href="listings.php" class="browse-btn">Browse Properties <i class="fas fa-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h3 id="deleteModalTitle">Delete this conversation?</h3>
        <p id="deleteModalDesc">This action cannot be undone.</p>
        <div class="modal-buttons">
            <button class="cancel-btn" onclick="closeDeleteModal()">Cancel</button>
            <button class="delete-btn" id="confirmDeleteBtn">Delete</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast" id="toast"></div>

<script>
// Toast notification
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(function() {
        toast.classList.remove('show');
    }, 2500);
}

// Auto-scroll to bottom of messages
const messagesList = document.getElementById('messagesList');
if (messagesList) {
    messagesList.scrollTop = messagesList.scrollHeight;
}

// --- Conversation Search ---
const convSearchInput = document.getElementById('convSearchInput');
if (convSearchInput) {
    convSearchInput.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        document.querySelectorAll('#convList .conversation-item').forEach(function (item) {
            const name = item.getAttribute('data-name') || '';
            item.style.display = name.indexOf(q) > -1 ? 'flex' : 'none';
        });
    });
}

// --- Emoji Picker ---
let emojiOpen = false;

function toggleEmoji() {
    const picker = document.getElementById('emojiPicker');
    if (picker) {
        emojiOpen = !emojiOpen;
        picker.style.display = emojiOpen ? 'block' : 'none';
        if (emojiOpen) {
            document.getElementById('messageInput').focus();
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const picker = document.getElementById('emojiPicker');
    if (picker) {
        picker.addEventListener('emoji-click', function(e) {
            const input = document.getElementById('messageInput');
            if (input) {
                const start = input.selectionStart;
                const end = input.selectionEnd;
                const text = input.value;
                const emoji = e.detail.unicode;
                input.value = text.substring(0, start) + emoji + text.substring(end);
                input.selectionStart = input.selectionEnd = start + emoji.length;
                input.focus();
            }
            picker.style.display = 'none';
            emojiOpen = false;
        });
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.emoji-picker-container') && !e.target.closest('.emoji-toggle')) {
                picker.style.display = 'none';
                emojiOpen = false;
            }
        });
    }

    // File input handler
    const fileInput = document.getElementById('file-input');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                let msg = '📎 Selected files:\n';
                for (let i = 0; i < this.files.length; i++) {
                    msg += '  • ' + this.files[i].name + ' (' + Math.round(this.files[i].size/1024) + 'KB)\n';
                }
                alert(msg + '\n(File upload feature coming soon!)');
                this.value = '';
            }
        });
    }
});

// --- Typing Indicator (simulated) ---
let typingTimeout = null;
const messageInput = document.getElementById('messageInput');
if (messageInput) {
    messageInput.addEventListener('input', function() {
        const indicator = document.getElementById('typingIndicator');
        if (indicator) {
            indicator.style.display = 'flex';
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(function() {
                indicator.style.display = 'none';
            }, 2000);
        }
    });
}

// --- Message Actions ---
function deleteMessage(msgId) {
    showDeleteModal('Delete this message?', 'This message will be removed from the chat.', function() {
        window.location.href = '?delete_msg=' + msgId + '&conversation=<?php echo $selected_conversation; ?>' + (<?php echo $selected_property_id ?: 0; ?> ? '&property_id=<?php echo $selected_property_id; ?>' : '');
    });
}

function deleteConversation(userId, userName) {
    showDeleteModal('Delete conversation with ' + userName + '?', 'All messages in this conversation will be permanently deleted.', function() {
        window.location.href = '?delete_conv=' + userId;
    });
}

function reactMessage(msgId) {
    const reactions = ['❤️', '👍', '😂', '😮', '😢', '🔥', '👏', '🎉'];
    const react = reactions[Math.floor(Math.random() * reactions.length)];
    showToast('Reacted with ' + react + ' to message');
}

function replyMessage(msgId, text) {
    const input = document.getElementById('messageInput');
    if (input) {
        const preview = text.substring(0, 60) + (text.length > 60 ? '...' : '');
        input.value = '> ' + preview + '\n\n';
        input.focus();
        showToast('Replying to message');
    }
}

function copyMessage(text) {
    navigator.clipboard.writeText(text).then(function() {
        showToast('✅ Message copied to clipboard!');
    }).catch(function() {
        showToast('Could not copy message.');
    });
}

// --- Conversation Three-Dot Menu ---
function toggleConvMenu(event, userId, userName) {
    event.stopPropagation();
    const menu = document.getElementById('convMenu_' + userId);
    if (menu) {
        document.querySelectorAll('.conv-dropdown.show').forEach(function(m) {
            if (m.id !== menu.id) m.classList.remove('show');
        });
        menu.classList.toggle('show');
    }
}

function toggleHeaderMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('headerMenu');
    if (menu) {
        document.querySelectorAll('.conv-dropdown.show').forEach(function(m) {
            if (m.id !== menu.id) m.classList.remove('show');
        });
        menu.classList.toggle('show');
    }
}

document.addEventListener('click', function() {
    document.querySelectorAll('.conv-dropdown.show').forEach(function(m) {
        m.classList.remove('show');
    });
});

// --- Delete Modal ---
let deleteCallback = null;

function showDeleteModal(title, desc, callback) {
    document.getElementById('deleteModalTitle').textContent = title;
    document.getElementById('deleteModalDesc').textContent = desc;
    document.getElementById('deleteModal').classList.add('show');
    deleteCallback = callback;
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('show');
    deleteCallback = null;
}

document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
    if (deleteCallback) {
        deleteCallback();
    }
    closeDeleteModal();
});

document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// Auto-refresh messages every 30 seconds
setInterval(function() {
    if (window.location.href.includes('conversation=')) {
        location.reload();
    }
}, 30000);
</script>

</body>
</html>