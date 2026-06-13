<?php
/**
 * signup.inc.php - Ro'yxatdan o'tish formasini qayta ishlash
 * 
 * Yangi foydalanuvchini ro'yxatdan o'tkazadi.
 * Muvaffaqiyatli bo'lsa, avtomatik tizimga kiritadi.
 */

require_once __DIR__ . '/../config/config.php';

// Faqat POST so'rov va signup tugmasi bosilganda ishlaydi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    
    // CSRF tokenni tekshirish
    ensure_csrf();
    
    // Forma ma'lumotlarini olish
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    // Xatolik bo'lganda forma maydonlarini saqlab qolish
    $_SESSION['old'] = [
        'full_name' => $name,
        'email' => $email,
        'phone' => $phone,
    ];

    // Ro'yxatdan o'tkazish
    $userModel = new User();
    $result = $userModel->register($name, $email, $password, $phone);

    if ($result['success']) {
        $_SESSION['success'] = 'Ro\'yxatdan o\'tish muvaffaqiyatli! Xush kelibsiz!';
        redirect('index.php');
    } else {
        flash('register_errors', $result['errors']);
        redirect('users/register.php');
    }
}

// Agar to'g'ridan-to'g'ri kirsalar, ro'yxatdan o'tish sahifasiga yo'naltirish
redirect('users/register.php');
