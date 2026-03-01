<?php
session_start();
$saved_lang = $_SESSION['site_lang']; // Preserve the language choice

session_unset();
session_destroy();

session_start();
$_SESSION['site_lang'] = $saved_lang; // Re-apply the language

header("Location: login.php");
exit();
?>