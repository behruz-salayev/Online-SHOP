<?php
/**
 * seller/index.php - Sotuvchi paneli bosh sahifasi (Dashboard)
 * 
 * Vazifalari:
 * - Statistika: tasdiqlangan/kutilayotgan mahsulotlar, buyurtmalar
 * - Sotuvchi ma'lumotlari
 */

require_once __DIR__ . '/../config/config.php';
User::requireLogin();
User::requireSeller();

$sellerModel = new Seller();
$productModel = new Product();
$orderModel = new Order();

$seller = $sellerModel->getByUserId($_SESSION['user_id']);
if (!$seller) {
    flash('error', 'Sotuvchi profili topilmadi. Iltimos, admin bilan bog\'laning.');
    redirect('index.php');
}

$products = $productModel->getAllBySeller($_SESSION['user_id']);
$orders = $orderModel->getBySeller($_SESSION['user_id']);
$totalProducts = count($products);
$activeProducts = count(array_filter($products, fn($p) => $p['status'] === 'active'));
$revenueStats = $orderModel->getSellerRevenueStats($_SESSION['user_id']);

$title = 'Dashboard';
require_once __DIR__ . '/../includes/seller_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #1a73e8;"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <h3><?= $totalProducts ?></h3>
            <p>Jami mahsulotlar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #0f9d58;"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?= $activeProducts ?></h3>
            <p>Faol mahsulotlar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #f9ab00;"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-info">
            <h3><?= count($orders) ?></h3>
            <p>Buyurtmalar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #7c3aed;"><i class="fas fa-user"></i></div>
        <div class="stat-info">
            <h3><?= htmlspecialchars($seller['business_name']) ?></h3>
            <p>Sotuvchi profili</p>
        </div>
    </div>
</div>

<!-- Daromad statistikasi -->
<div class="admin-section">
    <div class="section-header">
        <h2><i class="fas fa-chart-line"></i> Daromad statistikasi</h2>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #0f9d58;"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-info">
                <h3><?= formatPrice($revenueStats['today']['net']) ?></h3>
                <p>Bugun <span style="color:var(--success);font-size:12px">+<?= formatPrice($revenueStats['today']['income']) ?></span> <span style="color:var(--danger);font-size:12px">-<?= formatPrice($revenueStats['today']['loss']) ?></span></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #1a73e8;"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-info">
                <h3><?= formatPrice($revenueStats['week']['net']) ?></h3>
                <p>Haftalik <span style="color:var(--success);font-size:12px">+<?= formatPrice($revenueStats['week']['income']) ?></span> <span style="color:var(--danger);font-size:12px">-<?= formatPrice($revenueStats['week']['loss']) ?></span></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #7c3aed;"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info">
                <h3><?= formatPrice($revenueStats['month']['net']) ?></h3>
                <p>Oylik <span style="color:var(--success);font-size:12px">+<?= formatPrice($revenueStats['month']['income']) ?></span> <span style="color:var(--danger);font-size:12px">-<?= formatPrice($revenueStats['month']['loss']) ?></span></p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ea580c;"><i class="fas fa-infinity"></i></div>
            <div class="stat-info">
                <h3><?= formatPrice($revenueStats['all_time']['net']) ?></h3>
                <p>Jami <span style="color:var(--success);font-size:12px">+<?= formatPrice($revenueStats['all_time']['income']) ?></span> <span style="color:var(--danger);font-size:12px">-<?= formatPrice($revenueStats['all_time']['loss']) ?></span></p>
            </div>
        </div>
    </div>
</div>

<div class="seller-profile-card">
    <div class="seller-profile-header">
        <div class="seller-avatar">
            <?php if ($seller['logo']): ?>
                <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($seller['logo']) ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-store"></i>
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($seller['business_name']) ?></h2>
        <span class="seller-badge"><i class="fas fa-check-circle"></i> Sotuvchi</span>
        <?php if ($seller['business_description']): ?>
            <p class="seller-desc"><?= nl2br(htmlspecialchars($seller['business_description'])) ?></p>
        <?php endif; ?>
    </div>
    <div class="seller-profile-body">
        <div class="seller-info-grid">
            <div class="seller-info-item">
                <i class="fas fa-phone"></i>
                <div>
                    <label>Telefon</label>
                    <span><?= htmlspecialchars($seller['phone']) ?></span>
                </div>
            </div>
            <div class="seller-info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <label>Manzil</label>
                    <span><?= htmlspecialchars($seller['region_name'] ?? '') ?>, <?= htmlspecialchars($seller['district_name'] ?? '') ?>, <?= htmlspecialchars($seller['address']) ?></span>
                </div>
            </div>
            <div class="seller-info-item">
                <i class="fas fa-calendar-alt"></i>
                <div>
                    <label>Ro'yxatdan o'tgan</label>
                    <span><?= date('d.m.Y', strtotime($seller['created_at'])) ?></span>
                </div>
            </div>
            <div class="seller-info-item">
                <i class="fas fa-box"></i>
                <div>
                    <label>Mahsulotlar</label>
                    <span><?= $totalProducts ?> ta</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="admin-section">
    <div class="section-header">
        <h2><i class="fas fa-list"></i> So'nggi buyurtmalar</h2>
        <a href="<?= SITE_URL ?>/seller/orders.php" class="btn btn-sm btn-outline">Barchasi</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mijoz</th>
                    <th>Summa</th>
                    <th>Holat</th>
                    <th>Sana</th>
                </tr>
            </thead>
            <tbody>
                <?php $recentOrders = array_slice($orders, 0, 5); ?>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td><?= formatPrice($order['total_price']) ?></td>
                        <td><span class="status-badge status-<?= htmlspecialchars($order['status']) ?>"><?= $order['status'] == 'pending' ? 'Kutilmoqda' : ($order['status'] == 'confirmed' ? 'Tasdiqlandi' : ($order['status'] == 'processing' ? 'Tayyorlanmoqda' : ($order['status'] == 'shipped' ? 'Yo\'lda' : ($order['status'] == 'delivered' ? 'Yetkazildi' : 'Bekor qilindi')))) ?></span></td>
                        <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="5" class="text-center">Hali buyurtmalar yo'q</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/seller_footer.php'; ?>
