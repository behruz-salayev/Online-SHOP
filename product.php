<?php
/**
 * product.php - Mahsulot haqida batafsil sahifa
 * 
 * Vazifalari:
 * - Mahsulot rasmlari galereyasi
 * - Narx, chegirma
 * - Xususiyatlar jadvali
 * - Savatga qo'shish
 * - O'xshash mahsulotlar
 */

require_once __DIR__ . '/config/config.php';

// Mahsulot ID sini olish
$id = (int)($_GET['id'] ?? 0);

// Mahsulotni olish
$productModel = new Product();
$product = $productModel->getById($id);
$wishlist = new Wishlist();

// Agar mahsulot topilmasa
if (!$product) {
    flash('error', 'Mahsulot topilmadi');
    redirect('index.php');
}

$title = $product['name'];

// Qo'shimcha ma'lumotlar
$relatedProducts = $productModel->getRelated($id, $product['category_id']);
$isInWishlist = $wishlist->isInWishlist($id);
$specs = json_decode($product['specs'] ?? '[]', true) ?: [];
$images = json_decode($product['images'] ?? '[]', true) ?: [];

$reviewModel = new Review();
$reviews = $reviewModel->getByProduct($id);
$avgRating = $reviewModel->getAverage($id);
$reviewCount = $reviewModel->getCount($id);
$userReviewed = User::isLoggedIn() ? $reviewModel->hasUserReviewed($id, $_SESSION['user_id']) : false;

