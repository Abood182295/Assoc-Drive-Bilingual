<?php
session_start();
include 'init_lang.php'; // 1. Global translation logic
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $lang['site_title']; ?> - Login</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: <?php echo $font_family; ?>; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 420px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
        .btn-primary { width: 100%; padding: 12px; background: #007bff; border: none; color: white; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px; }
        .error-alert { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 10px; margin-bottom: 25px; text-align: center; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>

<div class="login-box">
    <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
        <form method="POST">
            <span style="font-size: 13px; color: #888;"><?php echo $lang['lang_select_label']; ?></span>
            <select name="language" onchange="this.form.submit()" style="padding: 5px; border-radius: 5px; border: 1px solid #ddd;">
                <option value="ar" <?php echo ($curr_lang == 'ar') ? 'selected' : ''; ?>>العربية</option>
                <option value="en" <?php echo ($curr_lang == 'en') ? 'selected' : ''; ?>>English</option>
            </select>
            <input type="hidden" name="lang_switch" value="1">
        </form>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <div class="error-alert">
            <i class='bx bx-error-circle'></i>
            <?php 
                if($_GET['error'] == 'wrong_password') {
                    echo ($curr_lang == 'ar' ? 'كلمة المرور غير صحيحة.' : 'Invalid password.');
                }
                if($_GET['error'] == 'user_not_found') {
                    // UPDATED: Now mentions Username instead of Email
                    echo ($curr_lang == 'ar' ? 'اسم المستخدم غير مسجل.' : 'Username not found.');
                }
            ?>
        </div>
    <?php endif; ?>

    <h2 style="margin-bottom: 30px; text-align: center; color: #333;">
        <i class='bx bx-lock-alt'></i> 
        <?php echo ($curr_lang == 'ar' ? 'تسجيل دخول الموظفين' : 'Staff Login'); ?>
    </h2>

    <form action="login_handler.php" method="POST">
        
        <div class="input-group">
            <label><?php echo ($curr_lang == 'ar' ? 'اسم المستخدم' : 'Username'); ?></label>
            <input type="text" name="username" required 
                   placeholder="<?php echo ($curr_lang == 'ar' ? 'أدخل اسم المستخدم' : 'Enter username'); ?>">
        </div>

        <div class="input-group">
            <label><?php echo ($curr_lang == 'ar' ? 'كلمة المرور' : 'Password'); ?></label>
            <input type="password" name="password" required 
                   placeholder="<?php echo ($curr_lang == 'ar' ? 'أدخل كلمة المرور' : 'Enter password'); ?>">
        </div>

        <button type="submit" name="login" class="btn-primary">
            <?php echo ($curr_lang == 'ar' ? 'دخول' : 'Login'); ?>
        </button>
    </form>

    <p style="text-align: center; margin-top: 20px; font-size: 14px; color: #666;">
        <?php echo ($curr_lang == 'ar' ? 'ليس لديك حساب؟' : "Don't have an account?"); ?> 
        <a href="signup.php" style="color: #007bff; text-decoration: none; font-weight: 600;">
            <?php echo ($curr_lang == 'ar' ? 'إنشاء حساب جديد' : 'Create Account'); ?>
        </a>
    </p>
</div>

</body>
</html>