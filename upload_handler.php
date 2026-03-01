<?php
// Display errors for your Hail local dev environment
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Initialize session and language
session_start();
include 'init_lang.php';

// 2. Database Connection (Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

// 3. Security Check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// 4. Handle Upload (Both Button and Drag & Drop)
// We check for $_FILES['file'] which covers both methods
if (isset($_FILES['file'])) {
    
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];

    // Capture folder_id from the AJAX request
    $folder_id = (!empty($_POST['folder_id']) && $_POST['folder_id'] !== 'null') ? (int)$_POST['folder_id'] : null;

    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Generate Unique Path
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $newFileName = uniqid('', true) . "." . $fileExt;
    $uploadDirectory = 'uploads/'; 
    $destination = $uploadDirectory . $newFileName;

    if ($fileError === 0) {
        // Create directory if missing
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        if (move_uploaded_file($fileTmpName, $destination)) {
            // Category Logic
            $category = 'general';
            if (in_array($fileExt, ['pdf', 'doc', 'docx'])) {
                $category = 'books';
            } elseif (in_array($fileExt, ['mp4', 'mp3', 'png', 'jpg', 'jpeg'])) {
                $category = 'media';
            }

            // 5. Save Record (Includes folder_id and file_size)
            $sql = "INSERT INTO files (user_id, folder_id, file_name, file_path, file_size, category, is_deleted) VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissis", $user_id, $folder_id, $fileName, $destination, $fileSize, $category);
            
            if ($stmt->execute()) {
                // IMPORTANT: Return JSON for AJAX to stop the "Uploading..." hang
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Database Error: ' . $stmt->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to move file. Check permissions.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload Error Code: ' . $fileError]);
    }
} else {
    // If accessed directly without a file
    echo json_encode(['success' => false, 'message' => 'No file received.']);
}
?>