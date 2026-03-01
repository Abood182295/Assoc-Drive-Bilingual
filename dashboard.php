<?php
// 1. Session and Security Check
session_start();
include 'init_lang.php'; 
include 'auto_purge.php'; // Runs the 30-day cleanup silently

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Database Connection (Hail Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

// 3. View & Folder Logic
$current_user   = $_SESSION['user_id'];
$view           = isset($_GET['view']) ? $_GET['view'] : 'all';
$searchTerm     = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$current_folder = isset($_GET['folder']) ? (int)$_GET['folder'] : null;

// 4. Storage Calculation (32GB Limit)
$total_limit = 32 * 1024 * 1024 * 1024; 
$storage_query = "SELECT SUM(file_size) AS total FROM files WHERE user_id = '$current_user' AND is_deleted = 0";
$storage_result = $conn->query($storage_query);
$used_size = ($storage_result && $row = $storage_result->fetch_assoc()) ? ($row['total'] ?? 0) : 0;
$storage_percentage = ($used_size / $total_limit) * 100;
$used_gb = round($used_size / (1024 * 1024 * 1024), 2);
$bar_color = ($storage_percentage >= 90) ? '#dc3545' : (($storage_percentage >= 70) ? '#fd7e14' : '#007bff');

// 5. Query Routing (Search + Folders Integration)
$sql = "SELECT * FROM files WHERE user_id = '$current_user' AND is_deleted = " . ($view == 'trash' ? "1" : "0");

if ($view == 'books') {
    $sql .= " AND category = 'books'";
} elseif ($view == 'recent') {
    $sql .= " ORDER BY upload_date DESC LIMIT 10";
} elseif (!empty($searchTerm)) {
    $sql .= " AND file_name LIKE '%$searchTerm%'";
} elseif ($current_folder) {
    $sql .= " AND folder_id = $current_folder";
} else {
    $sql .= " AND folder_id IS NULL"; 
}

if ($view != 'recent') $sql .= " ORDER BY upload_date DESC";
$result = $conn->query($sql);

// 6. Fetch Folders
$folders_res = $conn->query("SELECT * FROM folders WHERE user_id = '$current_user'");
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['site_title']; ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; margin: 0; display: flex; }
        
        .sidebar { width: 260px; background: #fff; height: 100vh; padding: 25px; position: fixed; display: flex; flex-direction: column; 
                   <?php echo ($curr_lang == 'ar') ? 'right: 0; border-left: 1px solid #ddd;' : 'left: 0; border-right: 1px solid #ddd;'; ?> }
        .nav-links { list-style: none; padding: 0; margin: 15px 0; flex-grow: 1; }
        .nav-links a { text-decoration: none; color: #444; display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; transition: 0.3s; }
        .nav-links a.active { background: #e7f1ff; color: #007bff; font-weight: 700; }
        
        .main-content { <?php echo ($curr_lang == 'ar') ? 'margin-right: 260px;' : 'margin-left: 260px;'; ?> padding: 40px 60px; width: calc(100% - 260px); box-sizing: border-box; }
        
        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-container { position: relative; width: 300px; }
        .search-container input { width: 100%; padding: 10px 40px 10px 15px; border-radius: 20px; border: 1px solid #ddd; outline: none; font-family: inherit; }
        .search-container i { position: absolute; <?php echo ($curr_lang == 'ar' ? 'left: 15px;' : 'right: 15px;'); ?> top: 12px; color: #888; }

        .upload-zone { border: 2px dashed #007bff; border-radius: 15px; padding: 30px; text-align: center; color: #007bff; background: #f0f7ff; margin-bottom: 30px; transition: 0.3s; }
        .upload-zone.dragover { background: #e1efff; border-color: #0056b3; transform: scale(1.01); }
        .btn-upload { background: #007bff; color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer; margin-top: 15px; font-weight: bold; font-family: inherit; display: inline-flex; align-items: center; gap: 8px; }

        .folder-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .folder-card { background: white; padding: 15px; border-radius: 10px; border: 1px solid #eee; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: 0.2s; }
        .folder-card:hover { background: #fdfdfd; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

        .file-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .file-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; position: relative; }

        /* Modernized Modal Controls */
        .modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); backdrop-filter: blur(5px); }
        .modal-wrapper { display: flex; justify-content: center; align-items: center; height: 100vh; position: relative; }
        #imgPreview, #videoPlayer, .doc-iframe { max-width: 85%; max-height: 85vh; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); background: #fff; }
        .modal-controls { position: absolute; top: 20px; right: 20px; display: flex; gap: 15px; z-index: 10; }
        .control-btn { width: 50px; height: 50px; border: none; border-radius: 50%; cursor: pointer; font-size: 24px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.3); transition: 0.3s; }
        .print-btn { background: #fff; color: #333; }
        .close-btn { background: #ff4d4d; color: white; }
        .close-btn:hover { background: #e63b3b; transform: rotate(90deg); }
    </style>
</head>
<body>

<nav class="sidebar">
    <h2 style="font-size: 18px;"><i class='bx bxs-hdd' style="color: #007bff;"></i> <?php echo $lang['site_title']; ?></h2>
    <form method="POST" style="margin-bottom:15px;">
        <select name="language" onchange="this.form.submit()" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid #eee; font-family: inherit;">
            <option value="ar" <?php echo ($curr_lang == 'ar' ? 'selected' : ''); ?>>العربية</option>
            <option value="en" <?php echo ($curr_lang == 'en' ? 'selected' : ''); ?>>English</option>
        </select>
        <input type="hidden" name="lang_switch" value="1">
    </form>

    <ul class="nav-links">
        <li><a href="?view=all" class="<?php echo ($view == 'all' ? 'active' : ''); ?>"><i class='bx bx-folder'></i> <?php echo $lang['all_files']; ?></a></li>
        <li><a href="?view=recent" class="<?php echo ($view == 'recent' ? 'active' : ''); ?>"><i class='bx bx-time'></i> <?php echo $lang['recent_files']; ?></a></li>
        <li><a href="?view=books" class="<?php echo ($view == 'books' ? 'active' : ''); ?>"><i class='bx bx-book'></i> <?php echo $lang['dawah_books']; ?></a></li>
        <li><a href="?view=trash" class="<?php echo ($view == 'trash' ? 'active' : ''); ?>"><i class='bx bx-trash'></i> <?php echo $lang['trash']; ?></a></li>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
        <li><a href="settings.php"><i class='bx bx-cog'></i> <?php echo $lang['settings']; ?></a></li>
        <li><a href="logout.php" style="color: #dc3545;"><i class='bx bx-log-out'></i> <?php echo $lang['logout']; ?></a></li>
    </ul>

    <div style="padding: 15px; background: #fcfcfc; border-radius: 10px; border: 1px solid #eee;">
        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold; margin-bottom: 5px;">
            <span><?php echo ($curr_lang == 'ar' ? 'المساحة' : 'Storage'); ?></span>
            <span style="color: <?php echo $bar_color; ?>;"><?php echo round($storage_percentage, 1); ?>%</span>
        </div>
        <div style="width: 100%; height: 8px; background: #e9ecef; border-radius: 10px; overflow: hidden;">
            <div style="width: <?php echo min($storage_percentage, 100); ?>%; height: 100%; background: <?php echo $bar_color; ?>; transition: 0.5s;"></div>
        </div>
        <p style="font-size: 10px; color: #888; margin-top: 5px; text-align: center;"><?php echo $used_gb; ?> GB / 32 GB</p>
    </div>
</nav>

<main class="main-content">
    <div class="header-top">
        <h3><?php 
            if($view == 'trash') echo $lang['trash'];
            elseif($view == 'books') echo $lang['dawah_books'];
            elseif($view == 'recent') echo $lang['recent_files'];
            else echo $lang['my_files_title'];
        ?></h3>

        <?php if ($view == 'trash'): ?>
            <button onclick="emptyTrashConfirm()" style="background:#dc3545; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer;">
                <i class='bx bx-trash'></i> <?php echo ($curr_lang == 'ar' ? 'إفراغ السلة' : 'Empty Trash'); ?>
            </button>
        <?php endif; ?>
        
        <form action="" method="GET" class="search-container">
            <input type="hidden" name="view" value="<?php echo $view; ?>">
            <input type="text" name="search" placeholder="<?php echo $lang['search_placeholder']; ?>" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <i class='bx bx-search'></i>
        </form>
    </div>

    <?php if ($view != 'trash'): ?>
        <div id="drop-zone" class="upload-zone">
            <i class='bx bxs-cloud-upload' style="font-size: 40px;"></i>
            <p><?php echo ($curr_lang == 'ar' ? 'اسحب الملفات هنا أو استخدم الزر' : 'Drag & Drop files here or use the button'); ?></p>
            <input type="file" id="fileInput" style="display: none;" onchange="uploadFile(this.files[0])">
            <button class="btn-upload" onclick="document.getElementById('fileInput').click()">
                <i class='bx bx-plus'></i> <?php echo ($curr_lang == 'ar' ? 'رفع ملف' : 'Upload File'); ?>
            </button>
        </div>
    <?php endif; ?>

    <?php if (!$searchTerm && $folders_res && $folders_res->num_rows > 0): ?>
        <h4 style="color:#888;">Folders</h4>
        <div class="folder-grid">
            <?php while($f = $folders_res->fetch_assoc()): ?>
                <div class="folder-card" onclick="location.href='?folder=<?php echo $f['folder_id']; ?>'">
                    <i class='bx bxs-folder' style="color: #ffca28; font-size: 24px;"></i>
                    <span><?php echo htmlspecialchars($f['folder_name']); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <h4 style="color:#888;">Files</h4>
    <div class="file-grid">
        
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()):
                $days_left_text = "";
if ($view == 'trash' && !empty($row['deleted_at'])) {
    $deleted_date = new DateTime($row['deleted_at']);
    $expiry_date  = clone $deleted_date;
    $expiry_date->modify('+30 days');
    $today = new DateTime();
    
    // Calculate difference
    $interval = $today->diff($expiry_date);
    $days_num = (int)$interval->format('%r%a'); // %r shows '-' if overdue
    
    // Determine the label based on language
    $unit = ($curr_lang == 'ar') ? 'يوم' : 'd';
    
    if ($days_num <= 0) {
        $days_left_text = "0 " . $unit;
    } else {
        $days_left_text = $days_num . " " . $unit;
    }
} ?>
                
                <div class="file-card">
                    <?php 
                        $fileExt = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION)); 
                        $iconColor = '#7f8c8d'; $icon = 'bxs-file';
                        if ($fileExt == 'mp4') { $icon = 'bxs-video'; $iconColor = '#007bff'; }
                        elseif ($fileExt == 'pdf') { $icon = 'bxs-file-pdf'; $iconColor = '#e74c3c'; }
                        elseif ($fileExt == 'mp3') { $icon = 'bxs-music'; $iconColor = '#8e44ad'; }
                        elseif (in_array($fileExt, ['png', 'jpg', 'jpeg'])) { $icon = 'bxs-file-image'; $iconColor = '#f1c40f'; }
                    ?>
                    <i class='bx <?php echo $icon; ?>' style="font-size: 55px; color: <?php echo $iconColor; ?>;"></i>
                    <div style="font-size: 14px; margin-top: 12px; font-weight: 700;"><?php echo htmlspecialchars($row['file_name']); ?></div>
                    <?php if ($view == 'trash'): ?>
                        <div style="font-size: 12px; color: #999; margin-top: 5px;"><?php echo $days_left_text; ?></div>
                    <?php endif; ?>

                    <div class="file-actions" style="display: flex; justify-content: center; gap: 12px; margin-top: 15px; border-top: 1px solid #eee; padding-top: 10px;">
                        <?php if ($view == 'trash'): ?>
                            <span style="font-size: 11px; font-weight: bold; color: <?php echo ($days_num <= 5) ? '#dc3545' : '#888'; ?>; display: flex; align-items: center; gap: 4px; margin-right: 8px;" title="Days until auto-purge">
                            <i class='bx bx-time-five'></i> <?php echo $days_left_text; ?>
                             </span>
                            <a href="javascript:void(0)" onclick="confirmRestore(<?php echo (int)$row['file_id']; ?>)" style="color:#28a745;"><i class='bx bx-undo'></i></a>
                            <a href="javascript:void(0)" onclick="confirmPermanentDelete(<?php echo (int)$row['file_id']; ?>)" style="color:#dc3545;"><i class='bx bx-x-circle'></i></a>
                        <?php else: ?>
                            <a href="javascript:void(0)" onclick="handlePreview('<?php echo addslashes($row['file_path']); ?>', '<?php echo $fileExt; ?>')" style="color:#007bff;"><i class='bx bx-show-alt'></i></a>
                            <a href="download.php?id=<?php echo $row['file_id']; ?>"><i class='bx bx-download'></i></a>
                            <a href="javascript:void(0)" onclick="confirmTrash(<?php echo (int)$row['file_id']; ?>, '<?php echo addslashes($row['file_name']); ?>')" style="color:#ff4d4d;"><i class='bx bx-trash'></i></a>
                        <?php endif; ?>
                    </div>
                </div> <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1/-1; color: #999;"><?php echo $lang['no_files_here']; ?></p>
        <?php endif; ?>
    </div>
</main>
<div id="audioModal" class="modal"><div class="modal-wrapper"><div class="modal-controls"><button class="control-btn close-btn" onclick="closeModal('audioModal', 'audioPlayer')"><i class='bx bx-x'></i></button></div><div style="background: white; padding: 40px; border-radius: 15px; text-align: center; width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);"><i class='bx bxs-music' style="font-size: 70px; color: #8e44ad; margin-bottom: 20px;"></i><h4 id="audioTitle" style="margin-bottom: 20px; color: #333;"><?php echo ($curr_lang == 'ar' ? 'معاينة الصوت' : 'Audio Preview'); ?></h4><audio id="audioPlayer" controls style="width: 100%;"><source id="audioSource" src="" type="audio/mpeg"></audio></div></div></div>
<div id="videoModal" class="modal"><div class="modal-wrapper"><div class="modal-controls"><button class="control-btn close-btn" onclick="closeModal('videoModal', 'videoPlayer')"><i class='bx bx-x'></i></button></div><video id="videoPlayer" controls><source id="videoSource" src="" type="video/mp4"></video></div></div>
<div id="imageModal" class="modal"><div class="modal-wrapper"><div class="modal-controls"><button class="control-btn print-btn" onclick="printFile('imgPreview')"><i class='bx bx-printer'></i></button><button class="control-btn close-btn" onclick="closeModal('imageModal')"><i class='bx bx-x'></i></button></div><img id="imgPreview" src=""></div></div>
<div id="docModal" class="modal"><div class="modal-wrapper"><div class="modal-controls"><button class="control-btn print-btn" onclick="printDocFrame()"><i class='bx bx-printer'></i></button><button class="control-btn close-btn" onclick="closeModal('docModal')"><i class='bx bx-x'></i></button></div><iframe id="docFrame" class="doc-iframe" src=""></iframe></div></div>
<script>
    const isArabic = "<?php echo $curr_lang; ?>" === 'ar';
    const dropZone = document.getElementById('drop-zone');

    // Key handlers
    document.addEventListener('keydown', (e) => {
        if (e.key === "Escape") { closeModal('videoModal', 'videoPlayer'); closeModal('imageModal'); closeModal('docModal'); }
    });

    // Trash bin logic
    function confirmRestore(id) {
        Swal.fire({ title: isArabic ? 'استعادة؟' : 'Restore?', icon: 'question', showCancelButton: true }).then((res) => { if (res.isConfirmed) window.location.href = `restore_handler.php?id=${id}`; });
    }
    function confirmPermanentDelete(id) {
        Swal.fire({ title: isArabic ? 'حذف نهائي؟' : 'Delete Forever?', icon: 'error', showCancelButton: true }).then((res) => { if (res.isConfirmed) window.location.href = `permanent_delete.php?id=${id}`; });
    }
 function emptyTrashConfirm() {
    Swal.fire({
        title: isArabic ? 'إفراغ سلة المهملات؟' : 'Empty Trash?',
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: isArabic ? 'إفراغ' : 'Empty'
    }).then((res) => { 
        if (res.isConfirmed) window.location.href = 'empty_trash_handler.php'; 
    });
}

    // Existing Functions
    function confirmTrash(id, name) { 
        Swal.fire({ title: isArabic ? 'نقل للسلة؟' : 'Move to Trash?', text: name, icon: 'warning', showCancelButton: true }).then((res) => { if (res.isConfirmed) window.location.href = `delete_handler.php?id=${id}`; }); 
    }
    function uploadFile(file) {
        if (!file) return;
        let formData = new FormData(); formData.append('file', file); formData.append('folder_id', '<?php echo $current_folder; ?>');
        Swal.fire({ title: isArabic ? 'جاري الرفع...' : 'Uploading...', didOpen: () => { Swal.showLoading(); } });
        fetch('upload_handler.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) location.reload(); else Swal.fire('Error', data.message, 'error'); });
    }
function handlePreview(path, ext) {
    // Standardize path for web browsers (XAMPP compatibility)
    const cleanPath = path.replace(/\\/g, '/') + "?t=" + new Date().getTime();

    if (ext === 'mp3') {
        const aud = document.getElementById("audioPlayer");
        document.getElementById("audioSource").src = cleanPath;
        aud.load();
        document.getElementById("audioModal").style.display = "block";
        aud.play(); // Direct playback for MP3s
    } else if (ext === 'mp4') {
        const vid = document.getElementById("videoPlayer");
        document.getElementById("videoSource").src = cleanPath;
        vid.load();
        document.getElementById("videoModal").style.display = "block";
        vid.play();
    } else if (['png', 'jpg', 'jpeg'].includes(ext)) {
        document.getElementById("imgPreview").src = cleanPath;
        document.getElementById("imageModal").style.display = "block";
    } else {
        // Document routing using Google Viewer
        const fullUrl = encodeURIComponent(window.location.origin + '/' + cleanPath);
        document.getElementById("docFrame").src = "https://docs.google.com/viewer?url=" + fullUrl + "&embedded=true";
        document.getElementById("docModal").style.display = "block";
    }
}
    function closeModal(mId, pId=null) { document.getElementById(mId).style.display = "none"; if(pId) document.getElementById(pId).pause(); }
    function printFile(elementId) {
        const src = document.getElementById(elementId).src;
        const pw = window.open('', '_blank'); pw.document.write(`<html><body onload="window.print(); window.close();"><img src="${src}" style="width:100%;"></body></html>`); pw.document.close();
    }
</script>
</body>
</html>