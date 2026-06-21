<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_GET['id'])) { header("Location: dashboard.php"); exit(); }

$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');
$user_id = $_SESSION['user_id'];
$folder_id = (int)$_GET['id'];

// Bring the folder AND all its files back, and clear the deletion timer
$conn->query("UPDATE folders SET is_deleted = 0, deleted_at = NULL WHERE folder_id = $folder_id AND user_id = '$user_id'");
$conn->query("UPDATE files SET is_deleted = 0, deleted_at = NULL WHERE folder_id = $folder_id AND user_id = '$user_id'");

$conn->close();
header("Location: dashboard.php?view=trash");
exit();
?>