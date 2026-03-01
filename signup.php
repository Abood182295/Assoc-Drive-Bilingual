<?php
session_start();
include 'init_lang.php'; // Global translation logic
// 2. Database Connection (Port 3307)
$host = 'localhost:3307';
$user = 'root';
$pass = ''; 
$db   = 'association_drive';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (isset($_POST['signup'])) {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $plain_pass = $_POST['password'];

    // 1. Hash the password for security
    $hashed_pass = password_hash($plain_pass, PASSWORD_DEFAULT);

    // 2. Insert the HASHED password into the database
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed_pass);
    
    if ($stmt->execute()) {
        header("Location: login.php?signup=success");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signup - Assoc. Drive</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .signup-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .input-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-signup { width: 100%; padding: 12px; background: #007bff; border: none; color: white; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 16px; }
        .btn-signup:hover { background: #0056b3; }
        .login-link { text-align: center; margin-top: 20px; font-size: 14px; }
    </style>
</head>
<body>
<div class="signup-box">
    <h2><i class='bx bx-user-plus'></i> 
        <?php echo ($curr_lang == 'ar' ? 'إنشاء حساب جديد' : 'Create New Account'); ?>
    </h2>
    
    <form action="signup_handler.php" method="POST">
        <div class="input-group">
            <label><?php echo ($curr_lang == 'ar' ? 'الاسم الكامل' : 'Full Name'); ?></label>
            <input type="text" name="full_name" required placeholder="<?php echo ($curr_lang == 'ar' ? 'أدخل اسمك الكامل' : 'Enter your full name'); ?>">
        </div>

        <div class="input-group">
            <label><?php echo ($curr_lang == 'ar' ? 'اسم المستخدم' : 'Username'); ?></label>
            <input type="text" name="username" required placeholder="e.g. abdullah_hail">
        </div>

        <div class="input-group">
            <label><?php echo ($curr_lang == 'ar' ? 'البريد الإلكتروني' : 'Email Address'); ?></label>
            <input type="email" name="email" required placeholder="example@mail.com">
        </div>

        <div class="input-group">
            <label><?php echo ($curr_lang == 'ar' ? 'كلمة المرور' : 'Password'); ?></label>
            <input type="password" name="password" required placeholder="<?php echo ($curr_lang == 'ar' ? 'أنشئ كلمة مرور' : 'Create a password'); ?>">
        </div>

        <button type="submit" name="signup" class="btn-primary">
            <?php echo ($curr_lang == 'ar' ? 'إنشاء الحساب' : 'Sign Up'); ?>
        </button>
    </form>

    <div class="login-link" style="text-align: center; margin-top: 20px;">
        <?php echo ($curr_lang == 'ar' ? 'لديك حساب بالفعل؟' : 'Already have an account?'); ?> 
        <a href="login.php"><?php echo ($curr_lang == 'ar' ? 'سجل دخولك هنا' : 'Login here'); ?></a>
    </div>
</div>
</body>
</html>