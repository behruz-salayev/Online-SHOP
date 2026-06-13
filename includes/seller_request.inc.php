<?php
/**
 * seller_request.inc.php - Sotuvchi arizasini qayta ishlash
 * 
 * Foydalanuvchi sotuvchi bo'lish uchun ariza topshiradi.
 * Ariza admin tomonidan ko'rib chiqiladi.
 */

require_once __DIR__ . '/../config/config.php';

// Foydalanuvchi tizimga kirgan bo'lishi kerak
User::requireLogin();

// Admin sotuvchi arizasi topshira olmaydi
if (User::isAdmin()) {
    flash('error', 'Adminlar sotuvchi bo\'la olmaydi.');
    redirect('admin/index.php');
}

// CSRF tekshirish
ensure_csrf();

// Faqat POST so'rovda ishlaydi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_seller_request'])) {
    
    // Forma ma'lumotlarini olish
    $businessName = trim($_POST['business_name'] ?? '');
    $businessDescription = trim($_POST['business_description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $regionId = (int)($_POST['region_id'] ?? 0);
    $districtId = (int)($_POST['district_id'] ?? 0);
    $address = trim($_POST['address'] ?? '');

    // Xatolik bo'lganda forma maydonlarini saqlab qolish
    $_SESSION['old'] = [
        'business_name' => $businessName,
        'business_description' => $businessDescription,
        'phone' => $phone,
        'region_id' => $regionId,
        'address' => $address,
    ];

    // Ma'lumotlarni tekshirish
    $errors = [];
    if (empty($businessName)) $errors[] = 'Biznes nomini kiriting';
    if (empty($businessDescription)) $errors[] = 'Biznes tavsifini kiriting';
    if (empty($phone)) $errors[] = 'Telefon raqamingizni kiriting';
    if ($regionId <= 0) $errors[] = 'Viloyatni tanlang';
    if ($districtId <= 0) $errors[] = 'Tumanni tanlang';
    if (empty($address)) $errors[] = 'Manzilni kiriting';

    // Kutilayotgan arizani tekshirish
    $requestModel = new SellerRequest();
    if ($requestModel->hasPendingRequest($_SESSION['user_id'])) {
        $errors[] = 'Sizda allaqachon ko\'rib chiqilayotgan ariza mavjud';
    }

    // Logo faylini yuklash (ixtiyoriy)
    $logo = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Logo faylini yuklashda xatolik yuz berdi';
        } else {
            // Faqat ruxsat etilgan formatlar
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Logo uchun faqat JPG, PNG yoki WEBP ruxsat etilgan';
            } else {
                $logo = uniqid('seller_logo_') . '.' . $ext;
                move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $logo);
            }
        }
    }

    // Xatoliklar bo'lsa, orqaga qaytarish
    if (!empty($errors)) {
        flash('error', implode('<br>', $errors));
        redirect('users/become_seller.php');
    }

    // Arizani yaratish
    $requestModel->create([
        'user_id' => $_SESSION['user_id'],
        'business_name' => $businessName,
        'business_description' => $businessDescription,
        'phone' => $phone,
        'region_id' => $regionId,
        'district_id' => $districtId,
        'address' => $address,
        'logo' => $logo,
    ]);

    $_SESSION['success'] = 'Arizangiz muvaffaqiyatli yuborildi. Admin tasdiqlagunga qadar kuting.';
    redirect('users/profile.php');
}

// Agar to'g'ridan-to'g'ri kirsalar
redirect('users/become_seller.php');
