<?php
// 1. Session and Global Initialization
session_start();
include 'init_lang.php'; // Ensures the language and session state are active

// 2. Security Check: Only logged-in users can download
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("Access denied. Please login first.");
}

// 3. Database Connection (Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 4. Sanitize Input
$file_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// 5. Fetch file info
$query = "SELECT file_name, file_path FROM files WHERE file_id = $file_id AND user_id = $user_id";
$result = $conn->query($query);

if ($row = $result->fetch_assoc()) {
    $file_path = $row['file_path'];
    $file_name = $row['file_name'];

    // 6. The "For Real" Download Logic
    if (file_exists($file_path)) {
        // Headers to clean the output buffer and prepare the download
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        
        // Read the file and exit
        readfile($file_path);
        exit;
    } else {
        // This helps you debug if the file path in the DB is wrong
        die("Error: File not found on the server. Path: " . htmlspecialchars($file_path));
    }
} else {
    die("Error: No record found for this file.");
}
?>