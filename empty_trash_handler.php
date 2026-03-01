<?php
// empty_trash_handler.php
session_start();

// 1. Connection (Hail Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 2. Fetch all trashed files to delete them from the hard drive first
$query = $conn->prepare("SELECT file_path FROM files WHERE user_id = ? AND is_deleted = 1");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

// 3. Physical cleanup (frees up space on your 32GB limit)
while ($row = $result->fetch_assoc()) {
    $path = $row['file_path'];
    if (file_exists($path)) {
        unlink($path); // Deletes the actual file from the uploads/ folder
    }
}

// 4. Database cleanup
$conn->query("DELETE FROM files WHERE user_id = '$user_id' AND is_deleted = 1");

// 5. Redirect back to trash bin with a success message
header("Location: dashboard.php?view=trash&status=emptied");
exit();
?>