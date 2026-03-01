<?php
session_start();
include 'init_lang.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$file_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');

// SQL logic to move file back to active status
$sql = "UPDATE files SET is_deleted = 0 WHERE file_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $file_id, $user_id);

if ($stmt->execute()) {
    header("Location: dashboard.php?view=all&status=restored");
}
?>