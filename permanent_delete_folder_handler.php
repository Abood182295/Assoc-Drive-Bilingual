<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_GET['id'])) { header("Location: dashboard.php"); exit(); }

$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');
$user_id = $_SESSION['user_id'];
$folder_id = (int)$_GET['id'];

// 1. Delete actual physical files from the server folder
$res = $conn->query("SELECT file_path FROM files WHERE folder_id = $folder_id AND user_id = '$user_id'");
while ($row = $res->fetch_assoc()) {
    if (file_exists($row['file_path'])) unlink($row['file_path']); 
}

// 2. Wipe records from the database
$conn->query("DELETE FROM files WHERE folder_id = $folder_id AND user_id = '$user_id'");
$conn->query("DELETE FROM folders WHERE folder_id = $folder_id AND user_id = '$user_id'");

$conn->close();
header("Location: dashboard.php?view=trash");
exit();
?>