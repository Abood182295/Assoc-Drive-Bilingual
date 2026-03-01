<?php
// auto_purge.php

// 1. Connection (Hail Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

// 2. Find files trashed more than 30 days ago
// We use the deleted_at timestamp we just added to your schema
$sql = "SELECT file_id, file_path FROM files 
        WHERE is_deleted = 1 
        AND deleted_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $path = $row['file_path'];
        $id = $row['file_id'];

        // 3. Physical removal from the server
        if (file_exists($path)) {
            unlink($path); // Deletes the actual file from the uploads folder
        }

        // 4. Permanent Database removal
        $conn->query("DELETE FROM files WHERE file_id = '$id'");
    }
}
?>