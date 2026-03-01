<?php
// init_lang.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Language Switching Logic
if (isset($_POST['lang_switch'])) {
    $_SESSION['curr_lang'] = $_POST['language'];
}

// 2. Default to Arabic (ar) for users in Hail
if (!isset($_SESSION['curr_lang'])) {
    $_SESSION['curr_lang'] = 'ar'; 
}

$curr_lang = $_SESSION['curr_lang'];

// 3. Layout and Styling Variables
$dir = ($curr_lang == 'ar') ? 'rtl' : 'ltr';
$font_family = ($curr_lang == 'ar') ? "'Cairo', sans-serif" : "'Segoe UI', Tahoma, sans-serif";

// 4. Arabic Dictionary
$lang_ar = [
    "site_title"         => "درايف الجمعية",
    "all_files"          => "كل الملفات",
    "recent_files"       => "أحدث الملفات",
    "dawah_books"        => "كتب الدعوة",
    "trash"              => "سلة المحذوفات",
    "settings"           => "الإعدادات",
    "logout"             => "تسجيل الخروج",
    "search_placeholder" => "ابحث عن ملف...",
    "lang_select_label"  => "اللغة:",
    "change_btn"         => "تغيير",
    "my_files_title"     => "ملفاتي",
    "no_files_here"      => "لا توجد ملفات هنا حالياً.",
    "upload_title"       => "اختر ملفاً لرفعه إلى مساحتك الشخصية",
    "choose_file_btn"    => "اختر ملف",
    "no_file_selected"   => "لم يتم اختيار ملف",
    "selected_file"      => "الملف المختار: ",
    "start_upload_btn"   => "بدء الرفع",
    "trash_title"        => "سلة المحذوفات",
    "settings_header"    => "إعدادات الحساب",
    "settings_desc"      => "إدارة معلوماتك الشخصية وتفضيلات الأمان",
    "personal_info"      => "المعلومات الشخصية",
    "full_name_label"    => "الاسم الكامل",
    "email_label"        => "البريد الإلكتروني",
    "security_title"     => "الأمان",
    "current_pass"       => "كلمة المرور الحالية",
    "new_pass"           => "كلمة المرور الجديدة",
    "pass_hint"          => "اتركها فارغة إذا لم ترد التغيير",
    "save_btn"           => "حفظ التغييرات",
    "confirm_delete"     => "هل أنت متأكد؟",
    "confirm_text"       => "سيتم نقل هذا الملف إلى سلة المحذوفات!",
    "confirm_btn"        => "نعم، انقل للمحذوفات",
    "cancel_btn"         => "إلغاء",
    "storage"            => "المساحة"
];

// 5. English Dictionary
$lang_en = [
    "site_title"         => "Assoc. Drive",
    "all_files"          => "All Files",
    "recent_files"       => "Recent Files",
    "dawah_books"        => "Dawah Books",
    "trash"              => "Trash Bin",
    "settings"           => "Settings",
    "logout"             => "Logout",
    "search_placeholder" => "Search for a file...",
    "lang_select_label"  => "Language:",
    "change_btn"         => "Change",
    "my_files_title"     => "My Files",
    "no_files_here"      => "No files found here currently.",
    "upload_title"       => "Choose a file to upload",
    "choose_file_btn"    => "Choose File",
    "no_file_selected"   => "No file selected",
    "selected_file"      => "Selected file: ",
    "start_upload_btn"   => "Start Upload",
    "trash_title"        => "Trash Bin",
    "settings_header"    => "Account Settings",
    "settings_desc"      => "Manage your personal info and security",
    "personal_info"      => "Personal Information",
    "full_name_label"    => "Full Name",
    "email_label"        => "Email Address",
    "security_title"     => "Security",
    "current_pass"       => "Current Password",
    "new_pass"           => "New Password",
    "pass_hint"          => "Leave blank if you don't want to change",
    "save_btn"           => "Save Changes",
    "confirm_delete"     => "Are you sure?",
    "confirm_text"       => "This file will be moved to the trash!",
    "confirm_btn"        => "Yes, move to trash",
    "cancel_btn"         => "Cancel",
    "storage"            => "Storage"
];

// 6. Select Active Dictionary
$lang = ($curr_lang == 'ar') ? $lang_ar : $lang_en;
?>