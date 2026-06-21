<?php
session_start();

// 1. Ensure user is logged in and a file ID is provided
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$file_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// 2. Database Connection (Hail XAMPP configuration)
$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');

if ($conn->connect_error) {
    header("Location: dashboard.php?status=error");
    exit();
}

// 3. Professional Soft Delete
$sql = "UPDATE files SET is_deleted = 1, deleted_at = NOW() WHERE file_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $file_id, $user_id);

// 4. Smart Redirect Logic: Find out where the user just came from
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php?view=all';

// Remove any existing status messages from the previous URL to prevent chaining
$redirect_url = preg_replace('/([?&])status=[^&]+(&|$)/', '$1', $redirect_url);
$redirect_url = rtrim($redirect_url, '?&');

// Determine if we need a '?' or '&' to append the new status
$separator = (parse_url($redirect_url, PHP_URL_QUERY) == NULL) ? '?' : '&';

// 5. Execute and gracefully redirect instead of using die()
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        // Success: Send them back to the exact folder they were in
        header("Location: " . $redirect_url . $separator . "status=trashed");
    } else {
        // Error: File not found or permission denied
        header("Location: " . $redirect_url . $separator . "status=not_found");
    }
} else { 
    // Database Execution Error
    header("Location: " . $redirect_url . $separator . "status=error"); 
}

$stmt->close();
$conn->close();
exit();
?>