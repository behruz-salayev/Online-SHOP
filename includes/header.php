<?php
/**
 * header.php - Saytning yuqori qismi (HEAD)
 * 
 * Bu fayl barcha sahifalarda bir xil:
 * - HTML boshlanishi
 * - Navigatsiya paneli
 * - Qidiruv paneli
 * - Kategoriyalar
 * - Flash xabarlar
 */
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? SITE_NAME ?> - <?= SITE_NAME ?></title>
    <!-- Font Awesome 6 (ikonkalar) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Asosiy stillar -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/links/style/main.css?v=<?= filemtime(__DIR__ . '/../links/style/main.css') ?>">
</head>
<body>

<!-- ===== Yuqori panel (top bar) ===== -->
<div class="top-bar">
    <div class="container top-bar-inner">
        <div class="top-bar-links">
            <a href="<?= SITE_URL ?>/index.php"><i class="fas fa-home"></i> Bosh sahifa</a>
            
            <?php if (User::isLoggedIn()): ?>
                <!-- Tizimga kirgan foydalanuvchi -->
                <?php if (User::isAdmin()): ?>
                    <a href="<?= SITE_URL ?>/admin/index.php"><i class="fas fa-shield-alt"></i> Admin panel</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/users/profile.php"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?></a>
            <?php else: ?>
                <!-- Tizimga kirmagan foydalanuvchi -->
                <a href="<?= SITE_URL ?>/users/login.php"><i class="fas fa-sign-in-alt"></i> Kirish</a>
                <a href="<?= SITE_URL ?>/users/register.php"><i class="fas fa-user-plus"></i> Ro'yxatdan o'tish</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===== Asosiy header (logo, qidiruv, tugmalar) ===== -->
<header class="main-header">
    <div class="header-inner">
        <!-- Logo -->
        <div class="logo">
            <a href="<?= SITE_URL ?>/index.php">
                <i class="fas fa-shopping-bag"></i>
                <span>PhoneStore</span>
            </a>
        </div>

        <!-- Qidiruv paneli -->
        <div class="search-bar">
            <form action="<?= SITE_URL ?>/index.php" method="GET">
                <select name="category_id" class="search-category">
                    <option value="">Barcha kategoriyalar</option>
                    <?php
                    $catModel = new Category();
                    $categories = $catModel->getAll();
                    foreach ($categories as $cat):
                        $selected = (isset($_GET['category_id']) && $_GET['category_id'] == $cat['id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $cat['id'] ?>" <?= $selected ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="Qidirish..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <!-- Header tugmalari (sevimlilar, savat) -->
        <div class="header-actions">
            <?php $wishlistModel = new Wishlist(); $wishlistCount = $wishlistModel->getCount(); ?>
            <a href="<?= SITE_URL ?>/wishlist.php" class="action-btn" title="Sevimlilar">
                <i class="fas fa-heart"></i>
                <?php if ($wishlistCount > 0): ?>
                    <span class="badge"><?= $wishlistCount ?></span>
                <?php endif; ?>
            </a>

            <?php if (!User::isLoggedIn() || !User::isAdmin()): ?>
                <?php $cartModel = new Cart(); $cartCount = $cartModel->getCount(); ?>
                <a href="<?= SITE_URL ?>/cart.php" class="action-btn" title="Savat">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- ===== Kategoriyalar navigatsiyasi ===== -->
<nav class="main-nav">
    <div class="nav-inner">
        <button class="nav-toggle" onclick="this.nextElementSibling.classList.toggle('show')">
            <i class="fas fa-bars"></i> Kategoriyalar
        </button>
        <ul class="nav-list">
            <li><a href="<?= SITE_URL ?>/index.php"><i class="fas fa-th-large"></i> Barchasi</a></li>
            <?php foreach ($categories as $cat): ?>
                <li>
                    <a href="<?= SITE_URL ?>/index.php?category_id=<?= $cat['id'] ?>">
                        <?= htmlspecialchars($cat['name']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<!-- ===== Asosiy kontent ===== -->
<main class="container">
    <!-- Flash xabarlar (muvaffaqiyat va xatolik) -->
    <?php
    $success = flash('success');
    $error = flash('error');
    if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            <button onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            <button onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endif; ?>
