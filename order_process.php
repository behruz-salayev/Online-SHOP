<?php
/**
 * order_process.php - Buyurtma yaratish jarayoni
 * 
 * Checkout formasidan kelgan ma'lumotlarni qayta ishlaydi
 * va yangi buyurtma yaratadi.
 */

require_once __DIR__ . '/config/config.php';

// Kirish talab qilinadi
User::requireLogin();

// Admin buyurtma yarata olmaydi
if (User::isAdmin()) {
    $_SESSION['error'] = 'Adminlar buyurtma bera olmaydi.';
    redirect('index.php');
}

// Sotuvchi o'z mahsulotiga buyurtma bera olmaydi
if (User::isSeller()) {
    $cart = new Cart();
    foreach ($cart->getItems() as $item) {
        if ((int)$item['seller_id'] === (int)$_SESSION['user_id']) {
            $_SESSION['error'] = 'Siz o\'z mahsulotingizni sotib ololmaysiz.';
            redirect('cart.php');
        }
    }
}

// Faqat POST so'rov
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('checkout.php');
}

// CSRF tekshirish
ensure_csrf();

// Forma ma'lumotlarini olish
$phone = trim($_POST['phone'] ?? '');
$regionId = (int)($_POST['region_id'] ?? 0);
$districtId = (int)($_POST['district_id'] ?? 0);
$address = trim($_POST['address'] ?? '');
$paymentMethod = $_POST['payment_method'] ?? 'cash';
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;

// Ma'lumotlarni tekshirish
$errors = [];
if (empty($phone)) $errors[] = 'Telefon raqam kiritilishi shart';
if (empty($regionId)) $errors[] = 'Viloyat tanlanishi shart';
if (empty($districtId)) $errors[] = 'Tuman tanlanishi shart';
if (empty($address)) $errors[] = 'Manzil kiritilishi shart';

if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    redirect('checkout.php');
}

// Buyurtma yaratish
try {
    $orderModel = new Order();
    $orderId = $orderModel->create($_SESSION['user_id'], [
        'region_id' => $regionId,
        'district_id' => $districtId,
        'address' => $address,
        'phone' => $phone,
        'payment_method' => $paymentMethod,
        'latitude' => $latitude,
        'longitude' => $longitude,
    ]);

    $_SESSION['success'] = 'Buyurtmangiz qabul qilindi! Buyurtma raqami: #' . $orderId;
    redirect('order_success.php?id=' . $orderId);

} catch (Exception $e) {
    $_SESSION['error'] = 'Xatolik yuz berdi: ' . $e->getMessage();
    redirect('checkout.php');
}
