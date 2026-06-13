<?php
/**
 * admin/index.php - Admin panel bosh sahifasi (Dashboard)
 * 
 * Vazifalari:
 * - Umumiy statistika (buyurtmalar, daromad, mahsulotlar)
 * - So'nggi buyurtmalar ro'yxati
 */

require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$orderModel = new Order();
$sellerRequestModel = new SellerRequest();
$productModel = new Product();

// Statistik ma'lumotlar
$stats = $orderModel->getStats();
$pendingRequests = $sellerRequestModel->countByStatus('pending');
$pendingProducts = $productModel->countByApprovalStatus('pending');

// So'nggi 10 ta buyurtma
$recentOrders = $orderModel->getAll();
$recentOrders = array_slice($recentOrders, 0, 10);

$title = 'Admin Panel';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

<!-- Statistika kartalari -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: #1a73e8;"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-info">
            <h3><?= $stats['total_orders'] ?></h3>
            <p>Jami buyurtmalar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #f9ab00;"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= $stats['pending_orders'] ?></h3>
            <p>Kutilayotgan buyurtmalar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #0f9d58;"><i class="fas fa-money-bill-wave"></i></div>
        <div class="stat-info">
            <h3><?= formatPrice($stats['total_revenue']) ?></h3>
            <p>Jami daromad</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #7c3aed;"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <h3><?= $stats['total_products'] ?></h3>
            <p>Faol mahsulotlar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #ea580c;"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= $pendingProducts ?></h3>
            <p>Kutilayotgan mahsulotlar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #6366f1;"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h3><?= $pendingRequests ?></h3>
            <p>Sotuvchi arizalari</p>
        </div>
    </div>
</div>

<!-- So'nggi buyurtmalar -->
<div class="admin-section">
    <div class="section-header">
        <h2><i class="fas fa-list"></i> So'nggi buyurtmalar</h2>
        <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-sm btn-outline">Barchasi</a>
    </div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mijoz</th>
                    <th>Summa</th>
                    <th>To'lov</th>
                    <th>Holat</th>
                    <th>Sana</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td><?= formatPrice($order['total_price']) ?></td>
                        <td>
                            <?= $order['payment_method'] == 'cash' ? 'Naqd' : 
                                ($order['payment_method'] == 'card' ? 'Karta' : 'O\'tkazma') ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= $orderModel->getStatusLabel($order['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="6" class="text-center">Hali buyurtmalar yo'q</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
