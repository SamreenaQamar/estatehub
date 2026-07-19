<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    die('Direct access not permitted.');
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];

if (isset($_POST['send_message'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $property_id = (int)$_POST['property_id'];
    $message = trim($_POST['message']);

    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit();
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO messages (sender_id, receiver_id, property_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    mysqli_stmt_bind_param($stmt, 'iiis', $admin_id, $receiver_id, $property_id, $message);
    if (mysqli_stmt_execute($stmt)) {
        $msg_id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'msg_id' => $msg_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to send message']);
    }
    mysqli_stmt_close($stmt);
    exit();
}

if (isset($_GET['delete_conversation'])) {
    $sender = (int)$_GET['sender'];
    $property = (int)$_GET['property'];

    $stmt = mysqli_prepare($conn, "DELETE FROM messages WHERE property_id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
    mysqli_stmt_bind_param($stmt, 'iiiii', $property, $sender, $admin_id, $admin_id, $sender);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete conversation']);
    }
    mysqli_stmt_close($stmt);
    exit();
}

if (isset($_GET['delete_message'])) {
    $msg_id = (int)$_GET['msg_id'];

    $check = mysqli_query($conn, "SELECT sender_id FROM messages WHERE id = $msg_id");
    if ($check && $row = mysqli_fetch_assoc($check)) {
        if ($row['sender_id'] != $admin_id) {
            echo json_encode(['success' => false, 'error' => 'You can only delete your own messages']);
            exit();
        }
        $stmt = mysqli_prepare($conn, "DELETE FROM messages WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $msg_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete message']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Message not found']);
    }
    exit();
}

if (isset($_GET['edit_message'])) {
    $msg_id = (int)$_GET['msg_id'];
    $new_message = trim($_GET['message']);

    if (empty($new_message)) {
        echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
        exit();
    }

    $check = mysqli_query($conn, "SELECT sender_id FROM messages WHERE id = $msg_id");
    if ($check && $row = mysqli_fetch_assoc($check)) {
        if ($row['sender_id'] != $admin_id) {
            echo json_encode(['success' => false, 'error' => 'You can only edit your own messages']);
            exit();
        }
        $stmt = mysqli_prepare($conn, "UPDATE messages SET message = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $new_message, $msg_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to edit message']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Message not found']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
?>