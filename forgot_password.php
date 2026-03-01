<?php
session_start();
include 'init_lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo ($curr_lang == 'ar' ? 'استعادة كلمة المرور' : 'Forgot Password'); ?></title>
    <style>
        body { margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f8f9fa; font-family: <?php echo $font_family; ?>; }
        .auth-box { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .input-group { text-align: <?php echo ($curr_lang == 'ar' ? 'right' : 'left'); ?>; margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-auth { width: 100%; padding: 14px; background-color: #007bff; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <div class="auth-box">
        <div style="margin-bottom: 20px;">
            <form method="POST">
                <select name="language" onchange="this.form.submit()">
                    <option value="ar" <?php echo ($curr_lang == 'ar') ? 'selected' : ''; ?>>العربية</option>
                    <option value="en" <?php echo ($curr_lang == 'en') ? 'selected' : ''; ?>>English</option>
                </select>
                <input type="hidden" name="lang_switch" value="1">
            </form>
        </div>

        <h2><?php echo ($curr_lang == 'ar' ? 'نسيت كلمة المرور؟' : 'Forgot Password?'); ?></h2>
        <p style="color: #666; font-size: 14px; margin-bottom: 25px;">
            <?php echo ($curr_lang == 'ar' ? 'أدخل اسم المستخدم لاستعادة حسابك' : 'Enter your username to recover your account'); ?>
        </p>
        
        <form action="recovery_handler.php" method="POST">
            <div class="input-group">
                <label><?php echo ($curr_lang == 'ar' ? 'اسم المستخدم' : 'Username'); ?></label>
                <input type="text" name="username" required>
            </div>
            <button type="submit" class="btn-auth"><?php echo ($curr_lang == 'ar' ? 'إرسال طلب' : 'Send Request'); ?></button>
            <p style="margin-top: 20px;"><a href="login.php" style="color: #007bff; text-decoration: none;"><?php echo ($curr_lang == 'ar' ? 'العودة لتسجيل الدخول' : 'Back to Login'); ?></a></p>
        </form>
    </div>
</body>
</html>