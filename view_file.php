<?php
session_start();
include 'init_lang.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$file_path = isset($_GET['path']) ? $_GET['path'] : '';
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview - Assoc. Drive</title>
    <style>
    body { font-family: <?php echo $font_family; ?>; }

    .page-lang-switcher { text-align: center; margin-bottom: 20px; }
    .lang-btn { background: #eee; border: 1px solid #ddd; padding: 5px 15px; border-radius: 20px; cursor: pointer; }
    

    .view-header { 
        position: fixed; top: 20px; right: 20px; z-index: 1000;
        display: flex; gap: 15px; align-items: center;
        background: rgba(255, 255, 255, 0.8); /* Semi-transparent background for buttons */
        padding: 10px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .header-icon {
        color: #333; font-size: 28px; cursor: pointer;
        text-decoration: none; display: flex; align-items: center; justify-content: center;
        transition: color 0.3s, transform 0.2s;
        background: none; border: none; padding: 0;
    }

    .header-icon:hover { transform: scale(1.1); }
    .zoom-in:hover { color: #007bff; }
    .zoom-out:hover { color: #6c757d; }
    .print-icon:hover { color: #28a745; }
    .close-icon:hover { color: #dc3545; }

    /* The Container that holds the image for zooming */
    .preview-container {
        width: 100%; height: 100%;
        display: flex; align-items: center; justify-content: center;
        overflow: auto; /* Allows scrolling when zoomed in */
    }

    .preview-img {
        max-width: 90%; max-height: 90vh;
        object-fit: contain; transition: transform 0.2s ease-in-out;
        border-radius: 8px; box-shadow: 0 4px 25px rgba(0,0,0,0.1);
    }

    iframe { width: 90%; height: 90vh; border: none; }
</style>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<div class="view-header">
    <?php if ($is_image): ?>
        <button onclick="zoomImage(1.2)" class="header-icon zoom-in" title="Zoom In"><i class='bx bx-zoom-in'></i></button>
        <button onclick="zoomImage(0.8)" class="header-icon zoom-out" title="Zoom Out"><i class='bx bx-zoom-out'></i></button>
    <?php endif; ?>

    <button onclick="printFile()" class="header-icon print-icon" title="Print File"><i class='bx bx-printer'></i></button>
    <a href="dashboard.php" class="header-icon close-icon" title="Close Preview"><i class='bx bx-x'></i></a>
</div>

<div class="preview-container">
    <?php if ($is_image): ?>
        <img src="<?php echo htmlspecialchars($file_path); ?>" class="preview-img" id="zoomableImage">
    <?php else: ?>
        <iframe src="<?php echo htmlspecialchars($file_path); ?>"></iframe>
    <?php endif; ?>
</div>

<script>
let currentScale = 1;

function zoomImage(factor) {
    const img = document.getElementById('zoomableImage');
    if (!img) return;
    currentScale *= factor;
    // Bounds for stability
    if (currentScale < 0.5) currentScale = 0.5;
    if (currentScale > 3) currentScale = 3;
    
    img.style.transform = `scale(${currentScale})`;
}

function printFile() {
    <?php if ($is_image): ?>
        window.print();
    <?php else: ?>
        const frame = document.querySelector('iframe');
        frame.contentWindow.focus();
        frame.contentWindow.print();
    <?php endif; ?>
}
</script>

</body>
</html>