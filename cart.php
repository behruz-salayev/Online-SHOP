<?php
/**
 * cart.php - Savat sahifasi
 * 
 * Foydalanuvchi savatdagi mahsulotlarni ko'rishi,
 * sonini o'zgartirishi va olib tashlashi mumkin.
 */

require_once __DIR__ . '/config/config.php';

$title = 'Savat';

$cart = new Cart();
$items = $cart->getItems();
$total = $cart->getTotal();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Non-breadcrumbs -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a>
    <span>/</span>
    <span>Savat</span>
</div>

<h1 class="page-title"><i class="fas fa-shopping-cart"></i> Savat</h1>

<?php if (empty($items)): ?>
    <!-- Savat bo'sh -->
    <div class="empty-state">
        <i class="fas fa-shopping-cart"></i>
        <h3>Savat bo'sh</h3>
        <p>Hozircha savatingizda mahsulotlar yo'q. Xarid qilishni boshlang!</p>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary">Mahsulotlar</a>
    </div>
<?php else: ?>
    <!-- Savat mazmuni -->
    <div class="cart-layout">
        <div class="cart-items">
            <?php foreach ($items as $item): ?>
                <div class="cart-item" id="cart-item-<?= $item['id'] ?>">
                    <!-- Rasm -->
                    <div class="cart-item-image">
                        <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>"
                             alt="<?= htmlspecialchars($item['name']) ?>">
                    </div>
                    <!-- Ma'lumot -->
                    <div class="cart-item-info">
                        <h3>
                            <a href="<?= SITE_URL ?>/product.php?id=<?= $item['id'] ?>">
                                <?= htmlspecialchars($item['name']) ?>
                            </a>
                        </h3>
                        <p class="cart-item-price"><?= formatPrice($item['price']) ?></p>
                    </div>
                    <!-- Soni -->
                    <div class="cart-item-qty">
                        <button onclick="updateCart(<?= $item['id'] ?>, -1)">-</button>
                        <span id="qty-<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
                        <button onclick="updateCart(<?= $item['id'] ?>, 1)">+</button>
                    </div>
                    <!-- Summa -->
                    <div class="cart-item-total">
                        <?= formatPrice($item['price'] * $item['quantity']) ?>
                    </div>
                    <!-- O'chirish -->
                    <button class="cart-item-remove" onclick="removeFromCart(<?= $item['id'] ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Savat yakuni -->
        <div class="cart-summary">
            <h3>Buyurtma summasi</h3>
            <div class="summary-row">
                <span>Mahsulotlar soni:</span>
                <span><?= $cart->getCount() ?> dona</span>
            </div>
            <div class="summary-row">
                <span>Yetkazib berish:</span>
                <span class="free">Bepul</span>
            </div>
            <div class="summary-row summary-total">
                <span>Jami:</span>
                <span id="cart-total"><?= formatPrice($total) ?></span>
            </div>
            <a href="<?= SITE_URL ?>/checkout.php" class="btn btn-primary btn-block btn-lg">
                <i class="fas fa-credit-card"></i> Buyurtma berish
            </a>
            <a href="<?= SITE_URL ?>/index.php" class="btn btn-outline btn-block">
                Xaridni davom ettirish
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- JavaScript -->
<script>
function updateCart(productId, delta) {
    const qtyEl = document.getElementById('qty-' + productId);
    let newQty = parseInt(qtyEl.textContent) + delta;
    if (newQty < 1) { removeFromCart(productId); return; }

    fetch('<?= SITE_URL ?>/cart_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=update&product_id=' + productId + '&quantity=' + newQty
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function removeFromCart(productId) {
    if (!confirm('Haqiqatan ham olib tashlashni xohlaysizmi?')) return;
    fetch('<?= SITE_URL ?>/cart_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=remove&product_id=' + productId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
