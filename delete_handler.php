<?php
session_start();
include 'init_lang.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$file_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');

// Professional Soft Delete: Mark as 1 instead of deleting
$sql = "UPDATE files SET is_deleted = 1,deleted_at = NOW()  WHERE file_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $file_id, $user_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
    header("Location: dashboard.php?view=all&status=trashed");
    exit();
} else {
   die("Error: File not found or permission denied. Ensure File User ID matches Session User ID.");
}
} else { die("Database Execution Error: " . $stmt->error); 
}
?>