<?php
/**
 * get_districts.php - Viloyat bo'yicha tumanlarni olish (AJAX)
 * 
 * Checkout va sotuvchi arizasi formalarida
 * viloyat tanlanganda tumanlarni yuklash uchun ishlatiladi.
 * 
 * Javob: JSON formatidagi tumanlar massivi
 */

require_once __DIR__ . '/config/config.php';

// Viloyat ID sini olish
$regionId = (int)($_GET['region_id'] ?? 0);

// Agar ID noto'g'ri bo'lsa
if (!$regionId) {
    jsonResponse([], 400);
}

// Tumanlarni olish va qaytarish
$region = new Region();
$districts = $region->getDistricts($regionId);

jsonResponse($districts);
