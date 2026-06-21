<?php
session_start();

// 1. Database Connection
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Process the Form Submission
if (isset($_POST['signup'])) {
    // Collect inputs safely
    $full_name  = trim($_POST['full_name']);
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $plain_pass = $_POST['password'];

    // 3. Hash the password for strict security
    $hashed_pass = password_hash($plain_pass, PASSWORD_DEFAULT);

    // 4. Secure Insert using Prepared Statements (Prevents SQL Injection)
    $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, password) VALUES (?, ?, ?, ?)");
    
    // "ssss" means 4 strings are being bound to the statement
    $stmt->bind_param("ssss", $username, $full_name, $email, $hashed_pass);
    
    if ($stmt->execute()) {
        // Redirect to login page upon success
        header("Location: login.php?signup=success");
        exit();
    } else {
        // Handle potential errors (like duplicate emails/usernames)
        echo "Error registering user: " . $stmt->error;
    }
} else {
    // If someone visits this file directly without submitting the form
    header("Location: signup.php");
    exit();
}
?>