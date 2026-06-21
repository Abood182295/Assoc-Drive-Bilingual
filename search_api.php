<?php
// search_api.php
session_start();
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if (isset($_GET['query']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "%" . $conn->real_escape_string($_GET['query']) . "%";

    // Fetch matching files that are not in the trash
    $stmt = $conn->prepare("SELECT file_id, file_name, file_path FROM files WHERE user_id = ? AND file_name LIKE ? AND is_deleted = 0 LIMIT 5");
    $stmt->bind_param("is", $user_id, $query);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }

    echo json_encode($suggestions);
}
?>