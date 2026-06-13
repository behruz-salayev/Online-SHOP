<?php
/**
 * PhoneStore - Asosiy konfiguratsiya fayli
 * 
 * Bu faylda ma'lumotlar bazasi sozlamalari, sayt sozlamalari
 * va avtomatik yuklovchi (autoloader) joylashgan
 */

// ===== Ma'lumotlar bazasi sozlamalari =====
define('DB_HOST', 'localhost');   // Ma'lumotlar bazasi serveri
define('DB_USER', 'root');        // Foydalanuvchi nomi
define('DB_PASS', '');           // Parol
define('DB_NAME', 'online_market'); // Ma'lumotlar bazasi nomi

// ===== Sayt sozlamalari =====
define('SITE_NAME', 'PhoneStore');        // Sayt nomi
define('SITE_URL', 'http://localhost/online'); // Saytning to'liq URL manzili
define('CURRENCY', "so'm");               // Pul birligi
define('IMAGES_DIR', 'links/images/');     // Rasm papkasi (nisbiy yo'l)
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/online/links/images/'); // Yuklash papkasi (absolyut yo'l)

// ===== Sessiyani boshlash =====
// Agar sessiya hali boshlanmagan bo'lsa, boshlaymiz
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Xatoliklarni ko'rsatish (faqat ishlab chiqish vaqtida) =====
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');

// ===== Class'larni avtomatik yuklash =====
// Kerakli class faylini avtomatik topib yuklaydi
spl_autoload_register(function ($className) {
    // Qidiriladigan papkalar
    $paths = [
        __DIR__ . '/../app/core/' . $className . '.php',   // Core classlar
        __DIR__ . '/../app/models/' . $className . '.php',  // Model classlar
    ];
    
    // Har bir papkadan class faylini qidiramiz
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// ===== Yordamchi funksiyalarni yuklash =====
require_once __DIR__ . '/../app/core/helpers.php';
