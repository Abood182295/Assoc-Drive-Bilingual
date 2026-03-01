<?php
session_start();
include 'init_lang.php';
?>
echo "Max Upload: " . ini_get('upload_max_filesize') . "<br>";
echo "Max Post: " . ini_get('post_max_size');
?>
<!DOCTYPE html>
<html lang="<?php echo $curr_lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <style>
        body { font-family: <?php echo $font_family; ?>; }
        /* Professional Switcher Style */
        .page-lang-switcher { text-align: center; margin-bottom: 20px; }
        .lang-btn { background: #eee; border: 1px solid #ddd; padding: 5px 15px; border-radius: 20px; cursor: pointer; }
    </style>
</head>