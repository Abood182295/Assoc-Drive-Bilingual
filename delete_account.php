<?php
// delete_account.php
session_start();
include 'init_lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database Connection (Hail Local XAMPP Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

$current_uid = $_SESSION['user_id'];

// 1. Fetch paths to physically delete files from htdocs
$file_query = "SELECT file_path FROM files WHERE user_id = '$current_uid'";
$files_result = $conn->query($file_query);

if ($files_result) {
    while($row = $files_result->fetch_assoc()){
        $path = $row['file_path'];
        if (file_exists($path)) {
            unlink($path); // Purges the actual file from storage
        }
    }
}

// 2. Database Purge (Using 'user_id' to match fixed schema)
$conn->query("DELETE FROM files WHERE user_id = '$current_uid'");
$conn->query("DELETE FROM users WHERE user_id = '$current_uid'");

session_destroy();
header("Location: login.php?status=account_deleted");
exit();
?>