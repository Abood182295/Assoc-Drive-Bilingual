<?php
// 1. Entry Point Logic
session_start();
include 'init_lang.php'; // Use your existing bilingual logic

// 2. Auto-Redirect
// If the user is already logged in, don't show the landing page, go to the Drive
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($curr_lang == 'ar' ? 'مرحباً بك في أسوشيت درايف' : 'Welcome to Assoc. Drive'); ?></title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap');
        
        body {
            font-family: 'Cairo', sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            text-align: center;
        }

        .hero-container {
            padding: 20px;
            max-width: 500px;
            width: 90%;
        }

        .logo-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        h1 { font-size: 2.5rem; margin-bottom: 10px; }
        p { font-size: 1.1rem; opacity: 0.9; margin-bottom: 30px; }

        .btn-start {
            background: white;
            color: #007bff;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.2rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn-start:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(0,0,0,0.3);
            background: #f8f9fa;
        }

        /* Mobile Optimization */
        @media (max-width: 600px) {
            h1 { font-size: 1.8rem; }
            .logo-icon { font-size: 60px; }
        }
    </style>
</head>
<body>

    <div class="hero-container">
        <i class='bx bxs-cloud-upload logo-icon'></i>
        <h1>Assoc. Drive</h1>
        <p>
            <?php echo ($curr_lang == 'ar' 
                ? 'نظام إدارة الملفات الآمن والذكي لجمعية الدعوة والإرشاد بحائل.' 
                : 'The secure, intelligent file management system for the Dawah & Guidance Association in Hail.'); ?>
        </p>
        
        <a href="login.php" class="btn-start">
            <?php echo ($curr_lang == 'ar' ? 'ابدأ الآن' : 'Get Started'); ?>
            <i class='bx <?php echo ($curr_lang == 'ar' ? 'bx-left-arrow-alt' : 'bx-right-arrow-alt'); ?>'></i>
        </a>

        <div style="margin-top: 40px; font-size: 0.8rem; opacity: 0.7;">
            © 2026 Assoc. Drive | Developed by Abdullah Muhammad Saleem
        </div>
    </div>

</body>
</html>