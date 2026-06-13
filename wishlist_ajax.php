<?php
/**
 * wishlist_ajax.php - Sevimlilar uchun AJAX so'rovlarni qayta ishlash
 * 
 * Mahsulotni sevimlilarga qo'shadi yoki olib tashlaydi (toggle).
 * Agar foydalanuvchi tizimga kirmagan bo'lsa, login sahifasiga yo'naltiradi.
 * 
 * Javob JSON formatida qaytariladi
 */

require_once __DIR__ . '/config/config.php';

$productId = (int)($_POST['product_id'] ?? 0);

$wishlist = new Wishlist();
$result = $wishlist->toggle($productId);

// Agar foydalanuvchi kirmagan bo'lsa, login sahifasiga yo'naltirish
if (!$result['success'] && !User::isLoggedIn()) {
    $result['redirect'] = SITE_URL . '/users/login.php';
}

jsonResponse($result);
