<?php
session_start();
include 'init_lang.php';
$conn = new mysqli('localhost:3307', 'root', '', 'association_drive');

if (isset($_POST['signup'])) {
    $name = trim($_POST['full_name']);
    $user = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    // 1. Secure Hashing
    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    // 2. Check if email exists (Using prepared statement for security)
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $user, $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: signup.php?error=taken");
        exit();
    }

    // 3. Clean Insert
   $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, role) VALUES (?, ?, ?, ?, 'staff')");
   $stmt->bind_param("ssss", $name, $user, $email, $hashed_pass);
    if ($stmt->execute()) {
        header("Location: login.php?signup=success");
        exit();
    }
}
?>