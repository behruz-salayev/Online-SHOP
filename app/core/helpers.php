<?php
/**
 * helpers.php - Yordamchi funksiyalar
 * 
 * Bu faylda umumiy vazifalarni bajaradigan funksiyalar joylashgan:
 * - Xatoliklarni ko'rsatish
 * - Sahifalarga yo'naltirish
 * - JSON javob qaytarish
 * - Narxlarni formatlash
 * - CSRF himoyasi
 * - Flash xabarlar
 */

/**
 * O'zgaruvchini chiroyli qilib ekranga chiqarish va to'xtatish
 * (faqat ishlab chiqish vaqtida ishlatiladi)
 */
function dd($var): void {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    exit;
}

/**
 * Foydalanuvchini boshqa sahifaga yo'naltirish
 * 
 * @param string $url Yo'naltiriladigan sahifa (masalan: 'index.php')
 */
function redirect(string $url): void {
    header('Location: ' . SITE_URL . '/' . $url);
    exit;
}

/**
 * JSON formatida javob qaytarish
 * AJAX so'rovlar uchun ishlatiladi
 * 
 * @param mixed $data Yuboriladigan ma'lumotlar
 * @param int $code HTTP status kodi
 */
function jsonResponse(mixed $data, int $code = 200): void {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Narxni chiroyli formatda ko'rsatish
 * Masalan: 1500000 -> "1 500 000 so'm"
 * 
 * @param float $price Narx
 * @return string Formatlangan narx
 */
function formatPrice(float $price): string {
    return number_format($price, 0, ',', ' ') . ' ' . CURRENCY;
}

/**
 * Matndan slug (URL uchun qulay matn) yaratish
 * Masalan: "iPhone 15 Pro" -> "iphone-15-pro"
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    return strtolower($text) ?: 'product';
}

/**
 * Flash xabarlar - bir marta ko'rsatiladigan xabarlar
 * 
 * @param string $key Xabar kaliti (success, error)
 * @param mixed $value Xabar matni (agar null bo'lsa, o'qish rejimi)
 * @return mixed Xabar qiymati yoki null
 */
function flash(string $key, mixed $value = null): mixed {
    if ($value !== null) {
        $_SESSION[$key] = $value;
        return null;
    }
    if (isset($_SESSION[$key])) {
        $val = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $val;
    }
    return null;
}

/**
 * Forma maydoni uchun eski qiymatni olish
 * Forma to'ldirilganda xatolik bo'lsa, kiritilgan qiymat saqlanib qoladi
 */
function old(string $key, string $default = ''): string {
    return $_SESSION['old'][$key] ?? $default;
}

/**
 * Forma maydoni uchun eski qiymatni olish va o'chirish
 */
function old_flash(string $key): string {
    if (isset($_SESSION['old'][$key])) {
        $val = $_SESSION['old'][$key];
        unset($_SESSION['old'][$key]);
        return $val;
    }
    return '';
}

/**
 * CSRF token yaratish yoki mavjudini qaytarish
 * Bu soxta so'rovlardan himoya qilish uchun kerak
 * 
 * @return string CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF tokenni tekshirish
 * 
 * @param string $token Tekshiriladigan token
 * @return bool Token to'g'ri bo'lsa true
 */
function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * CSRF ni majburiy tekshirish
 * Agar token noto'g'ri bo'lsa, foydalanuvchini orqaga qaytaradi
 */
function ensure_csrf(): void {
    if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
        $_SESSION['error'] = 'Xatolik yuz berdi. Iltimos, sahifani yangilab qayta urinib ko\'ring.';
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

/**
 * CSRF uchun yashirin input yaratish
 * Formalarda ishlatiladi: <?= csrf_field() ?>
 * 
 * @return string HTML input
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

/**
 * GPS koordinatalari orqali manzilni aniqlash
 * OpenStreetMap (Nominatim) API sidan foydalanadi
 * 
 * @param float $lat Kenglik
 * @param float $lng Uzunlik
 * @return string Manzil matni
 */
function getAddressFromCoordinates(float $lat, float $lng): string {
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&accept-language=uz";
    $context = stream_context_create([
        'http' => ['header' => 'User-Agent: OnlineMarket/1.0']
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        return $data['display_name'] ?? 'Noma\'lum manzil';
    }
    return 'Manzil aniqlanmadi';
}
