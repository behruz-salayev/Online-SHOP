<?php
/**
 * login.inc.php - Kirish formasini qayta ishlash
 * 
 * POST so'rov orqali kelgan login ma'lumotlarini tekshiradi
 * va foydalanuvchini tizimga kiritadi.
 */

require_once __DIR__ . '/../config/config.php';

// Faqat POST so'rov va login tugmasi bosilganda ishlaydi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    
    // CSRF tokenni tekshirish
    ensure_csrf();
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Bo'sh maydonlarni tekshirish
    if (empty($email) || empty($password)) {
        flash('login_errors', ['Email va parol kiritilishi shart']);
        $_SESSION['old']['email'] = $email;
        redirect('users/login.php');
    }

    // Tizimga kirish
    $userModel = new User();
    $result = $userModel->login($email, $password);

    if ($result['success']) {
        $_SESSION['success'] = 'Xush kelibsiz!';
        
        // Rolga qarab yo'naltirish
        if ($result['role'] === 'admin') {
            redirect('admin/index.php');
        }
        if ($result['role'] === 'seller') {
            redirect('seller/index.php');
        }
        redirect('index.php');
    } else {
        // Xatolik - orqaga qaytarish
        flash('login_errors', $result['errors']);
        $_SESSION['old']['email'] = $email;
        redirect('users/login.php');
    }
}

// Agar to'g'ridan-to'g'ri kirsalar, login sahifasiga yo'naltirish
redirect('users/login.php');
