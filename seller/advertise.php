<?php
require_once __DIR__ . '/../config/config.php';
User::requireLogin();
User::requireSeller();

flash('error', 'Reklama tizimi o\'chirilgan.');
redirect('seller/products.php');