// Sharh yuborish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    ensure_csrf();
    if (!User::isLoggedIn()) {
        flash('error', 'Sharh qoldirish uchun tizimga kiring.');
        redirect('users/login.php');
    }
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($rating < 1 || $rating > 5) {
        flash('error', 'Reyting 1-5 oralig\'ida bo\'lishi kerak.');
    } elseif ($userReviewed) {
        flash('error', 'Siz allaqachon sharh qoldirgansiz.');
    } else {
        $reviewModel->create($id, $_SESSION['user_id'], $rating, $comment);
        flash('success', 'Sharhingiz administrator tomonidan tekshirilgandan so\'ng e\'lon qilinadi.');
        redirect('product.php?id=' . $id);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Non-breadcrumbs (sahifa yo'li) -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a>
    <span>/</span>
    <a href="<?= SITE_URL ?>/index.php?category_id=<?= $product['category_id'] ?>">
        <?= htmlspecialchars($product['category_name']) ?>
    </a>
    <span>/</span>
    <span><?= htmlspecialchars($product['name']) ?></span>
</div>

<!-- Mahsulot detallari -->
<div class="product-detail">
    <!-- Galereya -->
    <div class="product-detail-gallery">
        <div class="main-image">
            <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($product['image'] ?? 'placeholder.jpg') ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>" id="mainImage">
        </div>
        <?php if (!empty($images)): ?>
            <div class="thumbnails">
                <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($product['image']) ?>"
                     onclick="document.getElementById('mainImage').src=this.src" class="active">
                <?php foreach ($images as $img): ?>
                    <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($img) ?>"
                         onclick="document.getElementById('mainImage').src=this.src">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Mahsulot ma'lumotlari -->
    <div class="product-detail-info">
        <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
        <h1><?= htmlspecialchars($product['name']) ?></h1>

        <!-- Reyting -->
        <div class="product-rating">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star-half-alt"></i>
            <span>(4.5)</span>
        </div>

        <!-- Narx -->
        <div class="product-price-lg">
            <span class="current-price"><?= formatPrice($product['price']) ?></span>
            <?php if ($product['old_price'] > 0): ?>
                <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                <span class="discount">
                    -<?= round((($product['old_price'] - $product['price']) / $product['old_price']) * 100) ?>%
                </span>
            <?php endif; ?>
        </div>

        <!-- Mavjudlik -->
        <p class="stock-info <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
            <i class="fas <?= $product['stock'] > 0 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
            <?= $product['stock'] > 0 ? 'Sotuvda mavjud (' . $product['stock'] . ' dona)' : 'Tugagan' ?>
        </p>

        <!-- Tavsif -->
        <div class="product-description">
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        </div>

        <!-- Xususiyatlar -->
        <?php if (!empty($specs)): ?>
            <div class="product-specs">
                <h3>Xususiyatlari</h3>
                <table>
                    <?php foreach ($specs as $key => $value): ?>
                        <tr>
                            <td><?= htmlspecialchars($key) ?></td>
                            <td><?= htmlspecialchars($value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        <!-- Harakat tugmalari -->
        <div class="product-actions">
            <div class="qty-control">
                <button onclick="changeQty(-1)">-</button>
                <input type="number" id="qty" value="1" min="1" max="<?= $product['stock'] ?>">
                <button onclick="changeQty(1)">+</button>
            </div>

            <?php if (!User::isAdmin()): ?>
            <button class="btn btn-primary btn-lg" onclick="addToCart(<?= $product['id'] ?>)"
                    <?= $product['stock'] < 1 ? 'disabled' : '' ?>>
                <i class="fas fa-shopping-cart"></i> Savatga
            </button>
            <?php endif; ?>

            <button class="btn btn-outline btn-lg" onclick="toggleWishlist(<?= $product['id'] ?>, this)">
                <i class="<?= $isInWishlist ? 'fas' : 'far' ?> fa-heart"></i>
                <?= $isInWishlist ? 'Sevimlilarda' : 'Sevimlilarga qo\'shish' ?>
            </button>
        </div>

        <!-- Sotuvchi do'koni -->
        <?php if (!empty($product['seller_id'])): ?>
            <a href="<?= SITE_URL ?>/store.php?id=<?= $product['seller_profile_id'] ?? $product['seller_id'] ?>" class="seller-store-card">
                <div class="seller-store-info">
                    <?php if (!empty($product['seller_logo'])): ?>
                        <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($product['seller_logo']) ?>" 
                             alt="<?= htmlspecialchars($product['seller_store_name'] ?? $product['seller_name']) ?>">
                    <?php else: ?>
                        <div class="seller-store-icon"><i class="fas fa-store"></i></div>
                    <?php endif; ?>
                    <div>
                        <strong><?= htmlspecialchars($product['seller_store_name'] ?? $product['seller_name'] ?? 'Do\'kon') ?></strong>
                        <span><i class="fas fa-chevron-right"></i> Do'konga o'tish</span>
                    </div>
                </div>
            </a>
        <?php endif; ?>

        <!-- Qo'shimcha ma'lumot -->
        <div class="product-meta">
            <p><i class="fas fa-truck"></i> Yetkazib berish: 1-3 ish kuni</p>
            <p><i class="fas fa-undo"></i> 30 kun ichida qaytarish</p>
            <p><i class="fas fa-shield-alt"></i> Sifat kafolati</p>
        </div>
    </div>
</div>

<!-- O'xshash mahsulotlar -->
<?php if (!empty($relatedProducts)): ?>
<section class="section">
    <div class="section-header">
        <h2>O'xshash mahsulotlar</h2>
    </div>
    <div class="product-grid">
        <?php foreach ($relatedProducts as $rp): ?>
            <div class="product-card" onclick="window.location='<?= SITE_URL ?>/product.php?id=<?= $rp['id'] ?>'">
                <div class="product-image">
                    <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($rp['image'] ?? 'placeholder.jpg') ?>"
                         alt="<?= htmlspecialchars($rp['name']) ?>">
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($rp['name']) ?></h3>
                    <div class="product-price">
                        <span class="current-price"><?= formatPrice($rp['price']) ?></span>
                    </div>
                    <?php if (!User::isAdmin()): ?>
                        <button class="btn btn-primary btn-block" onclick="event.stopPropagation(); addToCart(<?= $rp['id'] ?>)">
                            Savatga
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Sharhlar -->
<section class="section reviews-section">
    <div class="section-header">
        <h2><i class="fas fa-star"></i> Sharhlar (<?= $reviewCount ?>)</h2>
    </div>

    <?php if ($avgRating > 0): ?>
        <div class="reviews-summary">
            <div class="reviews-avg">
                <span class="avg-rating"><?= $avgRating ?></span>
                <div class="avg-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="color:<?= $i <= round($avgRating) ? 'var(--warning)' : 'var(--border)' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span class="avg-count"><?= $reviewCount ?> ta sharh</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="reviews-list">
        <?php foreach ($reviews as $r): ?>
            <div class="review-item">
                <div class="review-header">
                    <div class="review-avatar"><?= strtoupper(mb_substr($r['user_name'], 0, 1)) ?></div>
                    <div>
                        <strong><?= htmlspecialchars($r['user_name']) ?></strong>
                        <div class="review-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color:<?= $i <= $r['rating'] ? 'var(--warning)' : 'var(--border)' ?>;font-size:12px;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <small style="margin-left:auto;color:var(--text-muted);"><?= date('d.m.Y', strtotime($r['created_at'])) ?></small>
                </div>
                <?php if ($r['comment']): ?>
                    <p class="review-comment"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (empty($reviews)): ?>
            <div class="empty-state" style="padding:30px;">
                <i class="fas fa-comment"></i>
                <h3>Hali sharhlar yo'q</h3>
                <p>Ushbu mahsulot haqida birinchi bo'lib fikr bildiring!</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sharh qoldirish formasi -->
    <div class="review-form">
        <h3><i class="fas fa-pen"></i> Sharh qoldirish</h3>
        <?php if (!User::isLoggedIn()): ?>
            <p><a href="<?= SITE_URL ?>/users/login.php">Tizimga kiring</a> sharh qoldirish uchun.</p>
        <?php elseif ($userReviewed): ?>
            <p style="color:var(--text-muted);"><i class="fas fa-check-circle" style="color:var(--success);"></i> Siz bu mahsulotga sharh qoldirgansiz.</p>
        <?php else: ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div class="rating-select">
                    <span>Reyting:</span>
                    <div class="star-input">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>" <?= $i == 5 ? 'checked' : '' ?>>
                            <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <textarea name="comment" class="form-control" placeholder="Fikringizni yozing..." rows="3" style="margin-top:10px;"></textarea>
                <button type="submit" name="submit_review" class="btn btn-primary" style="margin-top:10px;">
                    <i class="fas fa-paper-plane"></i> Yuborish
                </button>
            </form>
        <?php endif; ?>
    </div>
</section>

<!-- JavaScript -->
<script>
function changeQty(delta) {
    const input = document.getElementById('qty');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > <?= $product['stock'] ?>) val = <?= $product['stock'] ?>;
    input.value = val;
}

function addToCart(productId) {
    const qty = document.getElementById('qty').value;
    fetch('<?= SITE_URL ?>/cart_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=add&product_id=' + productId + '&quantity=' + qty
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Mahsulot savatga qo\'shildi!', 'success');
            const badge = document.querySelector('.action-btn[title="Savat"] .badge');
            if (badge) badge.textContent = data.count;
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
                btn.innerHTML = icon.outerHTML + ' Sevimlilarda';
                showToast('Sevimlilarga qo\'shildi', 'success');
            } else {
                icon.className = 'far fa-heart';
                btn.innerHTML = icon.outerHTML + ' Sevimlilarga qo\'shish';
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
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
