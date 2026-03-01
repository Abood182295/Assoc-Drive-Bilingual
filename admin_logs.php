<?php
session_start();
include 'init_lang.php';
$host = 'localhost:3307';
$user = 'root';
$pass = ''; // Default XAMPP pass is empty
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// 1. Security Gatekeeper: Ensure only logged-in users can see this
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Fetch Logs: Join tables to get file names and uploader/downloader names
$sql = "SELECT 
            l.download_time, 
            f.file_name, 
            u.full_name AS downloader_name 
        FROM download_logs l
        JOIN files f ON l.file_id = f.file_id
        JOIN users u ON l.user_id = u.user_id
        ORDER BY l.download_time DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
    <head>
    <meta charset="UTF-8">
    <title>Download Audit Logs - Assoc. Drive</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: <?php echo $font_family; ?>; background: #f4f7f6; padding: 40px; }
        .page-lang-switcher { text-align: center; margin-bottom: 20px; }
        .lang-btn { background: #eee; border: 1px solid #ddd; padding: 5px 15px; border-radius: 20px; cursor: pointer; }
        .container { max-width: 900px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h2 { color: #333; display: flex; align-items: center; gap: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #555; }
        .back-btn { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #007bff; font-weight: 500; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="back-btn"><i class='bx bx-left-arrow-alt'></i> Back to Dashboard</a>
    <h2><i class='bx bx-list-ul'></i> System Download Logs</h2>
    
    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>File Name</th>
                <th>Downloaded By</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['download_time'])); ?></td>
                        <td><strong><?php echo htmlspecialchars($row['file_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['downloader_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">No download activity recorded yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>