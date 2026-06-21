<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_GET['ids'])) {
    header("Location: dashboard.php");
    exit();
}

$host = 'localhost:3307'; $user = 'root'; $pass = ''; $db = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

$user_id = $_SESSION['user_id'];
$clean_ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));

if (!empty($clean_ids)) {
    // Set is_deleted back to 0
    $conn->query("UPDATE files SET is_deleted = 0 WHERE file_id IN ($clean_ids) AND user_id = '$user_id'");
}

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';
header("Location: " . $referer);
exit();
?>