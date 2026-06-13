<?php
/**
 * cart_ajax.php - Savat uchun AJAX so'rovlarni qayta ishlash
 * 
 * Vazifalari:
 * - Mahsulotni savatga qo'shish (add)
 * - Mahsulot sonini o'zgartirish (update)
 * - Mahsulotni savatdan o'chirish (remove)
 * 
 * Barcha javoblar JSON formatida qaytariladi
 */

require_once __DIR__ . '/config/config.php';

// Admin savatga mahsulot qo'sha olmaydi
if (User::isLoggedIn() && User::isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'Adminlar savatdan foydalana olmaydi'], 403);
    exit;
}

// So'rov turini aniqlash
$action = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

$cart = new Cart();

switch ($action) {
    // Mahsulotni savatga qo'shish
    case 'add':
        $cart->add($productId, max(1, $quantity));
        jsonResponse([
            'success' => true,
            'count' => $cart->getCount()
        ]);
        break;

    // Mahsulot sonini o'zgartirish
    case 'update':
        $cart->updateQuantity($productId, max(1, $quantity));
        jsonResponse(['success' => true]);
        break;

    // Mahsulotni savatdan o'chirish
    case 'remove':
        $cart->remove($productId);
        jsonResponse(['success' => true]);
        break;

    // Noto'g'ri so'rov
    default:
        jsonResponse(
            ['success' => false, 'error' => 'Noto\'g\'ri so\'rov'],
            400
        );
}
