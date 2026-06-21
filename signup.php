<?php
session_start();
include 'init_lang.php'; // Global translation logic
?>
<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($curr_lang == 'ar' ? 'إنشاء حساب - أسوشيت درايف' : 'Signup - Assoc. Drive'); ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        
        /* Responsive Body */
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: #f8f9fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px; /* Prevents touching edges on small mobile screens */
            box-sizing: border-box;
        }

        /* Fluid Container */
        .signup-container { 
            background: white; 
            /* clamp() smoothly scales padding based on screen size */
            padding: clamp(25px, 5vw, 40px); 
            border-radius: 15px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 100%; 
            max-width: 450px; 
        }

        h2 { text-align: center; color: #333; margin-bottom: 30px; font-size: clamp(1.5rem, 3vw, 1.8rem); }
        
        .input-group { margin-bottom: 20px; text-align: <?php echo ($curr_lang == 'ar' ? 'right' : 'left'); ?>; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; font-size: 0.95rem; }
        .input-group input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-sizing: border-box; 
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .input-group input:focus { border-color: #007bff; outline: none; }

        .btn-signup { 
            width: 100%; 
            padding: 14px; 
            background: #007bff; 
            border: none; 
            color: white; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 1rem; 
            font-family: inherit;
            transition: 0.3s; 
        }
        .btn-signup:hover { background: #0056b3; transform: translateY(-2px); }
        
        .login-link { text-align: center; margin-top: 25px; font-size: 0.95rem; color: #666; }
        .login-link a { color: #007bff; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="signup-container">
    <h2><i class='bx bx-user-plus' style="color: #007bff;"></i> 
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

        <button type="submit" name="signup" class="btn-signup">
            <?php echo ($curr_lang == 'ar' ? 'إنشاء الحساب' : 'Sign Up'); ?>
        </button>
    </form>

    <div class="login-link">
        <?php echo ($curr_lang == 'ar' ? 'لديك حساب بالفعل؟' : 'Already have an account?'); ?> 
        <a href="login.php"><?php echo ($curr_lang == 'ar' ? 'سجل دخولك هنا' : 'Login here'); ?></a>
    </div>
</div>

</body>
</html>