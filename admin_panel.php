<?php
include 'init_lang.php'; 

// 1. Security Check (Ensure only the Admin can see this)
if (!isset($_SESSION['user_id']) || $_SESSION['username'] !== 'abdullah') {
    header("Location: dashboard.php");
    exit();
}

// 2. Database Connection
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

// 3. Handle Deletion Request
if (isset($_GET['delete_user'])) {
    $target_id = $_GET['delete_user'];
    
    // Safety: Don't let the admin delete themselves
    if ($target_id != $_SESSION['user_id']) {
        // First delete their files to avoid orphaned data
        $conn->query("DELETE FROM files WHERE user_id = '$target_id'");
        $conn->query("DELETE FROM users WHERE user_id = '$target_id'");
        header("Location: admin_panel.php?msg=deleted");
        exit();
    }
}

// 4. Fetch All Users
$users_result = $conn->query("SELECT user_id, username, full_name, email FROM users");
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - <?php echo $lang['site_title']; ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');
        body { font-family: <?php echo $font_family; ?>; background-color: #f8f9fa; margin: 0; display: flex; }
        .sidebar { width: 260px; background: #fff; height: 100vh; padding: 25px; position: fixed; 
                   <?php echo ($curr_lang == 'ar' ? 'right: 0; border-left: 1px solid #ddd;' : 'left: 0; border-right: 1px solid #ddd;'); ?> }
        .main-content { <?php echo ($curr_lang == 'ar' ? 'margin-right: 260px;' : 'margin-left: 260px;'); ?> padding: 40px; width: 100%; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        th, td { padding: 15px; text-align: <?php echo ($curr_lang == 'ar' ? 'right' : 'left'); ?>; border-bottom: 1px solid #eee; }
        th { background-color: #f1f1f1; color: #555; }
        .btn-del { color: #dc3545; border: none; background: none; cursor: pointer; font-size: 20px; }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2>Admin Management</h2>
        <ul style="list-style: none; padding: 0;">
            <li><a href="dashboard.php" style="text-decoration: none; color: #444;"><i class='bx bx-arrow-back'></i> <?php echo ($curr_lang == 'ar' ? 'العودة' : 'Back'); ?></a></li>
            <li><a href="register.php" style="text-decoration: none; color: #007bff;"><i class='bx bx-user-plus'></i> <?php echo ($curr_lang == 'ar' ? 'إضافة مستخدم' : 'Add User'); ?></a></li>
        </ul>
    </nav>

    <main class="main-content">
        <h2><i class='bx bxs-user-detail'></i> <?php echo ($curr_lang == 'ar' ? 'إدارة المستخدمين' : 'User Management'); ?></h2>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php echo ($curr_lang == 'ar' ? 'الاسم' : 'Full Name'); ?></th>
                    <th><?php echo ($curr_lang == 'ar' ? 'اسم المستخدم' : 'Username'); ?></th>
                    <th><?php echo ($curr_lang == 'ar' ? 'البريد' : 'Email'); ?></th>
                    <th><?php echo ($curr_lang == 'ar' ? 'إجراء' : 'Action'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>
                        <?php if($row['username'] !== 'abdullah'): ?>
                            <button onclick="confirmDelete(<?php echo $row['user_id']; ?>, '<?php echo $row['username']; ?>')" class="btn-del"><i class='bx bx-trash'></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>

    <script>
        function confirmDelete(id, name) {
            Swal.fire({
                title: '<?php echo ($curr_lang == 'ar' ? 'حذف المستخدم؟' : 'Delete User?'); ?>',
                text: "<?php echo ($curr_lang == 'ar' ? 'سيتم حذف كافة ملفات ' : 'All files for '); ?>" + name + " <?php echo ($curr_lang == 'ar' ? 'نهائياً!' : ' will be deleted!'); ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: '<?php echo ($curr_lang == 'ar' ? 'نعم، احذف' : 'Yes, delete'); ?>'
            }).then((result) => { if (result.isConfirmed) window.location.href = 'admin_panel.php?delete_user=' + id; });
        }
    </script>
</body>
</html>