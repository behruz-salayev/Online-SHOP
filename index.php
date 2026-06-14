<?php
/**
 * index.php - Bosh sahifa
 * 
 * Vazifalari:
 * - Mashhur mahsulotlarni ko'rsatish
 * - Barcha mahsulotlarni kategoriya bo'yicha filtrlash
 * - Qidirish va saralash
 * - Sahifalash (pagination)
 */

require_once __DIR__ . '/config/config.php';

$title = 'Bosh sahifa';

// Model obyektlarini yaratish
$productModel = new Product();
$categoryModel = new Category();

// Filtrlash parametrlarini olish
$categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;  // Kategoriya ID
$search = $_GET['search'] ?? '';              // Qidiruv matni
$sort = $_GET['sort'] ?? 'newest';            // Saralash turi
$page = max(1, (int)($_GET['page'] ?? 1));   // Joriy sahifa
$limit = 12;                                   // Har sahifadagi mahsulotlar soni
$offset = ($page - 1) * $limit;               // Qancha tashlab ketish

// Mahsulotlarni olish
$totalProducts = $productModel->countAll($categoryId, $search);
$totalPages = max(1, ceil($totalProducts / $limit));
$products = $productModel->getAll($categoryId, $search, $sort, $limit, $offset);
$featuredProducts = $productModel->getFeatured(4);
$categories = $categoryModel->getAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ===== BOSH SAHIFA HERO ===== -->
<section class="hero-section">
    <div class="hero-content">
        <h1><span>PhoneStore</span>ga xush kelibsiz!</h1>
        <p>Eng so'nggi telefonlar, eng yaxshi narxlarda. Faqat bir marta bosish bilan xarid qiling.</p>
        <div class="hero-search">
            <form action="<?= SITE_URL ?>/index.php" method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Mahsulot nomini yozing..." required>
                <button type="submit">Qidirish</button>
            </form>
        </div>
    </div>
</section>

<!-- ===== MASHHUR MAHSULOTLAR ===== -->
<?php if (!empty($featuredProducts)): ?>
<section class="section featured-section">
    <div class="section-header">
        <h2><i class="fas fa-star"></i> Mashhur mahsulotlar</h2>
        <a href="<?= SITE_URL ?>/index.php?sort=popular" class="view-all">Barchasini ko'rish</a>
    </div>
    <div class="product-grid featured-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <div class="product-card" onclick="window.location='<?= SITE_URL ?>/product.php?id=<?= $product['id'] ?>'">
                <div class="product-image">
                    <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($product['image'] ?? 'placeholder.jpg') ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php if ($product['old_price'] > 0): ?>
                        <span class="discount-badge">
                            -<?= round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) ?>%
                        </span>
                    <?php endif; ?>
                    <button class="wishlist-btn" onclick="event.stopPropagation(); toggleWishlist(<?= $product['id'] ?>, this)">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
                <div class="product-info">
                    <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="product-price">
                        <span class="current-price"><?= formatPrice($product['price']) ?></span>
                        <?php if ($product['old_price'] > 0): ?>
                            <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!User::isAdmin() && !(User::isLoggedIn() && User::isSeller() && (int)$product['seller_id'] === (int)$_SESSION['user_id'])): ?>
                        <button class="btn btn-primary btn-block" onclick="event.stopPropagation(); addToCart(<?= $product['id'] ?>)">
                            <i class="fas fa-shopping-cart"></i> Savatga
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ===== BARCHA MAHSULOTLAR ===== -->
<section class="section">
    <div class="section-header">
        <h2><i class="fas fa-list"></i> Barcha mahsulotlar</h2>
        <!-- Saralash -->
        <div class="sort-controls">
            <form method="GET" id="filter-form">
                <?php if ($categoryId): ?>
                    <input type="hidden" name="category_id" value="<?= $categoryId ?>">
                <?php endif; ?>
                <?php if ($search): ?>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>
                <select name="sort" onchange="this.form.submit()">
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Eng yangi</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Narx: arzondan qimmatga</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Narx: qimmatdan arzonga</option>
                    <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Nomi bo'yicha</option>
                </select>
            </form>
        </div>
    </div>

    <!-- Mahsulot topilmadi -->
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>Mahsulot topilmadi</h3>
            <p>Qidiruv natijasida hech narsa topilmadi. Boshqa kalit so'z bilan urinib ko'ring.</p>
            <a href="<?= SITE_URL ?>/index.php" class="btn btn-primary">Barcha mahsulotlar</a>
        </div>
    <?php else: ?>
        <!-- Mahsulotlar panjarasi -->
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
                        <button class="wishlist-btn" onclick="event.stopPropagation(); toggleWishlist(<?= $product['id'] ?>, this)">
                            <i class="<?= (new Wishlist())->isInWishlist($product['id']) ? 'fas' : 'far' ?> fa-heart"></i>
                        </button>
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
                        <?php if (!User::isAdmin() && !(User::isLoggedIn() && User::isSeller() && (int)$product['seller_id'] === (int)$_SESSION['user_id'])): ?>
                            <button class="btn btn-primary btn-block" 
                                    onclick="event.stopPropagation(); addToCart(<?= $product['id'] ?>)"
                                    <?= $product['stock'] < 1 ? 'disabled' : '' ?>>
                                <i class="fas fa-shopping-cart"></i> Savatga
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sahifalash -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                       class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- JavaScript: Savat va sevimlilar uchun AJAX -->
<script>
function addToCart(productId) {
    fetch('<?= SITE_URL ?>/cart_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.action-btn[title="Savat"] .badge');
            if (badge) badge.textContent = data.count;
            else {
                const btn = document.querySelector('.action-btn[title="Savat"]');
                btn.innerHTML += '<span class="badge">' + data.count + '</span>';
            }
            showToast('Mahsulot savatga qo\'shildi', 'success');
        } else if (data.error) {
            showToast(data.error, 'error');
        }
    });
}

function toggleWishlist(productId, btn) {
    fetch('<?= SITE_URL ?>/wishlist_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const icon = btn.querySelector('i');
            if (data.action === 'added') {
                icon.className = 'fas fa-heart';
                showToast('Sevimlilarga qo\'shildi', 'success');
            } else {
                icon.className = 'far fa-heart';
                showToast('Sevimlilardan olib tashlandi', 'info');
            }
        } else if (data.redirect) {
            window.location.href = data.redirect;
        }
    });
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
