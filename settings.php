<?php
session_start();
include 'init_lang.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database Connection (Local XAMPP Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

$current_uid = $_SESSION['user_id'];
$message = "";

// 1. Handle Update Submission
if (isset($_POST['update_settings'])) {
    $new_name  = $conn->real_escape_string($_POST['full_name']);
    $new_email = $conn->real_escape_string($_POST['email']);
    $new_pass  = $_POST['new_password'];

    $update_sql = "UPDATE users SET full_name = '$new_name', email = '$new_email' WHERE user_id = '$current_uid'";
    $conn->query($update_sql);
    
    $_SESSION['full_name'] = $new_name;

    if (!empty($new_pass)) {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '$hashed_pass' WHERE user_id = '$current_uid'");
    }
    $message = "success";
}

// 2. Fetch User Data for the Form
$user_query = $conn->query("SELECT full_name, email FROM users WHERE user_id = '$current_uid'");
$user_data  = $user_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['settings']; ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');
        
        * {
            box-sizing: border-box;
        }

        body { 
            font-family: <?php echo $font_family; ?>; 
            background-color: #f8f9fa; 
            margin: 0; 
            display: flex; 
            /* Fluid Typography */
            font-size: clamp(0.875rem, 1vw + 0.5rem, 1.125rem);
        }

        .sidebar { 
            width: 260px; 
            background: #fff; 
            height: 100vh; 
            padding: 25px; 
            position: fixed; 
            <?php echo ($curr_lang == 'ar' ? 'right: 0; border-left: 1px solid #ddd;' : 'left: 0; border-right: 1px solid #ddd;'); ?> 
        }

        .main-content { 
            <?php echo ($curr_lang == 'ar' ? 'margin-right: 260px;' : 'margin-left: 260px;'); ?> 
            padding: clamp(20px, 4vw, 40px); 
            width: calc(100% - 260px); 
        }

        .settings-card { 
            background: white; 
            padding: clamp(20px, 5vw, 30px); 
            border-radius: 15px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            max-width: 600px; 
            width: 100%;
        }

        h2 {
            font-size: clamp(1.5rem, 3vw, 1.8rem);
        }

        .form-group { 
            margin-bottom: 20px; 
            text-align: <?php echo ($curr_lang == 'ar' ? 'right' : 'left'); ?>; 
        }

        .form-control { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-family: inherit; 
            font-size: 1em;
        }

        .form-control:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn-save { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 12px 25px; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            font-weight: bold; 
            font-size: 1em;
            transition: 0.3s;
        }

        .btn-save:hover {
            background: #0056b3;
        }

        /* Mobile Responsiveness */
        @media screen and (max-width: 768px) {
            body { 
                flex-direction: column; 
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                <?php echo ($curr_lang == 'ar' ? 'right: auto; border-left: none;' : 'left: auto; border-right: none;'); ?>
                border-bottom: 1px solid #ddd;
                padding: 15px;
            }
            .sidebar ul {
                display: flex;
                margin: 0;
            }
            .main-content {
                margin-left: 0;
                margin-right: 0;
                width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <h2 style="font-size: 18px; margin-top: 0;"><i class='bx bxs-hdd' style="color: #007bff;"></i> <?php echo $lang['site_title']; ?></h2>
        <ul style="list-style: none; padding: 0;">
            <li><a href="dashboard.php" style="text-decoration: none; color: #444;"><i class='bx bx-left-arrow-alt'></i> <?php echo ($curr_lang == 'ar' ? 'الرئيسية' : 'Home'); ?></a></li>
        </ul>
    </nav>

    <main class="main-content">
        <div class="settings-card">
            <h2><?php echo $lang['settings_header']; ?></h2>
            <form method="POST">
                <div class="form-group">
                    <label><?php echo $lang['full_name_label']; ?></label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['email_label']; ?></label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['new_pass']; ?></label>
                    <input type="password" name="new_password" class="form-control" placeholder="<?php echo $lang['pass_hint']; ?>">
                </div>
                <button type="submit" name="update_settings" class="btn-save"><?php echo $lang['save_btn']; ?></button>
            </form>
        </div>
    </main>

    <script>
        <?php if($message == "success"): ?>
            Swal.fire({ icon: 'success', title: '<?php echo ($curr_lang == 'ar' ? 'تم التحديث' : 'Updated'); ?>', timer: 2000, showConfirmButton: false });
        <?php endif; ?>
    </script>
</body>
</html>