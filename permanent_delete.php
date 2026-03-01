<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$file_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');

// 1. Get the file path to delete from the actual folder
$res = $conn->query("SELECT file_path FROM files WHERE file_id = $file_id AND user_id = $user_id");
$file = $res->fetch_assoc();

if ($file) {
    unlink($file['file_path']); // Deletes the actual file from storage
    
    // 2. Delete the database record
    $conn->query("DELETE FROM files WHERE file_id = $file_id");
}

header("Location: dashboard.php?view=trash&status=erased");
?>