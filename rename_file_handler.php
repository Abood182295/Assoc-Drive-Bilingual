<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_GET['id']) || empty($_GET['name'])) {
    header("Location: dashboard.php");
    exit();
}

$host = 'localhost:3307'; $user = 'root'; $pass = ''; $db = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

$user_id = $_SESSION['user_id'];
$file_id = (int)$_GET['id'];
$new_name = $conn->real_escape_string(urldecode($_GET['name']));

if (!empty($new_name)) {
    // Update the database. Note: We do NOT change the physical file_path, just the display name.
    $sql = "UPDATE files SET file_name = '$new_name' WHERE file_id = $file_id AND user_id = '$user_id'";
    $conn->query($sql);
}

$conn->close();

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';
header("Location: " . $referer);
exit();
?>