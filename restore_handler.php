<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$file_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Database Connection Variables (Targeting XAMPP Port 3307)
$host = 'localhost:3307'; 
$db_user = 'root';
$db_pass = '';
$db_name = 'association_drive';

try {
    // Attempt Connection
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    // Check for connection errors
    if ($conn->connect_error) {
        die("Database Connection Failed on Port 3307: " . $conn->connect_error);
    }

    // Reset the deleted status (0 = false) and clear the deletion timestamp
    $sql = "UPDATE files SET is_deleted = 0, deleted_at = NULL WHERE file_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $file_id, $user_id);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    // Success redirect
    header("Location: dashboard.php?view=trash&status=restored");
    exit();

} catch (Exception $e) {
    die("Execution Error: " . $e->getMessage());
}
?>