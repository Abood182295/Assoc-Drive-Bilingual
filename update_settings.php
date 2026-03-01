<?php
session_start();
include 'init_lang.php';
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database Connection settings
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // 1. Update basic profile info first
    $updateSql = "UPDATE users SET full_name = '$full_name', email = '$email' WHERE user_id = $user_id";
    
    if ($conn->query($updateSql)) {
        // 2. Check if a password change was requested
        if (!empty($current_password) && !empty($new_password)) {
            $checkQuery = $conn->query("SELECT password FROM users WHERE user_id = $user_id");
            $userRow = $checkQuery->fetch_assoc();

            // Verify the current password before allowing a change
            if (password_verify($current_password, $userRow['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id");
                header("Location: settings.php?status=success");
            } else {
                // Redirect with a specific error code for wrong current password
                header("Location: settings.php?status=wrong_pass");
            }
        } else {
            // Only name and email were updated
            header("Location: settings.php?status=success");
        }
    } else {
        header("Location: settings.php?status=error");
    }
} else {
    header("Location: settings.php");
}

$conn->close();
?>