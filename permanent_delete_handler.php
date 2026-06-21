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

    // Step 1: Get the file path so we can delete the actual physical file from the server
    $path_sql = "SELECT file_path FROM files WHERE file_id = ? AND user_id = ?";
    $path_stmt = $conn->prepare($path_sql);
    $path_stmt->bind_param("ii", $file_id, $user_id);
    $path_stmt->execute();
    $result = $path_stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $file_path = $row['file_path'];
        
        // Step 2: Delete the physical file from the XAMPP directory if it exists
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Step 3: Delete the record from the database completely
        $del_sql = "DELETE FROM files WHERE file_id = ? AND user_id = ?";
        $del_stmt = $conn->prepare($del_sql);
        $del_stmt->bind_param("ii", $file_id, $user_id);
        $del_stmt->execute();
        $del_stmt->close();
    }

    $path_stmt->close();
    $conn->close();

    // Success redirect
    header("Location: dashboard.php?view=trash&status=permanently_deleted");
    exit();

} catch (Exception $e) {
    die("Execution Error: " . $e->getMessage());
}
?>