<?php
/**
 * profile.php - Foydalanuvchi profili sahifasi
 * 
 * Vazifalari:
 * - Shaxsiy ma'lumotlarni tahrirlash
 * - Buyurtmalar tarixini ko'rish
 * - Parolni o'zgartirish
 * - Sevimlilarga o'tish
 */

require_once __DIR__ . '/../config/config.php';

// Kirish talab qilinadi
User::requireLogin();

$title = 'Mening profilim';

// Modellarni yuklash
$userModel = new User();
$sellerRequestModel = new SellerRequest();
$sellerRequest = $sellerRequestModel->getByUserId($_SESSION['user_id']);
$user = $userModel->getById($_SESSION['user_id']);

if (!$user) {
    $_SESSION['error'] = 'Foydalanuvchi topilmadi. Iltimos, qayta tizimga kiring.';
    redirect('users/login.php');
}

$orderModel = new Order();
$orders = $orderModel->getByUser($_SESSION['user_id']);
$wishlist = new Wishlist();
$wishlistCount = $wishlist->getCount();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Non-breadcrumbs -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a> <span>/</span>
    <span>Mening profilim</span>
</div>

<h1 class="page-title"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['full_name'] ?? '') ?></h1>

<div class="profile-layout">
    <!-- Yon panel -->
    <div class="profile-sidebar">
        <div class="profile-avatar">
            <i class="fas fa-user-circle"></i>
            <h3><?= htmlspecialchars($user['full_name'] ?? '') ?></h3>
            <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
        </div>

        <!-- Sotuvchi arizasi holati -->
        <?php if (!User::isSeller() && !User::isAdmin()): ?>
            <?php if ($sellerRequest && $sellerRequest['status'] === 'pending'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-clock"></i>
                    Sotuvchi arizangiz ko'rib chiqilmoqda.
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/users/become_seller.php" 
                   class="btn btn-sm btn-primary" style="display:block;margin-bottom:12px;text-align:center;">
                    Sotuvchi bo'lish
                </a>
            <?php endif; ?>
        <?php elseif (User::isSeller()): ?>
            <a href="<?= SITE_URL ?>/seller/index.php" 
               class="btn btn-sm btn-primary" style="display:block;margin-bottom:12px;text-align:center;">
                Sotuvchi paneliga o'tish
            </a>
        <?php endif; ?>

        <!-- Profil navigatsiyasi -->
        <ul class="profile-nav">
            <li><a href="#profile-info" class="active" onclick="showTab('profile-info', this)">
                <i class="fas fa-user"></i> Shaxsiy ma'lumotlar</a></li>
            <li><a href="#profile-orders" onclick="showTab('profile-orders', this)">
                <i class="fas fa-box"></i> Buyurtmalarim (<?= count($orders) ?>)</a></li>
            <li><a href="#profile-password" onclick="showTab('profile-password', this)">
                <i class="fas fa-lock"></i> Parolni o'zgartirish</a></li>
            <li><a href="<?= SITE_URL ?>/wishlist.php">
                <i class="fas fa-heart"></i> Sevimlilar (<?= $wishlistCount ?>)</a></li>
            <li class="profile-nav-divider"></li>
            <li><a href="<?= SITE_URL ?>/users/logout.php" class="text-danger">
                <i class="fas fa-sign-out-alt"></i> Chiqish</a></li>
        </ul>
    </div>

    <!-- Asosiy kontent -->
    <div class="profile-content">
        <!-- Shaxsiy ma'lumotlar -->
        <div id="profile-info" class="tab-content active">
            <h2>Shaxsiy ma'lumotlar</h2>
            
            <form method="POST" action="<?= SITE_URL ?>/includes/profile_update.inc.php" class="profile-form">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>To'liq ism</label>
                    <input type="text" name="full_name" 
                           value="<?= htmlspecialchars($user['full_name']) ?>" 
                           required class="form-control">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" 
                           class="form-control" readonly disabled>
                </div>
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="phone" 
                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
                           required class="form-control">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Saqlash</button>
            </form>
        </div>

        <!-- Buyurtmalar -->
        <div id="profile-orders" class="tab-content">
            <h2>Mening buyurtmalarim</h2>
            <?php if (empty($orders)): ?>
                <div class="empty-state small">
                    <i class="fas fa-box-open"></i>
                    <p>Hali buyurtma bermagansiz</p>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <a href="<?= SITE_URL ?>/order_success.php?id=<?= $order['id'] ?>" class="order-card">
                            <div class="order-id">Buyurtma #<?= $order['id'] ?></div>
                            <div class="order-date"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                            <div class="order-amount"><?= formatPrice($order['total_price']) ?></div>
                            <div class="order-status">
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= (new Order())->getStatusLabel($order['status']) ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Parolni o'zgartirish -->
        <div id="profile-password" class="tab-content">
            <h2>Parolni o'zgartirish</h2>
            
            <?php
            $passErrors = flash('password_errors');
            if ($passErrors):
            ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($passErrors as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= SITE_URL ?>/includes/profile_update.inc.php" class="profile-form">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label>Joriy parol</label>
                    <input type="password" name="current_password" required class="form-control">
                </div>
                <div class="form-group">
                    <label>Yangi parol <small>(kamida 6 belgi)</small></label>
                    <input type="password" name="new_password" required class="form-control" minlength="6">
                </div>
                <button type="submit" name="update_password" class="btn btn-primary">Parolni yangilash</button>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript: Tablarni almashtirish -->
<script>
function showTab(tabId, link) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.profile-nav a').forEach(a => a.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    if (link) link.classList.add('active');
}

// URL hash bo'yicha tabni ochish
if (window.location.hash) {
    const hash = window.location.hash.substring(1);
    const link = document.querySelector(`.profile-nav a[href="#${hash}"]`);
    if (link) showTab(hash, link);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
