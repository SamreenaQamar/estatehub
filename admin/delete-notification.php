<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Only admin can delete notifications
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$type = $_POST['type'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if (empty($type) || $id <= 0) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit();
}

$success = false;
$error = '';

switch ($type) {
    case 'message':
        $stmt = mysqli_prepare($conn, "DELETE FROM messages WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'user':
        $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'deleted' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'inquiry':
        $stmt = mysqli_prepare($conn, "DELETE FROM inquiries WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        break;

    case 'property':
        $stmt = mysqli_prepare($conn, "UPDATE properties SET status = 'Deleted' WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (mysqli_stmt_execute($stmt)) {
            $success = true;
        } else {
            $error = mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
        break;

    default:
        $error = 'Invalid notification type';
}

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => $error ?: 'Deletion failed']);
}
exit();