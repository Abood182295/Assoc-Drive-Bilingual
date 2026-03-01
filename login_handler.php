<?php
session_start();
include 'init_lang.php';

// 1. Establish connection to Port 3307
$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['login'])) {
    // 2. Collect 'username' instead of 'email'
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 3. Updated SQL Query to search by username
    $stmt = $conn->prepare("SELECT user_id, full_name, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // 4. Verify the password hash
        if (password_verify($password, $user['password'])) {
            
            // 5. Securely store user data in Session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];

            header("Location: dashboard.php");
            exit();
        } else {
            // Error handling for incorrect password
            header("Location: login.php?error=wrong_password");
            exit();
        }
    } else {
        // Error handling if username is not found
        header("Location: login.php?error=user_not_found");
        exit();
    }
}
?>