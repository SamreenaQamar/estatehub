<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// ============================================================
// AUTHENTICATION & USER CHECK
// ============================================================
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['user', 'buyer'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$buyer_id = (int) $_SESSION['user_id'];

// ============================================================
// AJAX ACTIONS
// ============================================================
$action = isset($_GET['action']) ? $_GET['action'] : '';

// ---- SEND MESSAGE ----
if ($action === 'send' && isset($_POST['send_message'])) {
    $receiver_id = (int) ($_POST['receiver_id'] ?? 0);
    $property_id = (int) ($_POST['property_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if (!$receiver_id || !$property_id || empty($message)) {
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, property_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiis", $buyer_id, $receiver_id, $property_id, $message);
    if ($stmt->execute()) {
        $msg_id = $stmt->insert_id;
        echo json_encode(['success' => true, 'msg_id' => $msg_id]);
    } else {
        echo json_encode(['error' => 'Database error']);
    }
    $stmt->close();
    exit();
}

// ---- DELETE MESSAGE ----
if ($action === 'delete_message' && isset($_GET['msg_id'])) {
    $msg_id = (int) $_GET['msg_id'];
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("ii", $msg_id, $buyer_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Message not found or permission denied']);
    }
    $stmt->close();
    exit();
}

// ---- EDIT MESSAGE ----
if ($action === 'edit_message' && isset($_GET['msg_id']) && isset($_GET['message'])) {
    $msg_id = (int) $_GET['msg_id'];
    $new_message = trim($_GET['message']);
    if (empty($new_message)) {
        echo json_encode(['error' => 'Message cannot be empty']);
        exit();
    }
    $stmt = $conn->prepare("UPDATE messages SET message = ? WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("sii", $new_message, $msg_id, $buyer_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update or no permission']);
    }
    $stmt->close();
    exit();
}

// ---- DELETE CONVERSATION ----
if ($action === 'delete_conversation' && isset($_GET['user']) && isset($_GET['property'])) {
    $other_user = (int) $_GET['user'];
    $property = (int) $_GET['property'];

    $stmt = $conn->prepare("DELETE FROM messages 
                            WHERE property_id = ? 
                              AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))");
    $stmt->bind_param("iiiii", $property, $buyer_id, $other_user, $other_user, $buyer_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to delete conversation']);
    }
    $stmt->close();
    exit();
}

// ---- DEFAULT ----
echo json_encode(['error' => 'Invalid action']);
exit();
?>