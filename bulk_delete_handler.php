<?php
session_start();

if (!isset($_SESSION['user_id']) || empty($_GET['ids'])) {
    header("Location: dashboard.php");
    exit();
}

// 1. Database Connection (Using your exact XAMPP 3307 details)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$ids = $_GET['ids']; // e.g., "12,45,67"

// 2. Security Check: Convert all IDs to safe integers
$id_array = explode(',', $ids);
$clean_ids = array_map('intval', $id_array);
$final_ids = implode(',', $clean_ids);

// 3. Execute the Bulk Delete Query
if (!empty($final_ids)) {
    // Move files to trash (is_deleted = 1)
    $sql = "UPDATE files SET is_deleted = 1 WHERE file_id IN ($final_ids) AND user_id = '$user_id'";
    
    if ($conn->query($sql)) {
        // Go back to the page the user was just on
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php';
        header("Location: " . $referer);
    } else {
        echo "Database Error: " . $conn->error;
    }
} else {
    header("Location: dashboard.php");
}

$conn->close();
exit();
?>