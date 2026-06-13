<?php
/**
 * logout.php - Tizimdan chiqish
 * 
 * Foydalanuvchi sessiyasini tozalaydi
 * va bosh sahifaga yo'naltiradi.
 */

require_once __DIR__ . '/../config/config.php';

$user = new User();
$user->logout();

redirect('index.php');
