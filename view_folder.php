<?php
session_start();
include 'init_lang.php';

// Database Connection (Hail Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$folder_id = (int)$_GET['id'];
$current_user = $_SESSION['user_id'];

// Fetch folder info
$fQuery = $conn->query("SELECT folder_name FROM folders WHERE folder_id = $folder_id AND user_id = $current_user");
$fData = $fQuery->fetch_assoc();
$folderName = $fData['folder_name'] ?? 'Folder';

// Fetch files
$result = $conn->query("SELECT * FROM files WHERE folder_id = $folder_id AND is_deleted = 0 ORDER BY upload_date DESC");
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($folderName); ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    
    body { 
        font-family: 'Cairo', sans-serif; 
        background-color: #f8f9fa; 
        margin: 0; 
        padding: clamp(15px, 4vw, 40px);
        font-size: clamp(0.9rem, 1vw + 0.5rem, 1.1rem);
    }

    .header-container { 
        display: flex; justify-content: space-between; align-items: center; 
        margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 15px; 
    }
    
    .header-left { display: flex; align-items: center; gap: 15px; }
    .back-btn { font-size: 28px; color: #333; text-decoration: none; transition: 0.2s; }
    .back-btn:hover { color: #007bff; transform: scale(1.1); }

    .file-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
        gap: 20px; 
    }

    .file-card { 
        background: white; 
        padding: 25px; 
        border-radius: 15px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        text-align: center; 
        transition: 0.3s;
        position: relative; /* CRITICAL for checkbox positioning */
        border: 1px solid transparent;
    }

    .file-card i.main-icon { font-size: 50px; margin-bottom: 10px; }
    .f-name { font-weight: 700; color: #333; margin-top: 12px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Desktop Actions */
    .file-actions { 
        display: flex; justify-content: center; gap: 15px; 
        margin-top: 15px; border-top: 1px solid #eee; padding-top: 12px; 
    }
    .file-actions i, .file-actions a { font-size: 20px; cursor: pointer; text-decoration: none; transition: 0.2s; }
    .file-actions a:hover i { transform: scale(1.1); }

    /* MOBILE RESPONSIVENESS FIX */
    @media screen and (max-width: 768px) {
        .file-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
        .file-card { padding: 15px !important; }
        .file-card i.main-icon { font-size: 45px !important; }
        .f-name { font-size: 1.05rem !important; margin-top: 10px !important; }
        .file-actions { gap: 20px !important; padding-top: 10px !important; margin-top: 10px !important; }
        .file-actions i, .file-actions a { font-size: 18px !important; }
        .header-container { flex-direction: column; align-items: flex-start; gap: 15px; }
    }

    /* MODALS */
    .modal { display: none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); }
    .modal-controls { position: fixed; top: 20px; <?php echo ($curr_lang == 'ar' ? 'left: 20px;' : 'right: 20px;'); ?> display: flex; gap: 15px; z-index: 100000; }
    .modal-btn { background: rgba(0, 0, 0, 0.6); color: #fff; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 50%; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; backdrop-filter: blur(5px); transition: 0.3s ease; }
    .modal-btn:hover { background: #fff; color: #333; transform: scale(1.1); }
    .close-btn:hover { background: #dc3545; color: white; border-color: #dc3545; }
    .modal-wrapper { display: flex; justify-content: center; align-items: center; height: 100vh; position: relative; padding: 15px; width: 100%;}
    #imgPreview, #videoPlayer { max-width: 100%; max-height: 85vh; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); background: #fff; }
</style>
</head>
<body>

    <div class="header-container">
        <div class="header-left">
            <a href="dashboard.php" class="back-btn"><i class='bx <?php echo ($curr_lang == 'ar' ? 'bx-right-arrow-alt' : 'bx-left-arrow-alt'); ?>'></i></a>
            <h2 style="margin:0; font-size: 1.5rem;"><i class='bx bx-folder-open' style="color: #ffc107; margin-right: 5px;"></i> <?php echo htmlspecialchars($folderName); ?></h2>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 14px; color: #555; margin: 0;">
                <input type="checkbox" id="masterSelect" onclick="toggleSelectAll(this)" style="width: 18px; height: 18px; cursor: pointer;">
                <span><?php echo ($curr_lang == 'ar' ? 'تحديد الكل' : 'Select All'); ?></span>
            </label>
        <?php endif; ?>
    </div>

    <div class="file-grid">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $ext = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                $icon = 'bxs-file'; $color = '#7f8c8d';
                if (in_array($ext, ['mp4', 'mkv', 'mov'])) { $icon = 'bxs-video'; $color = '#007bff'; }
                elseif ($ext == 'pdf') { $icon = 'bxs-file-pdf'; $color = '#e74c3c'; }
                elseif (in_array($ext, ['mp3', 'm4a', 'wav'])) { $icon = 'bxs-music'; $color = '#8e44ad'; }
                elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) { $icon = 'bxs-file-image'; $color = '#f1c40f'; }
                elseif (in_array($ext, ['xlsx', 'xls', 'csv'])) { $icon = 'bxs-spreadsheet'; $color = '#28a745'; }
            ?>
                <div class="file-card">
                    <input type="checkbox" class="file-checkbox" value="<?php echo $row['file_id']; ?>" 
                          onclick="event.stopPropagation(); updateSelection();" 
                          style="position: absolute; top: 12px; <?php echo ($curr_lang == 'ar' ? 'right: 12px;' : 'left: 12px;'); ?> width: 18px; height: 18px; cursor: pointer; z-index: 5;">

                    <i class='bx <?php echo $icon; ?> main-icon' style="color: <?php echo $color; ?>;"></i>
                    <span class="f-name" title="<?php echo htmlspecialchars($row['file_name']); ?>"><?php echo htmlspecialchars($row['file_name']); ?></span>
                    
                    <div class="file-actions">
                        <a href="javascript:void(0)" onclick="handlePreview('<?php echo addslashes($row['file_path']); ?>', '<?php echo $ext; ?>')" style="color:#007bff;" title="<?php echo ($curr_lang == 'ar' ? 'عرض' : 'Preview'); ?>"><i class='bx bx-show-alt'></i></a>
                        <a href="download.php?id=<?php echo $row['file_id']; ?>" style="color:#333;" title="<?php echo ($curr_lang == 'ar' ? 'تحميل' : 'Download'); ?>"><i class='bx bx-download'></i></a>
                        
                        <a href="javascript:void(0)" onclick="confirmFileRename(<?php echo $row['file_id']; ?>, '<?php echo addslashes($row['file_name']); ?>', '<?php echo $ext; ?>')" style="color:#28a745;" title="<?php echo ($curr_lang == 'ar' ? 'إعادة تسمية' : 'Rename'); ?>"><i class='bx bx-edit-alt'></i></a>
                        
                        <a href="javascript:void(0)" onclick="confirmTrash(<?php echo $row['file_id']; ?>, '<?php echo addslashes($row['file_name']); ?>')" style="color:#ff4d4d;" title="<?php echo ($curr_lang == 'ar' ? 'حذف' : 'Delete'); ?>"><i class='bx bx-trash'></i></a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="grid-column: 1/-1; text-align: center; color: #999; padding: 40px;">
                <?php echo ($curr_lang == 'ar' ? 'هذا المجلد فارغ حالياً' : 'This folder is empty.'); ?>
            </p>
        <?php endif; ?>
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

<div id="videoModal" class="modal">
    <div class="modal-controls">
        <button onclick="closeModal('videoModal', 'videoPlayer')" class="modal-btn close-btn" title="<?php echo ($curr_lang == 'ar' ? 'إغلاق' : 'Close'); ?>"><i class='bx bx-x'></i></button>
    </div>
    <div class="modal-wrapper" onclick="closeModal('videoModal', 'videoPlayer')">
        <video id="videoPlayer" controls onclick="event.stopPropagation();"><source id="videoSource" src=""></video>
    </div>
</div>

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

<div id="bulkActionBar" style="display: none; position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background: #333; color: white; padding: 15px 30px; border-radius: 50px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); z-index: 99999; align-items: center; gap: 20px;">
    <span id="selectionCount" style="font-weight: bold; border-right: 1px solid #555; padding-right: 15px;">0 Selected</span>
    <div style="display: flex; gap: 15px;">
        <button onclick="bulkDelete()" style="background: #ff4d4d; border: none; color: white; padding: 8px 15px; border-radius: 20px; cursor: pointer; display: flex; align-items: center; gap: 5px;">
            <i class='bx bx-trash'></i> <?php echo ($curr_lang == 'ar' ? 'حذف المحدد' : 'Delete Selected'); ?>
        </button>
        <button onclick="cancelSelection()" style="background: transparent; border: 1px solid #888; color: #ccc; padding: 8px 15px; border-radius: 20px; cursor: pointer;">
            <?php echo ($curr_lang == 'ar' ? 'إلغاء' : 'Cancel'); ?>
        </button>
    </div>
</div>

<script>
    const isArabic = "<?php echo $curr_lang; ?>" === 'ar';

    function confirmTrash(id, name) {
        Swal.fire({ title: isArabic ? 'نقل للسلة؟' : 'Move to Trash?', text: name, icon: 'warning', showCancelButton: true, confirmButtonColor: '#ff4d4d', cancelButtonText: isArabic ? 'إلغاء' : 'Cancel', confirmButtonText: isArabic ? 'نعم، انقل' : 'Yes, move it'
        }).then((res) => { if (res.isConfirmed) window.location.href = `delete_handler.php?id=${id}`; });
    }

    function confirmFileRename(fileId, oldFullName, ext) {
        const regex = new RegExp('\\.' + ext + '$', 'i');
        const oldName = oldFullName.replace(regex, '');
        Swal.fire({ title: isArabic ? 'إعادة تسمية الملف' : 'Rename File', input: 'text', inputValue: oldName, showCancelButton: true, confirmButtonText: isArabic ? 'حفظ' : 'Save', cancelButtonText: isArabic ? 'إلغاء' : 'Cancel', inputValidator: (value) => { if (!value) return isArabic ? 'مطلوب!' : 'Required!'; }
        }).then((result) => { if (result.isConfirmed) { const newFullName = encodeURIComponent(result.value + '.' + ext); window.location.href = `rename_file_handler.php?id=${fileId}&name=${newFullName}`; } });
    }

    function handlePreview(path, ext) {
        const cleanPath = path.replace(/\\/g, '/') + "?t=" + new Date().getTime();
        const audioExts = ['mp3', 'm4a', 'ogg', 'wav', 'flac', 'aac', 'opus', 'wma'];
        const videoExts = ['mp4', 'mkv', 'avi', 'mov', 'webm', 'flv', 'wmv'];
        const imageExts = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg', 'webp'];

        if (audioExts.includes(ext)) {
            document.getElementById("audioSource").src = cleanPath;
            document.getElementById("audioPlayer").load();
            document.getElementById("audioModal").style.display = "block";
            document.getElementById("audioPlayer").play();
        } 
        else if (videoExts.includes(ext)) {
            document.getElementById("videoSource").src = cleanPath;
            document.getElementById("videoPlayer").load();
            document.getElementById("videoModal").style.display = "block";
            document.getElementById("videoPlayer").play(); 
        } 
        else if (imageExts.includes(ext)) {
            document.getElementById("imgPreview").src = cleanPath;
            document.getElementById("imageModal").style.display = "block";
        } else { window.open(cleanPath, '_blank'); }
    }

    function closeModal(mId, pId=null) {
        document.getElementById(mId).style.display = "none";
        if(pId) document.getElementById(pId).pause();
    }

    window.onclick = function(event) {
        if (event.target.className === 'modal-wrapper') {
            closeModal('imageModal');
            closeModal('videoModal', 'videoPlayer');
        }
    }

    function printImage() {
        const imgSrc = document.getElementById('imgPreview').src;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`<html><head><title>Print</title><style>body{margin:0;display:flex;justify-content:center;align-items:center;height:100vh;background:white;}img{max-width:100%;max-height:100vh;object-fit:contain;}</style></head><body><img src="${imgSrc}" onload="window.print(); window.close();"></body></html>`);
        printWindow.document.close();
    }

    // MULTI-SELECTION LOGIC
    function updateSelection() {
        const checkboxes = document.querySelectorAll('.file-checkbox');
        const checkedBoxes = document.querySelectorAll('.file-checkbox:checked');
        const masterSelect = document.getElementById('masterSelect');
        const bar = document.getElementById('bulkActionBar');
        const countLabel = document.getElementById('selectionCount');

        checkboxes.forEach(cb => {
            const card = cb.closest('.file-card');
            if (cb.checked) card.style.borderColor = "#007bff";
            else card.style.borderColor = "transparent";
        });

        if (masterSelect) masterSelect.checked = (checkedBoxes.length === checkboxes.length && checkboxes.length > 0);

        if (checkedBoxes.length > 0) {
            bar.style.display = 'flex';
            countLabel.innerText = isArabic ? `${checkedBoxes.length} تم تحديد` : `${checkedBoxes.length} Selected`;
        } else {
            bar.style.display = 'none';
        }
    }

    function toggleSelectAll(master) {
        document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = master.checked);
        updateSelection();
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
</script>
</body>
</html>