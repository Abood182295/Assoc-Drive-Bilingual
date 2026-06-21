<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !isset($_GET['name'])) {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$folder_id = (int)$_GET['id'];
$new_name = trim($_GET['name']);

// Database Connection
$host = 'localhost:3307';
$db_user = 'root';
$db_pass = '';
$db_name = 'association_drive';

try {
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Security check: Ensure the folder belongs to the user
    $sql = "UPDATE folders SET folder_name = ? WHERE folder_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $new_name, $folder_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: dashboard.php?status=renamed");
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
    exit();

} catch (Exception $e) {
    die("Execution Error: " . $e->getMessage());
}
?>