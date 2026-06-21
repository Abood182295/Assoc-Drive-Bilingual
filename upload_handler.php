<?php
// Display errors for your local dev environment
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

// 3. Helper Function: Map extensions to Folder Names
function getTargetFolder($extension) {
    $extension = strtolower($extension);
    
    // Grouped by category for clean code and easy maintenance
    $folders = [
        // ==================== AUDIO ====================
        'mp3' => 'Audio', 'm4a' => 'Audio', 'ogg' => 'Audio', 'wav' => 'Audio', 
        'flac' => 'Audio', 'aac' => 'Audio', 'opus' => 'Audio', 'wma' => 'Audio', 
        'alac' => 'Audio', 'aiff' => 'Audio', 'dsd' => 'Audio', 'pcm' => 'Audio', 
        'mka' => 'Audio', 'tta' => 'Audio', 'wv' => 'Audio', 'ape' => 'Audio', 
        'spx' => 'Audio', 'caf' => 'Audio', 'amr' => 'Audio', 'mid' => 'Audio', 
        'midi' => 'Audio', 'xmf' => 'Audio', 'rmi' => 'Audio', 'kar' => 'Audio', 
        's3m' => 'Audio', 'xm' => 'Audio', 'it' => 'Audio', 'mod' => 'Audio',

        // ==================== VIDEOS ====================
        'mp4' => 'Videos', 'mkv' => 'Videos', 'avi' => 'Videos', 'mov' => 'Videos', 
        'webm' => 'Videos', 'flv' => 'Videos', 'wmv' => 'Videos', 'mpeg' => 'Videos', 
        'mpg' => 'Videos', 'm4v' => 'Videos', '3gp' => 'Videos', 'vob' => 'Videos', 
        'rmvb' => 'Videos',

        // ==================== IMAGES ====================
        'png' => 'Images', 'jpg' => 'Images', 'jpeg' => 'Images', 'gif' => 'Images', 
        'bmp' => 'Images', 'tiff' => 'Images', 'tif' => 'Images', 'svg' => 'Images', 
        'webp' => 'Images', 'ico' => 'Images', 'icns' => 'Images', 'heic' => 'Images', 
        'heif' => 'Images', 'jfif' => 'Images', 'psd' => 'Images', 'raw' => 'Images', 
        'arw' => 'Images', 'eps' => 'Images', 'ai' => 'Images',

        // ================= DOCUMENTS =================
        'pdf' => 'Documents', 'doc' => 'Documents', 'docx' => 'Documents', 
        'txt' => 'Documents', 'rtf' => 'Documents', 'ppt' => 'Documents', 
        'pptx' => 'Documents', 'odt' => 'Documents',
        
        // ================= EXCEL FILES =================
        'xlsx' => 'Excel Files', 'xls' => 'Excel Files', 'csv' => 'Excel Files', 'ods' => 'Excel Files'
    ];

    // Returns the correct folder, or 'Other Files' if it's a completely unknown format
    return isset($folders[$extension]) ? $folders[$extension] : 'Other Files';
}

// 4. Security Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// 5. Handle Upload
if (isset($_FILES['file'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['file'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];

    // Generate Extension and Dynamic Folder Name
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $folderName = getTargetFolder($fileExt);

    // --- AUTOMATIC FOLDER ROUTING ---
    // A. Check if folder exists
    $stmtFolder = $conn->prepare("SELECT folder_id FROM folders WHERE folder_name = ? AND user_id = ?");
    $stmtFolder->bind_param("si", $folderName, $user_id);
    $stmtFolder->execute();
    $resFolder = $stmtFolder->get_result();

    if ($resFolder->num_rows > 0) {
        $target_folder_id = $resFolder->fetch_assoc()['folder_id'];
    } else {
        // B. Create if missing
        $stmtInsFolder = $conn->prepare("INSERT INTO folders (folder_name, user_id) VALUES (?, ?)");
        $stmtInsFolder->bind_param("si", $folderName, $user_id);
        $stmtInsFolder->execute();
        $target_folder_id = $stmtInsFolder->insert_id;
    }

    // Generate Unique Path
    $newFileName = uniqid('', true) . "." . $fileExt;
    $destination = 'uploads/' . $newFileName;

    if ($fileError === 0) {
        if (!is_dir('uploads/')) mkdir('uploads/', 0755, true);

        if (move_uploaded_file($fileTmpName, $destination)) {
            // Category for Sidebar
            $category = (in_array($fileExt, ['mp4', 'mkv', 'mp3', 'png', 'jpg'])) ? 'media' : 'books';

            // 6. Save Final Record
            $sql = "INSERT INTO files (user_id, folder_id, file_name, file_path, file_size, category, is_deleted) VALUES (?, ?, ?, ?, ?, ?, 0)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissis", $user_id, $target_folder_id, $fileName, $destination, $fileSize, $category);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
            }
        }
    }
}
?>