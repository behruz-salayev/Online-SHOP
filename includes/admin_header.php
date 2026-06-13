<?php
/**
 * admin_header.php - Admin panelining yuqori qismi
 * 
 * Admin paneli uchun maxsus header:
 * - Yon panel (sidebar) bilan
 * - Admin uchun navigatsiya
 */

// Kutilayotgan ma'lumotlar soni (badge uchun)
$pendingProductsCount = (new Product())->countByApprovalStatus('pending');
$pendingSellerRequestsCount = (new SellerRequest())->countByStatus('pending');
$pendingOrdersCount = (new Order())->getStats()['pending_orders'];
?><!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Admin' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/links/style/main.css?v=<?= filemtime(__DIR__ . '/../links/style/main.css') ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Yon panel (sidebar) -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="<?= SITE_URL ?>/admin/index.php" class="sidebar-logo">
                <i class="fas fa-shopping-bag"></i>
                <span>Admin Panel</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= SITE_URL ?>/admin/index.php" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="<?= SITE_URL ?>/admin/products.php" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                <i class="fas fa-box"></i> Mahsulotlar
            </a>
            <a href="<?= SITE_URL ?>/admin/pending_products.php" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'pending_products.php' ? 'active' : '' ?>">
                <i class="fas fa-clock"></i> Kutilayotgan mahsulotlar
                <?php if ($pendingProductsCount > 0): ?>
                    <span class="sidebar-badge"><?= $pendingProductsCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= SITE_URL ?>/admin/seller_requests.php" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'seller_requests.php' ? 'active' : '' ?>">
                <i class="fas fa-user-check"></i> Sotuvchi arizalari
                <?php if ($pendingSellerRequestsCount > 0): ?>
                    <span class="sidebar-badge"><?= $pendingSellerRequestsCount ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= SITE_URL ?>/admin/categories.php" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : '' ?>">
                <i class="fas fa-th-large"></i> Kategoriyalar
            </a>
            <a href="<?= SITE_URL ?>/admin/orders.php" 
               class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                <i class="fas fa-truck"></i> Buyurtmalar
                <?php if ($pendingOrdersCount > 0): ?>
                    <span class="sidebar-badge"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>
            <hr>
            <a href="<?= SITE_URL ?>/index.php"><i class="fas fa-store"></i> Do'konga o'tish</a>
            <a href="<?= SITE_URL ?>/users/profile.php"><i class="fas fa-user-cog"></i> Akkaunt sozlamalari</a>
            <a href="<?= SITE_URL ?>/users/logout.php"><i class="fas fa-sign-out-alt"></i> Chiqish</a>
        </nav>
    </aside>

    <!-- Asosiy kontent qismi -->
    <div class="admin-content">
        <?php
        // Flash xabarlar
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
