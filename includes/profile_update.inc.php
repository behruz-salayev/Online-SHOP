<?php
/**
 * profile_update.inc.php - Profil ma'lumotlarini yangilash
 * 
 * Ikki rejimda ishlaydi:
 * 1. Profil ma'lumotlarini yangilash (update_profile)
 * 2. Parolni o'zgartirish (update_password)
 */

require_once __DIR__ . '/../config/config.php';

// Foydalanuvchi tizimga kirgan bo'lishi kerak
User::requireLogin();
// CSRF tekshirish
ensure_csrf();

$userId = $_SESSION['user_id'];
$userModel = new User();

// ===== Profil ma'lumotlarini yangilash =====
if (isset($_POST['update_profile'])) {
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        flash('error', 'Ism kiritilishi shart');
        redirect('users/profile.php#profile-info');
    }

    $userModel->updateProfile($userId, $name, $phone);
    $_SESSION['success'] = 'Ma\'lumotlar yangilandi';
    redirect('users/profile.php#profile-info');
}

// ===== Parolni o'zgartirish =====
if (isset($_POST['update_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';

    $result = $userModel->updatePassword($userId, $currentPass, $newPass);

    if ($result['success']) {
        $_SESSION['success'] = 'Parol muvaffaqiyatli o\'zgartirildi';
    } else {
        flash('password_errors', $result['errors']);
    }
    redirect('users/profile.php#profile-password');
}

// Agar to'g'ridan-to'g'ri kirsalar
redirect('users/profile.php');
