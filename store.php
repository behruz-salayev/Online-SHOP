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

if (!$seller) {
    flash('error', 'Sotuvchi topilmadi.');
    redirect('index.php');
}

$products = $productModel->getAllBySeller($sellerId, '', 'active');
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
        <?php endif; ?>
        <div>
            <h1><?= htmlspecialchars($seller['business_name']) ?></h1>
            <p><?= nl2br(htmlspecialchars($seller['business_description'])) ?></p>
            <p><strong>Telefon:</strong> <?= htmlspecialchars($seller['phone']) ?></p>
            <p><strong>Manzil:</strong> 
                <?= htmlspecialchars($seller['region_name'] ?? '') ?>, 
                <?= htmlspecialchars($seller['district_name'] ?? '') ?>, 
                <?= htmlspecialchars($seller['address']) ?>
            </p>
            <p><strong>Qo'shilgan sana:</strong> <?= date('d.m.Y', strtotime($seller['created_at'])) ?></p>
        </div>
    </div>
</div>

<!-- Mahsulotlar -->
<section class="section">
    <div class="section-header">
        <h2><i class="fas fa-box"></i> <?= htmlspecialchars($seller['business_name']) ?> mahsulotlari</h2>
    </div>

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
                    </div>
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
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
