<?php
require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$orderModel = new Order();
$userModel = new User();
$sellerRequestModel = new SellerRequest();
$productModel = new Product();

$stats = $orderModel->getStats();
$pendingRequests = $sellerRequestModel->countByStatus('pending');
$pendingProducts = $productModel->countByApprovalStatus('pending');

$recentOrders = $orderModel->getAll();
$recentOrders = array_slice($recentOrders, 0, 10);

$allUsers = $userModel->getAll();
$totalUsers = count($allUsers);
$totalSellers = count(array_filter($allUsers, fn($u) => $u['role'] === 'seller'));
$totalAdmins = count(array_filter($allUsers, fn($u) => $u['role'] === 'admin'));

$monthlyRevenue = $orderModel->getMonthlyRevenue();
$dailyOrders = $orderModel->getDailyOrderStats();

$title = 'Admin Panel';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
</div>

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
    <div class="stat-card">
        <div class="stat-icon" style="background: #e91e63;"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= $totalUsers ?></h3>
            <p>Foydalanuvchilar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: #00bcd4;"><i class="fas fa-store"></i></div>
        <div class="stat-info">
            <h3><?= $totalSellers ?></h3>
            <p>Sotuvchilar</p>
        </div>
    </div>
</div>

<div class="admin-section" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="admin-card">
        <h2><i class="fas fa-chart-line"></i> Oylik daromad (so'm)</h2>
        <canvas id="revenueChart" style="max-height:250px;margin-top:8px;"></canvas>
    </div>
    <div class="admin-card">
        <h2><i class="fas fa-chart-bar"></i> Kunlik buyurtmalar</h2>
        <canvas id="ordersChart" style="max-height:250px;margin-top:8px;"></canvas>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthNames = ['Yan','Fev','Mar','Apr','May','Iyun','Iyul','Avg','Sen','Okt','Noy','Dek'];
    const months = <?= json_encode(array_map(fn($m) => $monthNames[$m-1], array_column($monthlyRevenue, 'month'))) ?>;
    const revenues = <?= json_encode(array_column($monthlyRevenue, 'total')) ?>;

    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Daromad',
                data: revenues,
                borderColor: '#0f9d58',
                backgroundColor: 'rgba(15,157,88,0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() + ' so\'m' } } }
        }
    });

    const days = <?= json_encode(array_column($dailyOrders, 'date')) ?>;
    const counts = <?= json_encode(array_column($dailyOrders, 'count')) ?>;

    new Chart(document.getElementById('ordersChart'), {
        type: 'bar',
        data: {
            labels: days,
            datasets: [{
                label: 'Buyurtmalar',
                data: counts,
                backgroundColor: '#1a73e8',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
