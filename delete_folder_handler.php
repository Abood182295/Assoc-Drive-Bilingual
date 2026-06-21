<?php
session_start();

if (!isset($_SESSION['user_id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$user_id = $_SESSION['user_id'];
$folder_id = (int)$_GET['id'];

// Check how many files are inside this folder
$check_files = $conn->query("SELECT COUNT(*) as total FROM files WHERE folder_id = $folder_id AND user_id = '$user_id'");
$file_count = $check_files->fetch_assoc()['total'];

if ($file_count > 0) {
    // 1. Move all files to Trash & start their 30-day timer
    $conn->query("UPDATE files SET is_deleted = 1, deleted_at = NOW() WHERE folder_id = $folder_id AND user_id = '$user_id'");
    // 2. Move the folder to Trash & start its 30-day timer
    $conn->query("UPDATE folders SET is_deleted = 1, deleted_at = NOW() WHERE folder_id = $folder_id AND user_id = '$user_id'");
} else {
    // It's empty, so wipe it out completely!
    $conn->query("DELETE FROM folders WHERE folder_id = $folder_id AND user_id = '$user_id'");
}

$conn->close();
header("Location: dashboard.php");
exit();
?>