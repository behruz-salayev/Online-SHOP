<?php
/**
 * wishlist.php - Sevimli mahsulotlar sahifasi
 * 
 * Foydalanuvchi o'zining sevimli mahsulotlarini ko'rishi
 * va ulardan mahsulotlarni olib tashlashi mumkin.
 */

require_once __DIR__ . '/config/config.php';

// Kirish talab qilinadi
User::requireLogin();

$title = 'Sevimlilar';

$wishlist = new Wishlist();
$items = $wishlist->getUserWishlist();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Non-breadcrumbs -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a> <span>/</span>
    <span>Sevimlilar</span>
</div>

<h1 class="page-title"><i class="fas fa-heart"></i> Sevimli mahsulotlar</h1>

<?php if (empty($items)): ?>
    <!-- Bo'sh holat -->
    <div class="empty-state">
        <i class="fas fa-heart-broken"></i>
        <h3>Sevimlilar bo'sh</h3>
        <p>Hozircha sevimli mahsulotlar yo'q. Mahsulotlarni ko'rib chiqing!</p>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary">Mahsulotlar</a>
    </div>
<?php else: ?>
    <!-- Mahsulotlar panjarasi -->
    <div class="product-grid">
        <?php foreach ($items as $item): ?>
            <div class="product-card" onclick="window.location='<?= SITE_URL ?>/product.php?id=<?= $item['id'] ?>'">
                <div class="product-image">
                    <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>" alt="">
                    <button class="wishlist-btn active" 
                            onclick="event.stopPropagation(); removeWishlist(<?= $item['id'] ?>, this.closest('.product-card'))">
                        <i class="fas fa-heart"></i>
                    </button>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <div class="product-price">
                        <span class="current-price"><?= formatPrice($item['price']) ?></span>
                    </div>
                    <?php if (!User::isAdmin()): ?>
                        <button class="btn btn-primary btn-block" onclick="event.stopPropagation(); addToCart(<?= $item['id'] ?>)">
                            <i class="fas fa-shopping-cart"></i> Savatga
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- JavaScript -->
<script>
function addToCart(productId) {
    fetch('<?= SITE_URL ?>/cart_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId
    }).then(r => r.json()).then(data => {
        if (data.success) {
            const badge = document.querySelector('.action-btn[title="Savat"] .badge');
            if (badge) badge.textContent = data.count;
            showToast('Savatga qo\'shildi', 'success');
        } else if (data.error) {
            showToast(data.error, 'error');
        }
    });
}

function removeWishlist(productId, card) {
    fetch('<?= SITE_URL ?>/wishlist_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId
    }).then(r => r.json()).then(data => {
        if (data.success) {
            card.remove();
            showToast('Olib tashlandi', 'info');
        }
    });
}

function showToast(m, t) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + t;
    toast.textContent = m;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
