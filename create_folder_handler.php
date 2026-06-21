<?php
session_start();

// 1. Check if user is logged in and a folder name was sent
if (!isset($_SESSION['user_id']) || empty($_GET['name'])) {
    header("Location: dashboard.php");
    exit();
}

// 2. Database Connection (Using your Hail XAMPP Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Clean and prepare the data
$user_id = $_SESSION['user_id'];
$folder_name = $conn->real_escape_string(urldecode($_GET['name']));

// 4. Check if a folder with this exact name already exists for this user
$check_sql = "SELECT folder_id FROM folders WHERE user_id = '$user_id' AND folder_name = '$folder_name'";
$check_res = $conn->query($check_sql);

// 5. If it doesn't exist, create it
if ($check_res && $check_res->num_rows == 0) {
    $insert_sql = "INSERT INTO folders (user_id, folder_name) VALUES ('$user_id', '$folder_name')";
    $conn->query($insert_sql);
}

$conn->close();

// 6. Send the user straight back to the dashboard to see their new folder!
header("Location: dashboard.php");
exit();
?>