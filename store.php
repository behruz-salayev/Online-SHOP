<?php
/**
 * store.php - Sotuvchi do'koni sahifasi
 * 
 * Sotuvchining barcha mahsulotlarini ko'rsatadi.
 * URL: /store.php?id={seller_id} yoki .htaccess orqali /store/{id}
 */

require_once __DIR__ . '/config/config.php';

// Sotuvchi ID sini olish
$sellerId = (int)($_GET['id'] ?? 0);

if ($sellerId <= 0) {
    flash('error', 'Sotuvchi topilmadi.');
    redirect('index.php');
}

// Sotuvchi va mahsulotlarini olish
$sellerModel = new Seller();
$productModel = new Product();
$seller = $sellerModel->getById($sellerId);

// Agar sellers.id bo'yicha topilmasa, user ID bo'yicha qidirish
if (!$seller) {
    $seller = $sellerModel->getByUserId($sellerId);
}

if (!$seller) {
    flash('error', 'Sotuvchi topilmadi.');
    redirect('index.php');
}

$products = $productModel->getAllBySeller($seller['user_id'], '', 'active');
$title = htmlspecialchars($seller['business_name']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Non-breadcrumbs -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a> <span>/</span>
    <span><?= htmlspecialchars($seller['business_name']) ?></span>
</div>

<!-- Sotuvchi haqida -->
<div class="store-header">
    <div class="store-info">
        <?php if ($seller['logo']): ?>
            <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($seller['logo']) ?>" 
                 alt="<?= htmlspecialchars($seller['business_name']) ?>" class="store-logo">
        <?php else: ?>
            <div class="store-logo" style="display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.15);font-size:36px;color:#fff;">
                <i class="fas fa-store"></i>
            </div>
        <?php endif; ?>
        <div style="flex:1;">
            <h1><?= htmlspecialchars($seller['business_name']) ?></h1>
            <?php if ($seller['business_description']): ?>
                <p class="store-desc"><?= nl2br(htmlspecialchars($seller['business_description'])) ?></p>
            <?php endif; ?>
            <div class="store-meta">
                <span class="store-meta-item"><i class="fas fa-phone"></i> <?= htmlspecialchars($seller['phone']) ?></span>
                <span class="store-meta-item"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($seller['region_name'] ?? '') ?>, <?= htmlspecialchars($seller['district_name'] ?? '') ?></span>
                <span class="store-meta-item"><i class="fas fa-calendar-alt"></i> <?= date('d.m.Y', strtotime($seller['created_at'])) ?> dan buyon</span>
                <span class="store-meta-item"><i class="fas fa-box"></i> <?= count($products) ?> ta mahsulot</span>
            </div>
        </div>
    </div>
</div>

<!-- Mahsulotlar -->
<section class="section">
    <?php if (!empty($products)): ?>
        <div class="section-header">
            <h2><i class="fas fa-box"></i> <?= htmlspecialchars($seller['business_name']) ?> mahsulotlari <span style="font-size:14px;font-weight:400;color:var(--text-muted);">(<?= count($products) ?> ta)</span></h2>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>Hech qanday mahsulot yo'q</h3>
            <p>Sotuvchi hali mahsulot qo'shmagan.</p>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card" onclick="window.location='<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>'">
                    <div class="product-image">
                        <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($product['image'] ?? 'placeholder.jpg') ?>"
                             alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php if ($product['old_price'] > 0): ?>
                            <span class="discount-badge">
                                -<?= round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) ?>%
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <span class="product-category"><?= htmlspecialchars($product['category_name'] ?? '') ?></span>
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-stock <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                            <?= $product['stock'] > 0 ? 'Mavjud: ' . $product['stock'] . ' dona' : 'Tugagan' ?>
                        </p>
                        <div class="product-price">
                            <span class="current-price"><?= formatPrice($product['price']) ?></span>
                            <?php if ($product['old_price'] > 0): ?>
                                <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
