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

// 5. Main File Query Routing
$sql = "SELECT * FROM files WHERE user_id = '$current_user' AND is_deleted = " . ($view == 'trash' ? "1" : "0");

if ($view == 'books') {
    $sql .= " AND category = 'books'";
} elseif ($view == 'recent') {
    $sql .= " ORDER BY upload_date DESC LIMIT 10";
} elseif (!empty($searchTerm)) {
    $sql .= " AND file_name LIKE '%$searchTerm%'";
} elseif ($current_folder) {
    $sql .= " AND folder_id = $current_folder";
} elseif ($view == 'all') { 
    $sql .= " AND folder_id IS NULL"; 
}

if ($view != 'recent') $sql .= " ORDER BY upload_date DESC";
$result = $conn->query($sql);

// 6. Fetch Folders (SMART QUERY: Shows deleted folders in Trash view, active folders elsewhere)
$folder_query = "SELECT * FROM folders WHERE user_id = '$current_user'";
$folder_query .= ($view == 'trash') ? " AND is_deleted = 1" : " AND is_deleted = 0";
$folders_res = $conn->query($folder_query);

// 7. Helper Function for File Icons
function getFileStyle($path) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'mkv', 'mov'])) return ['icon' => 'bxs-video', 'color' => '#007bff'];
    if ($ext == 'pdf') return ['icon' => 'bxs-file-pdf', 'color' => '#e74c3c'];
    if (in_array($ext, ['mp3', 'm4a', 'wav', 'ogg'])) return ['icon' => 'bxs-music', 'color' => '#8e44ad'];
    if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) return ['icon' => 'bxs-file-image', 'color' => '#f1c40f'];
    if (in_array($ext, ['xlsx', 'xls', 'csv'])) return ['icon' => 'bxs-spreadsheet', 'color' => '#28a745'];
    return ['icon' => 'bxs-file', 'color' => '#7f8c8d'];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['site_title']; ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* CORE THEME & VARIABLES */
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');

        :root {
            --primary: #007bff;
            --primary-light: #e7f1ff;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --text-dark: #333333;
            --text-muted: #888888;
            --danger: #dc3545;
            --sidebar-w: 260px;
            --sidebar-collapsed-w: 80px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        * { box-sizing: border-box; }

        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: var(--bg-light); 
            margin: 0; 
            display: flex; 
            font-size: clamp(0.875rem, 1vw + 0.5rem, 1rem);
            overflow-x: hidden;
            color: var(--text-dark);
        }

        /* SIDEBAR */
        .sidebar { width: 360px; transition: var(--transition); overflow-x: hidden;}
        .sidebar.collapsed { width: 80px; padding: 25px 15px; }
        .sidebar.collapsed .sidebar-text { display: none !important; }
        .sidebar.collapsed .nav-links a { justify-content: center; gap: 0; }
        .sidebar.collapsed .nav-links i { font-size: 24px; }

        .nav-links { list-style: none; padding: 0; margin: 15px 0; flex-grow: 1; }
        .nav-links a { text-decoration: none; color: #444; display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; transition: 0.3s ease; }
        .nav-links a.active { background: var(--primary-light); color: var(--primary); font-weight: 700; }
        .nav-links a:hover { background: #f0f7ff; color: var(--primary); }

        /* MAIN CONTENT */
        .main-content { 
            padding: clamp(20px, 5vw, 60px); 
            width: calc(100% - var(--sidebar-w)); 
            transition: var(--transition);
            <?php echo ($curr_lang == 'ar') ? 'margin-right: var(--sidebar-w);' : 'margin-left: var(--sidebar-w);'; ?> 
        }

        .main-content.expanded {
            width: calc(100% - var(--sidebar-collapsed-w));
            <?php echo ($curr_lang == 'ar') ? 'margin-right: var(--sidebar-collapsed-w);' : 'margin-left: var(--sidebar-collapsed-w);'; ?>
        }

        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 20px; }

        /* SEARCH & INPUTS */
        .search-container { position: relative; width: 100%; max-width: 350px; }
        .search-container input { width: 100%; padding: 12px 40px 12px 15px; border-radius: 20px; border: 1px solid #ddd; outline: none; font-family: inherit; transition: 0.3s; }
        .search-container input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); }
        .search-container i { position: absolute; top: 50%; transform: translateY(-50%); color: var(--text-muted); <?php echo ($curr_lang == 'ar' ? 'left: 15px;' : 'right: 15px;'); ?> }
        .search-suggestions-box { position: absolute; top: 105%; left: 0; right: 0; background: var(--white); border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); z-index: 2000; display: none; overflow: hidden; }

        /* CARDS & GRIDS */
        .folder-grid, .file-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; margin-bottom: 35px; }

        .folder-card, .file-card { 
            background: var(--white); padding: 22px; border-radius: 15px; 
            box-shadow: var(--shadow); text-align: center; 
            position: relative; transition: var(--transition); cursor: pointer; 
            border: 1px solid transparent; display: flex; flex-direction: column; align-items: center;
        }
        .folder-card:hover, .file-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        
        .folder-card i.main-icon, .file-card i.main-icon { font-size: 50px; margin-bottom: 10px; }
        .f-name { font-weight: 700; color: var(--text-dark); font-size: 1.05rem; display: block; margin-top: 10px; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .file-actions { display: flex !important; gap: 15px; margin-top: 15px; border-top: 1px solid #eee; padding-top: 12px; width: 100%; justify-content: center; }
        .file-actions a, .file-actions i { font-size: 20px; text-decoration: none; cursor: pointer; transition: 0.2s; color: #555; }
        .file-actions a:hover i { transform: scale(1.1); }

        .upload-zone { border: 2px dashed var(--primary); border-radius: 15px; padding: 30px; text-align: center; color: var(--primary); background: #f0f7ff; margin-bottom: 30px; transition: var(--transition); cursor: pointer; }
        .upload-zone:hover { background: var(--primary-light); }

        /* MODALS */
        .modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); backdrop-filter: blur(8px); }
        .modal-controls { position: fixed; top: 20px; z-index: 100000; display: flex; gap: 15px; <?php echo ($curr_lang == 'ar' ? 'left: 20px;' : 'right: 20px;'); ?> }
        .modal-btn { background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; transition: 0.3s; }
        .modal-btn:hover { background: var(--white); color: var(--text-dark); transform: scale(1.1); }
        .close-btn:hover { background: var(--danger); color: var(--white); border-color: var(--danger); }
        .modal-wrapper { display: flex; justify-content: center; align-items: center; height: 100vh; padding: 15px; }
        #imgPreview, #videoPlayer { max-width: 85%; max-height: 85vh; border-radius: 12px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); background: #000; }

        /* MOBILE OPTIMIZATION */
        @media screen and (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100% !important; height: auto; position: relative; border: none; border-bottom: 1px solid #ddd; padding: 15px; }
            .sidebar.collapsed { width: 100% !important; }
            .nav-links { display: flex; flex-direction: row; overflow-x: auto; gap: 8px; padding-bottom: 5px; }
            .nav-links a span { display: none !important; } 
            .main-content { margin: 0 !important; width: 100%; padding: 20px; }
            .header-top { flex-direction: column; align-items: stretch; }
            .folder-grid, .file-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
            .file-actions { gap: 20px !important; }
        }
    </style>
</head>
<body>

<nav class="sidebar" id="sidebar">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; overflow: hidden;">
        <h2 style="font-size: 18px; margin: 0; white-space: nowrap; display: flex; align-items: center; gap: 10px;">
            <i class='bx bxs-hdd' style="color: #007bff; font-size: 24px; flex-shrink: 0;"></i> 
            <span class="sidebar-text"><?php echo $lang['site_title']; ?></span>
        </h2>
        <button onclick="toggleSidebar()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #444; flex-shrink: 0;">
            <i class='bx bx-menu' id="toggleIcon"></i>
        </button>
    </div>

    <div class="sidebar-text">
        <form method="POST" style="margin-bottom:15px;">
            <select name="language" onchange="this.form.submit()" style="width: 100%; padding: 6px; border-radius: 6px; border: 1px solid #eee; font-family: inherit;">
                <option value="ar" <?php echo ($curr_lang == 'ar' ? 'selected' : ''); ?>>العربية</option>
                <option value="en" <?php echo ($curr_lang == 'en' ? 'selected' : ''); ?>>English</option>
            </select>
            <input type="hidden" name="lang_switch" value="1">
        </form>
    </div>

    <ul class="nav-links">
        <li><a href="?view=all" class="<?php echo ($view == 'all' ? 'active' : ''); ?>"><i class='bx bx-folder'></i> <span class="sidebar-text"><?php echo $lang['all_files']; ?></span></a></li>
        <li><a href="?view=recent" class="<?php echo ($view == 'recent' ? 'active' : ''); ?>"><i class='bx bx-time'></i> <span class="sidebar-text"><?php echo $lang['recent_files']; ?></span></a></li>
        <li><a href="?view=books" class="<?php echo ($view == 'books' ? 'active' : ''); ?>"><i class='bx bx-book'></i> <span class="sidebar-text"><?php echo $lang['dawah_books']; ?></span></a></li>
        <li><a href="?view=trash" class="<?php echo ($view == 'trash' ? 'active' : ''); ?>"><i class='bx bx-trash'></i> <span class="sidebar-text"><?php echo $lang['trash']; ?></span></a></li>
        <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
        <li><a href="settings.php"><i class='bx bx-cog'></i> <span class="sidebar-text"><?php echo $lang['settings']; ?></span></a></li>
        <li><a href="logout.php" style="color: #dc3545;"><i class='bx bx-log-out'></i> <span class="sidebar-text"><?php echo $lang['logout']; ?></span></a></li>
    </ul>

    <div class="storage-box sidebar-text" style="padding: 15px; background: #fcfcfc; border-radius: 10px; border: 1px solid #eee; margin-top: auto;">
        <div style="display: flex; justify-content: space-between; font-size: 17px; font-weight: bold; margin-bottom: 5px;">
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
        <h3>
            <i class='bx <?php 
                if($view == 'trash') echo "bx-trash";
                elseif($view == 'books') echo "bx-book";
                elseif($view == 'recent') echo "bx-time";
                else echo "bx-folder";
            ?>'></i>
            <?php 
                if($view == 'trash') echo $lang['trash'];
                elseif($view == 'books') echo $lang['dawah_books'];
                elseif($view == 'recent') echo $lang['recent_files'];
                else echo $lang['my_files_title'];
            ?>
        </h3>

        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if ($view == 'trash'): ?>
                <button onclick="emptyTrashConfirm()" style="background:#dc3545; color:white; border:none; padding:8px 15px; border-radius:8px; cursor:pointer; display:flex; align-items:center; gap:5px;">
                    <i class='bx bx-trash-alt'></i> <?php echo ($curr_lang == 'ar' ? 'إفراغ السلة' : 'Empty Trash'); ?>
                </button>
            <?php endif; ?>
            
            <div class="search-container">
                <input type="text" id="liveSearch" placeholder="<?php echo $lang['search_placeholder']; ?>" autocomplete="off">
                <i class='bx bx-search'></i>
                <div id="searchSuggestions" class="search-suggestions-box"></div>
            </div>
        </div>
    </div>

    <?php if ($view != 'trash'): ?>
        <div id="drop-zone" class="upload-zone" onclick="document.getElementById('fileInput').click()">
            <i class='bx bxs-cloud-upload' style="font-size: 40px;"></i>
            <p><?php echo ($curr_lang == 'ar' ? 'اسحب الملفات هنا أو انقر للرفع' : 'Drag & Drop files here or click to upload'); ?></p>
            <input type="file" id="fileInput" style="display: none;" onchange="uploadFile(this.files[0])">
        </div>
    <?php endif; ?>

    <?php if (!$searchTerm && $folders_res && $view != 'books' && $view != 'recent'): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; margin-bottom: 15px;">
            <h4 style="color:#888; margin: 0;">
                <i class='bx bx-folder-open'></i> <?php echo ($curr_lang == 'ar' ? 'المجلدات' : 'Folders'); ?>
                <?php if($view == 'trash') echo ($curr_lang == 'ar' ? ' المحذوفة' : ' (Deleted)'); ?>
            </h4>
            
            <?php if ($view != 'trash'): ?>
                <button onclick="createNewFolder()" style="background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 5px; font-family: inherit; transition: 0.2s;">
                    <i class='bx bx-folder-plus' style="font-size: 18px;"></i> 
                    <?php echo ($curr_lang == 'ar' ? 'مجلد جديد' : 'New Folder'); ?>
                </button>
            <?php endif; ?>
        </div>
        
        <?php if ($folders_res->num_rows > 0): ?>
            <div class="folder-grid">
                <?php while ($folder = $folders_res->fetch_assoc()):
                    $fName = trim($folder['folder_name']);
                    $fid   = $folder['folder_id'];
                    
                    $style = ['icon' => 'bxs-folder', 'color' => '#ffc107']; 
                    if (stripos($fName, 'Audio') !== false || stripos($fName, 'Music') !== false || stripos($fName, 'صوت') !== false) {
                        $style = ['icon' => 'bxs-music', 'color' => '#8e44ad'];
                    } elseif (stripos($fName, 'Video') !== false || stripos($fName, 'فيديو') !== false) {
                        $style = ['icon' => 'bxs-video', 'color' => '#007bff'];
                    } elseif (stripos($fName, 'Image') !== false || stripos($fName, 'صورة') !== false) {
                        $style = ['icon' => 'bxs-image', 'color' => '#f1c40f'];
                    } elseif (stripos($fName, 'Doc') !== false || stripos($fName, 'مستند') !== false) {
                        $style = ['icon' => 'bxs-file-doc', 'color' => '#e74c3c'];
                    }

                    $countRes = $conn->query("SELECT COUNT(*) as total FROM files WHERE folder_id = $fid");
                    $total = $countRes->fetch_assoc()['total'];
                ?>
                <div class="folder-card" <?php echo $view != 'trash' ? "onclick=\"window.location.href='view_folder.php?id=$fid'\"" : ""; ?>>
                    
                    <?php if ($view != 'trash'): ?>
                        <div style="position: absolute; top: 10px; <?php echo ($curr_lang == 'ar' ? 'left: 10px;' : 'right: 10px;'); ?>; display: flex; gap: 8px; z-index: 10;">
                            <i class='bx bx-edit-alt' 
                               style="font-size: 18px; color: #888; cursor: pointer; transition: 0.2s;" 
                               onmouseover="this.style.color='#007bff'" onmouseout="this.style.color='#888'"
                               onclick="event.stopPropagation(); confirmRename(<?php echo $fid; ?>, '<?php echo addslashes($fName); ?>')" title="<?php echo ($curr_lang == 'ar' ? 'إعادة تسمية' : 'Rename'); ?>">
                            </i>
                            <i class='bx bx-trash' 
                               style="font-size: 18px; color: #ff4d4d; cursor: pointer; transition: 0.2s;" 
                               onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
                               onclick="event.stopPropagation(); confirmFolderDelete(<?php echo $fid; ?>, '<?php echo addslashes($fName); ?>')" title="<?php echo ($curr_lang == 'ar' ? 'حذف المجلد' : 'Delete Folder'); ?>">
                            </i>
                        </div>
                    <?php else: ?>
                        <div style="position: absolute; top: 10px; <?php echo ($curr_lang == 'ar' ? 'left: 10px;' : 'right: 10px;'); ?>; display: flex; gap: 8px; z-index: 10;">
                            <i class='bx bx-undo' 
                               style="font-size: 18px; color: #28a745; cursor: pointer; transition: 0.2s;" 
                               onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
                               onclick="event.stopPropagation(); restoreFolder(<?php echo $fid; ?>)" title="<?php echo ($curr_lang == 'ar' ? 'استعادة' : 'Restore'); ?>">
                            </i>
                            <i class='bx bx-x-circle' 
                               style="font-size: 18px; color: #dc3545; cursor: pointer; transition: 0.2s;" 
                               onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"
                               onclick="event.stopPropagation(); permDeleteFolder(<?php echo $fid; ?>)" title="<?php echo ($curr_lang == 'ar' ? 'حذف نهائي' : 'Delete Permanently'); ?>">
                            </i>
                        </div>
                    <?php endif; ?>

                    <i class='bx <?php echo $style['icon']; ?> main-icon' style="color: <?php echo $style['color']; ?>;"></i>
                    <div class="folder-info">
                        <div class="f-name"><?php echo htmlspecialchars($fName); ?></div>
                        <small><?php echo $total; ?> <?php echo ($curr_lang == 'ar' ? 'ملفات' : 'Files'); ?></small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($view == 'all' && !$searchTerm): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <h4 style="color:#888; margin: 0;">
                <i class='bx bx-time-five'></i> <?php echo ($curr_lang == 'ar' ? 'أحدث الملفات المرفوعة' : 'Recent Uploads'); ?>
            </h4>
            
            <?php 
            $recent_res = $conn->query("SELECT * FROM files WHERE user_id = '$current_user' AND is_deleted = 0 ORDER BY upload_date DESC LIMIT 5");
            if ($recent_res && $recent_res->num_rows > 0): 
            ?>
                <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; color: #555;">
                    <input type="checkbox" id="masterSelectRecent" onclick="toggleSelectAll(this, 'recent-grid')" style="width: 18px; height: 18px; cursor: pointer;">
                    <span><?php echo ($curr_lang == 'ar' ? 'تحديد الكل' : 'Select All'); ?></span>
                </label>
            <?php endif; ?>
        </div>
        
        <div id="recent-grid" class="file-grid" style="background: rgba(0,0,0,0.02); padding: 15px; border-radius: 15px; margin-top: 15px;">
            <?php if ($recent_res && $recent_res->num_rows > 0): 
                while($row = $recent_res->fetch_assoc()): 
                    $fStyle = getFileStyle($row['file_path']);
                    $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
            ?>
                <div class="file-card" style="transform: scale(0.98);">
                    <input type="checkbox" class="file-checkbox" value="<?php echo $row['file_id']; ?>" onclick="event.stopPropagation(); updateSelection();" style="position: absolute; top: 12px; <?php echo ($curr_lang == 'ar' ? 'right: 12px;' : 'left: 12px;'); ?> width: 18px; height: 18px; cursor: pointer; z-index: 5;">
                    
                    <i class='bx <?php echo $fStyle['icon']; ?> main-icon' style="color: <?php echo $fStyle['color']; ?>;"></i>
                    <div class="f-name" title="<?php echo htmlspecialchars($row['file_name']); ?>"><?php echo htmlspecialchars($row['file_name']); ?></div>
                    
                    <div class="file-actions">
                        <a href="javascript:void(0)" onclick="handlePreview('<?php echo addslashes($row['file_path']); ?>', '<?php echo $ext; ?>')" style="color:#007bff;" title="Preview"><i class='bx bx-show-alt'></i></a>
                        <a href="download.php?id=<?php echo $row['file_id']; ?>" style="color:#333;" title="Download"><i class='bx bx-download'></i></a>
                        <a href="javascript:void(0)" onclick="confirmFileRename(<?php echo $row['file_id']; ?>, '<?php echo addslashes($row['file_name']); ?>', '<?php echo $ext; ?>')" style="color:#28a745;" title="Rename"><i class='bx bx-edit-alt'></i></a>
                        <a href="javascript:void(0)" onclick="confirmTrash(<?php echo $row['file_id']; ?>, '<?php echo addslashes($row['file_name']); ?>')" style="color:#ff4d4d;" title="Delete"><i class='bx bx-trash'></i></a>
                    </div>
                </div>
            <?php endwhile; else: ?>
                <p style="grid-column: 1/-1; text-align: center; color: #bbb; padding: 20px;"><?php echo ($curr_lang == 'ar' ? 'لا توجد ملفات مرفوعة مؤخراً' : 'No recent uploads found'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($view != 'all' || !empty($searchTerm) || $current_folder): ?>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
            <h4 style="color:#888; margin: 0;">
                <i class='bx bx-list-ul'></i>
                <?php 
                    if ($view == 'trash') echo ($curr_lang == 'ar' ? 'سلة المحذوفات' : 'Trash Bin');
                    else echo ($current_folder ? ($curr_lang == 'ar' ? 'ملفات المجلد' : 'Folder Files') : ($curr_lang == 'ar' ? 'الملفات العامة' : 'General Files')); 
                ?>
            </h4>

            <?php if ($result && $result->num_rows > 0): ?>
                <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; color: #555;">
                    <input type="checkbox" id="masterSelectGeneral" onclick="toggleSelectAll(this, 'general-grid')" style="width: 18px; height: 18px; cursor: pointer;">
                    <span><?php echo ($curr_lang == 'ar' ? 'تحديد الكل' : 'Select All'); ?></span>
                </label>
            <?php endif; ?>
        </div>
        
        <div id="general-grid" class="file-grid" style="margin-top: 20px;">
        <?php if ($result && $result->num_rows > 0): 
            while($row = $result->fetch_assoc()): 
                $fStyle = getFileStyle($row['file_path']);
                $ext = pathinfo($row['file_path'], PATHINFO_EXTENSION);
        ?>
            <div class="file-card">
                <input type="checkbox" class="file-checkbox" value="<?php echo $row['file_id']; ?>" 
                       onclick="event.stopPropagation(); updateSelection();" 
                       style="position: absolute; top: 12px; <?php echo ($curr_lang == 'ar' ? 'right: 12px;' : 'left: 12px;'); ?> width: 18px; height: 18px; cursor: pointer; z-index: 5;">

                <i class='bx <?php echo $fStyle['icon']; ?> main-icon' style="color: <?php echo $fStyle['color']; ?>;"></i>
                <div class="f-name" title="<?php echo htmlspecialchars($row['file_name']); ?>">
                    <?php echo htmlspecialchars($row['file_name']); ?>
                </div>
                
                <div class="file-actions">
                    <?php if ($view == 'trash'): ?>
                        <a href="javascript:void(0)" onclick="confirmRestore(<?php echo $row['file_id']; ?>)" style="color:#28a745;" title="Restore"><i class='bx bx-undo'></i></a>
                        <a href="javascript:void(0)" onclick="confirmPermanentDelete(<?php echo $row['file_id']; ?>)" style="color:#dc3545;" title="Delete Forever"><i class='bx bx-x-circle'></i></a>
                    <?php else: ?>
                        <a href="javascript:void(0)" onclick="handlePreview('<?php echo addslashes($row['file_path']); ?>', '<?php echo $ext; ?>')" style="color:#007bff;" title="Preview"><i class='bx bx-show-alt'></i></a>
                        <a href="download.php?id=<?php echo $row['file_id']; ?>" style="color:#333;" title="Download"><i class='bx bx-download'></i></a>
                        <a href="javascript:void(0)" onclick="confirmFileRename(<?php echo $row['file_id']; ?>, '<?php echo addslashes($row['file_name']); ?>', '<?php echo $ext; ?>')" style="color:#28a745;" title="Rename"><i class='bx bx-edit-alt'></i></a>
                        <a href="javascript:void(0)" onclick="confirmTrash(<?php echo $row['file_id']; ?>, '<?php echo addslashes($row['file_name']); ?>')" style="color:#ff4d4d;" title="Delete"><i class='bx bx-trash'></i></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #bbb; padding: 40px;">
                <?php echo ($curr_lang == 'ar' ? 'لا توجد ملفات هنا' : 'No files found here'); ?>
            </p>
        <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<div id="audioModal" class="modal">
    <div class="modal-wrapper" onclick="closeModal('audioModal', 'audioPlayer')">
        <div style="position: relative; background: white; padding: 30px 20px; border-radius: 15px; text-align: center; width: 90%; max-width: 350px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);" onclick="event.stopPropagation();">
            <button onclick="closeModal('audioModal', 'audioPlayer')" style="position: absolute; top: -15px; <?php echo ($curr_lang == 'ar' ? 'left: -15px;' : 'right: -15px;'); ?> background: #dc3545; color: white; border: 3px solid white; border-radius: 50%; width: 45px; height: 45px; font-size: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 100000; padding: 0;">
                <i class='bx bx-x'></i>
            </button>
            <i class='bx bxs-music' style="font-size: 60px; color: #8e44ad;"></i>
            <h4 id="audioTitle" style="color: #333; margin: 15px 0;"></h4>
            <audio id="audioPlayer" controls style="width: 100%; outline: none;"><source id="audioSource" src=""></audio>
        </div>
    </div>
</div>

<div id="videoModal" class="modal">
    <div class="modal-controls">
        <button onclick="closeModal('videoModal', 'videoPlayer')" class="modal-btn close-btn" title="<?php echo ($curr_lang == 'ar' ? 'إغلاق' : 'Close'); ?>"><i class='bx bx-x'></i></button>
    </div>
    <div class="modal-wrapper" onclick="closeModal('videoModal', 'videoPlayer')">
        <video id="videoPlayer" controls onclick="event.stopPropagation();"><source id="videoSource" src=""></video>
    </div>
</div>

<div id="imageModal" class="modal">
    <div class="modal-controls">
        <button onclick="printImage()" class="modal-btn" title="<?php echo ($curr_lang == 'ar' ? 'طباعة' : 'Print'); ?>"><i class='bx bx-printer'></i></button>
        <button onclick="closeModal('imageModal')" class="modal-btn close-btn" title="<?php echo ($curr_lang == 'ar' ? 'إغلاق' : 'Close'); ?>"><i class='bx bx-x'></i></button>
    </div>
    <div class="modal-wrapper" onclick="closeModal('imageModal')">
        <img id="imgPreview" src="" onclick="event.stopPropagation();">
    </div>
</div>   

<div id="bulkActionBar" style="display: none; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 15px 30px; border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); z-index: 99999; align-items: center; gap: 20px;">
    <span id="selectionCount" style="font-weight: bold; border-right: 1px solid #555; padding-right: 15px;">0 Selected</span>
    <div style="display: flex; gap: 15px;">
        <?php if ($view == 'trash'): ?>
            <button onclick="bulkRestore()" style="background: #28a745; border: none; color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                <i class='bx bx-undo'></i> <?php echo ($curr_lang == 'ar' ? 'استعادة المحدد' : 'Restore Selected'); ?>
            </button>
            <button onclick="bulkPermanentDelete()" style="background: #dc3545; border: none; color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                <i class='bx bx-x-circle'></i> <?php echo ($curr_lang == 'ar' ? 'حذف نهائي' : 'Delete Permanently'); ?>
            </button>
        <?php else: ?>
            <button onclick="bulkDelete()" style="background: #ff4d4d; border: none; color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
                <i class='bx bx-trash'></i> <?php echo ($curr_lang == 'ar' ? 'حذف المحدد' : 'Delete Selected'); ?>
            </button>
        <?php endif; ?>
        <button onclick="cancelSelection()" style="background: transparent; border: 1px solid #888; color: #ccc; padding: 8px 15px; border-radius: 20px; cursor: pointer;">
            <?php echo ($curr_lang == 'ar' ? 'إلغاء' : 'Cancel'); ?>
        </button>
    </div>
</div>

<script>
    const isArabic = "<?php echo $curr_lang; ?>" === 'ar';
    const EXTENSIONS = {
        audio: ['mp3', 'm4a', 'ogg', 'wav', 'flac', 'aac', 'opus', 'wma'],
        video: ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv'],
        image: ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp']
    };

    async function uploadFile(file) {
        if (!file) return;
        let formData = new FormData();
        formData.append('file', file);
        Swal.fire({ title: isArabic ? 'جاري الرفع...' : 'Uploading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        try {
            const response = await fetch('upload_handler.php', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.success) location.reload();
            else Swal.fire('Error', data.message, 'error');
        } catch (error) { Swal.fire('Error', isArabic ? 'فشل الاتصال بالخادم' : 'Server connection failed', 'error'); }
    }

    function handlePreview(path, ext) {
        const cleanPath = path.replace(/\\/g, '/') + "?t=" + new Date().getTime();
        if (EXTENSIONS.audio.includes(ext)) {
            const player = document.getElementById("audioPlayer");
            document.getElementById("audioSource").src = cleanPath;
            player.load();
            document.getElementById("audioModal").style.display = "block";
            player.play();
        } 
        else if (EXTENSIONS.video.includes(ext)) {
            const player = document.getElementById("videoPlayer");
            document.getElementById("videoSource").src = cleanPath;
            player.load();
            document.getElementById("videoModal").style.display = "block";
            player.play();
        } 
        else if (EXTENSIONS.image.includes(ext)) {
            document.getElementById("imgPreview").src = cleanPath;
            document.getElementById("imageModal").style.display = "block";
        } else { window.open(cleanPath, '_blank'); }
    }

    function closeModal(mId, pId = null) {
        document.getElementById(mId).style.display = "none";
        if (pId) { const player = document.getElementById(pId); if (player) player.pause(); }
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');
        const icon = document.getElementById('toggleIcon');
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        icon.className = sidebar.classList.contains('collapsed') ? (isArabic ? 'bx bx-chevron-left' : 'bx bx-chevron-right') : 'bx bx-menu';
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal-wrapper') {
            closeModal('imageModal');
            closeModal('videoModal', 'videoPlayer');
        }
    }

    // FOLDER JS HANDLERS
    function createNewFolder() {
        Swal.fire({
            title: isArabic ? 'إنشاء مجلد جديد' : 'Create New Folder',
            input: 'text',
            inputPlaceholder: isArabic ? 'اسم المجلد...' : 'Folder Name...',
            showCancelButton: true,
            confirmButtonText: isArabic ? 'إنشاء' : 'Create',
            cancelButtonText: isArabic ? 'إلغاء' : 'Cancel',
            confirmButtonColor: '#007bff',
            inputValidator: (value) => { if (!value.trim()) return isArabic ? 'يجب كتابة اسم للمجلد!' : 'Folder name is required!'; }
        }).then((result) => {
            if (result.isConfirmed) {
                const folderName = encodeURIComponent(result.value.trim());
                window.location.href = `create_folder_handler.php?name=${folderName}`;
            }
        });
    }

    function confirmFolderDelete(folderId, folderName) {
        Swal.fire({
            title: isArabic ? 'حذف المجلد؟' : 'Delete Folder?',
            text: isArabic ? `سيتم حذف المجلد ونقل محتوياته للسلة.` : `Folder will be deleted and contents moved to trash.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonText: isArabic ? 'إلغاء' : 'Cancel',
            confirmButtonText: isArabic ? 'نعم، احذف' : 'Yes, delete'
        }).then((result) => { if (result.isConfirmed) window.location.href = `delete_folder_handler.php?id=${folderId}`; });
    }

    function restoreFolder(id) {
        Swal.fire({ title: isArabic ? "استعادة المجلد؟" : "Restore Folder?", icon: 'question', showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonText: isArabic ? "إلغاء" : "Cancel", confirmButtonText: isArabic ? "نعم" : "Yes"
        }).then((result) => { if (result.isConfirmed) window.location.href = `restore_folder_handler.php?id=${id}`; });
    }

    function permDeleteFolder(id) {
        Swal.fire({ title: isArabic ? "حذف نهائي للمجلد؟" : "Permanently Delete Folder?", text: isArabic ? "لا يمكن التراجع!" : "Cannot be undone!", icon: 'error', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonText: isArabic ? "إلغاء" : "Cancel", confirmButtonText: isArabic ? "نعم" : "Yes"
        }).then((result) => { if (result.isConfirmed) window.location.href = `permanent_delete_folder_handler.php?id=${id}`; });
    }

    function confirmRename(folderId, oldName) {
        Swal.fire({ title: isArabic ? 'إعادة تسمية المجلد' : 'Rename Folder', input: 'text', inputValue: oldName, showCancelButton: true, confirmButtonText: isArabic ? 'حفظ' : 'Save', cancelButtonText: isArabic ? 'إلغاء' : 'Cancel', inputValidator: (value) => { if (!value) return isArabic ? 'مطلوب!' : 'Required!'; }
        }).then((result) => { if (result.isConfirmed) { window.location.href = `rename_folder_handler.php?id=${folderId}&name=${encodeURIComponent(result.value)}`; } });
    }

    // FILE JS HANDLERS
    function confirmTrash(id, name) {
        Swal.fire({ title: isArabic ? "نقل للسلة؟" : "Move to Trash?", text: name, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff4d4d', cancelButtonText: isArabic ? "إلغاء" : "Cancel", confirmButtonText: isArabic ? "نعم، انقل" : "Yes, move it"
        }).then((result) => { if (result.isConfirmed) window.location.href = `delete_handler.php?id=${id}`; });
    }

    function confirmRestore(id) {
        Swal.fire({ title: isArabic ? "استعادة الملف؟" : "Restore File?", icon: 'question', showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonText: isArabic ? "إلغاء" : "Cancel", confirmButtonText: isArabic ? "نعم، استعد" : "Yes, restore it"
        }).then((result) => { if (result.isConfirmed) window.location.href = `restore_handler.php?id=${id}`; });
    }

    function confirmPermanentDelete(id) {
        Swal.fire({ title: isArabic ? "حذف نهائي؟" : "Delete Permanently?", text: isArabic ? "لا يمكن التراجع!" : "Cannot be undone!", icon: 'error', showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonText: isArabic ? "إلغاء" : "Cancel", confirmButtonText: isArabic ? "نعم، احذف" : "Yes, delete it"
        }).then((result) => { if (result.isConfirmed) window.location.href = `permanent_delete_handler.php?id=${id}`; });
    }

    function confirmFileRename(fileId, oldFullName, ext) {
        const regex = new RegExp('\\.' + ext + '$', 'i');
        const oldName = oldFullName.replace(regex, '');
        Swal.fire({ title: isArabic ? 'إعادة تسمية الملف' : 'Rename File', input: 'text', inputValue: oldName, showCancelButton: true, confirmButtonText: isArabic ? 'حفظ' : 'Save', cancelButtonText: isArabic ? 'إلغاء' : 'Cancel', inputValidator: (value) => { if (!value) return isArabic ? 'مطلوب!' : 'Required!'; }
        }).then((result) => { if (result.isConfirmed) { const newFullName = encodeURIComponent(result.value + '.' + ext); window.location.href = `rename_file_handler.php?id=${fileId}&name=${newFullName}`; } });
    }

    // MULTI-SELECTION LOGIC
    function toggleSelectAll(master, gridId) {
        const grid = document.getElementById(gridId);
        if (!grid) return;
        const checkboxes = grid.querySelectorAll('.file-checkbox');
        checkboxes.forEach(cb => { cb.checked = master.checked; });
        updateSelection();
    }

    function updateSelection() {
        const allCheckboxes = document.querySelectorAll('.file-checkbox');
        const allChecked = document.querySelectorAll('.file-checkbox:checked');
        const bar = document.getElementById('bulkActionBar');
        const countLabel = document.getElementById('selectionCount');

        allCheckboxes.forEach(cb => {
            const card = cb.closest('.file-card');
            if (cb.checked) card.style.borderColor = "#007bff";
            else card.style.borderColor = "transparent";
        });

        const recentGrid = document.getElementById('recent-grid');
        if (recentGrid) {
            const recentCbs = recentGrid.querySelectorAll('.file-checkbox');
            const recentChecked = recentGrid.querySelectorAll('.file-checkbox:checked');
            const masterRecent = document.getElementById('masterSelectRecent');
            if (masterRecent) masterRecent.checked = (recentCbs.length > 0 && recentCbs.length === recentChecked.length);
        }

        const generalGrid = document.getElementById('general-grid');
        if (generalGrid) {
            const generalCbs = generalGrid.querySelectorAll('.file-checkbox');
            const generalChecked = generalGrid.querySelectorAll('.file-checkbox:checked');
            const masterGeneral = document.getElementById('masterSelectGeneral');
            if (masterGeneral) masterGeneral.checked = (generalCbs.length > 0 && generalCbs.length === generalChecked.length);
        }

        if (allChecked.length > 0) {
            bar.style.display = 'flex';
            countLabel.innerText = isArabic ? `${allChecked.length} تم تحديد` : `${allChecked.length} Selected`;
        } else { bar.style.display = 'none'; }
    }

    function cancelSelection() {
        document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = false);
        updateSelection();
    }

    function bulkDelete() {
        const selectedIds = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
        Swal.fire({ title: isArabic ? 'حذف المحدد؟' : 'Delete selected?', text: isArabic ? `نقل ${selectedIds.length} للسلة` : `Moving ${selectedIds.length} to trash`, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff4d4d', confirmButtonText: isArabic ? 'نعم' : 'Yes', cancelButtonText: isArabic ? 'إلغاء' : 'Cancel'
        }).then((result) => { if (result.isConfirmed) window.location.href = `bulk_delete_handler.php?ids=${selectedIds.join(',')}`; });
    }

    function bulkRestore() {
        const selectedIds = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
        Swal.fire({ title: isArabic ? 'استعادة الملفات؟' : 'Restore files?', text: isArabic ? `سيتم استعادة ${selectedIds.length} ملفات` : `You are restoring ${selectedIds.length} files`, icon: 'question', showCancelButton: true, confirmButtonColor: '#28a745', confirmButtonText: isArabic ? 'نعم، استعد' : 'Yes, restore', cancelButtonText: isArabic ? 'إلغاء' : 'Cancel'
        }).then((result) => { if (result.isConfirmed) window.location.href = `bulk_restore_handler.php?ids=${selectedIds.join(',')}`; });
    }

    function bulkPermanentDelete() {
        const selectedIds = Array.from(document.querySelectorAll('.file-checkbox:checked')).map(cb => cb.value);
        Swal.fire({ title: isArabic ? 'حذف نهائي؟' : 'Delete Permanently?', text: isArabic ? `لا يمكن التراجع! سيتم مسح ${selectedIds.length} ملفات نهائياً` : `Cannot be undone! Permanently deleting ${selectedIds.length} files.`, icon: 'error', showCancelButton: true, confirmButtonColor: '#dc3545', confirmButtonText: isArabic ? 'نعم، احذف' : 'Yes, delete', cancelButtonText: isArabic ? 'إلغاء' : 'Cancel'
        }).then((result) => { if (result.isConfirmed) window.location.href = `bulk_permanent_delete_handler.php?ids=${selectedIds.join(',')}`; });
    }

    // SEARCH & PRINT
    let searchTimeout;
    document.getElementById('liveSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        const suggestionBox = document.getElementById('searchSuggestions');
        if (query.length === 0) { suggestionBox.style.display = 'none'; return; }

        searchTimeout = setTimeout(async () => {
            try {
                const response = await fetch(`search_api.php?query=${encodeURIComponent(query)}`);
                const data = await response.json();
                suggestionBox.innerHTML = '';
                if (data.length > 0) {
                    suggestionBox.style.display = 'block';
                    data.forEach(file => {
                        const item = document.createElement('div');
                        item.className = 'suggestion-item';
                        item.innerHTML = `<i class='bx bx-file'></i> ${file.file_name}`;
                        item.onclick = () => {
                            const ext = file.file_name.split('.').pop().toLowerCase();
                            handlePreview(file.file_path, ext);
                            suggestionBox.style.display = 'none';
                        };
                        suggestionBox.appendChild(item);
                    });
                } else { suggestionBox.style.display = 'none'; }
            } catch (e) { console.error("Search failed", e); }
        }, 300);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-container')) { document.getElementById('searchSuggestions').style.display = 'none'; }
    });

    function printImage() {
        const imgSrc = document.getElementById('imgPreview').src;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`<html><head><title>Print</title><style>body{margin:0;display:flex;justify-content:center;align-items:center;height:100vh;background:white;}img{max-width:100%;max-height:100vh;object-fit:contain;}</style></head><body><img src="${imgSrc}" onload="window.print(); window.close();"></body></html>`);
        printWindow.document.close();
    }
</script>
</body>
</html>