<?php
/**
 * seller_header.php - Sotuvchi panelining yuqori qismi
 * 
 * Admin paneldagi kabi sidebar layout bilan.
 * Sotuvchiga xos navigatsiya havolalari.
 */
?><!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sotuvchi paneli' ?> - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/links/style/main.css?v=<?= filemtime(__DIR__ . '/../links/style/main.css') ?>">
</head>
<body class="admin-body">

<div class="admin-layout">
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="<?= SITE_URL ?>/seller/index.php" class="sidebar-logo">
                <i class="fas fa-store"></i>
                <span>Sotuvchi paneli</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= SITE_URL ?>/seller/index.php"
               class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="<?= SITE_URL ?>/seller/products.php"
               class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
                <i class="fas fa-box"></i> Mahsulotlar
            </a>
            <a href="<?= SITE_URL ?>/seller/add_product.php"
               class="<?= basename($_SERVER['PHP_SELF']) == 'add_product.php' ? 'active' : '' ?>">
                <i class="fas fa-plus-circle"></i> Mahsulot qo'shish
            </a>
            <a href="<?= SITE_URL ?>/seller/orders.php"
               class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                <i class="fas fa-truck"></i> Buyurtmalar
            </a>
            <a href="<?= SITE_URL ?>/seller/profile.php"
               class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user-cog"></i> Profil
            </a>
            <hr>
            <a href="<?= SITE_URL ?>/index.php"><i class="fas fa-store"></i> Do'konga o'tish</a>
            <a href="<?= SITE_URL ?>/users/profile.php"><i class="fas fa-user"></i> Akkaunt sozlamalari</a>
            <a href="<?= SITE_URL ?>/users/logout.php"><i class="fas fa-sign-out-alt"></i> Chiqish</a>
        </nav>
    </aside>

    <div class="admin-content">
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
