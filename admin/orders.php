<?php
/**
 * admin/orders.php - Buyurtmalarni boshqarish (Admin)
 * 
 * Vazifalari:
 * - Barcha buyurtmalarni ko'rish
 * - Status bo'yicha filtrlash
 * - Buyurtma holatini o'zgartirish
 * - Buyurtma tarixini ko'rish
 * - Xaritada mijoz manzilini ko'rish
 */

require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$orderModel = new Order();

// Buyurtma holatini o'zgartirish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    ensure_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        $orderModel->updateStatus($orderId, $newStatus, $comment);
        $_SESSION['success'] = "Buyurtma #{$orderId} holati o'zgartirildi";
    }
    redirect('admin/orders.php');
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportStatus = $_GET['status'] ?? 'all';
    $exportOrders = $orderModel->getAll($exportStatus);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="buyurtmalar.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, "\xEF\xBB\xBF");
    fputcsv($output, ['ID', 'Mijoz', 'Telefon', 'Manzil', 'Summa', "To'lov", 'Holat', 'Sana']);

    foreach ($exportOrders as $o) {
        fputcsv($output, [
            $o['id'],
            $o['full_name'],
            $o['phone'] ?? $o['user_phone'] ?? '',
            $o['region_name'] . ', ' . $o['district_name'] . ', ' . $o['address'],
            $o['total_price'],
            $o['payment_method'],
            $o['status'],
            $o['created_at'],
        ]);
    }
    fclose($output);
    exit;
}

$currentStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$orders = $orderModel->getAll($currentStatus);

if (!empty($search)) {
    $orders = array_filter($orders, fn($o) =>
        stripos((string)$o['id'], $search) !== false ||
        stripos($o['full_name'], $search) !== false ||
        stripos($o['phone'] ?? $o['user_phone'] ?? '', $search) !== false
    );
}

$title = 'Buyurtmalar';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-truck"></i> Buyurtmalar</h1>
    <a href="?export=csv&status=<?= $currentStatus ?>" class="btn btn-outline"><i class="fas fa-download"></i> CSV export</a>
</div>

<div class="admin-card" style="margin-bottom:16px;">
    <form method="GET">
        <div class="form-row" style="align-items:end;">
            <input type="hidden" name="status" value="<?= $currentStatus ?>">
            <div class="form-group" style="flex:1;">
                <input type="text" name="search" class="form-control" placeholder="Buyurtma ID, mijoz nomi yoki telefon bo'yicha qidirish..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;gap:6px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Qidirish</button>
                <?php if ($search): ?>
                    <a href="?status=<?= $currentStatus ?>" class="btn btn-outline"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Status filtr tugmalari -->
<div class="order-tabs">
    <?php
    $tabs = [
        'all' => 'Barchasi',
        'pending' => 'Kutilmoqda',
        'confirmed' => 'Tasdiqlandi',
        'processing' => 'Tayyorlanmoqda',
        'shipped' => 'Yo\'lda',
        'delivered' => 'Yetkazildi',
        'cancelled' => 'Bekor qilindi'
    ];
    foreach ($tabs as $key => $label): ?>
        <a href="?status=<?= $key ?>" class="tab-btn <?= $currentStatus == $key ? 'active' : '' ?>">
            <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- Buyurtmalar jadvali -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mijoz</th>
                    <th>Telefon</th>
                    <th>Manzil</th>
                    <th>Xarita</th>
                    <th>Summa</th>
                    <th>To'lov</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order['id'] ?></strong></td>
                        <td><?= htmlspecialchars($order['full_name']) ?></td>
                        <td><?= htmlspecialchars($order['phone'] ?? $order['user_phone']) ?></td>
                        <td>
                            <?= htmlspecialchars($order['region_name'] ?? '') ?>,
                            <?= htmlspecialchars($order['district_name'] ?? '') ?><br>
                            <small><?= htmlspecialchars($order['address']) ?></small>
                        </td>
                        <td>
                            <?php if ($order['latitude'] && $order['longitude']): ?>
                                <a href="https://www.openstreetmap.org/?mlat=<?= $order['latitude'] ?>&mlon=<?= $order['longitude'] ?>&zoom=15" 
                                   target="_blank" class="btn btn-sm btn-outline" title="Xaritada ko'rish">
                                    <i class="fas fa-map-marked-alt"></i>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= formatPrice($order['total_price']) ?></strong></td>
                        <td><?= $order['payment_method'] == 'cash' ? 'Naqd' : ($order['payment_method'] == 'card' ? 'Karta' : 'O\'tkazma') ?></td>
                        <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= $orderModel->getStatusLabel($order['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($order['created_at'])) ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="toggleOrderDetails(<?= $order['id'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <!-- Buyurtma detallari (yashirin qator) -->
                    <tr class="order-details-row" id="order-details-<?= $order['id'] ?>" style="display:none;">
                        <td colspan="10">
                            <div class="order-details-panel">
                                <div class="order-details-grid">
                                    <!-- Mahsulotlar -->
                                    <div>
                                        <h4>Buyurtma mahsulotlari</h4>
                                        <table class="admin-table mini">
                                            <thead><tr><th>Mahsulot</th><th>Narx</th><th>Soni</th><th>Summa</th></tr></thead>
                                            <tbody>
                                                <?php $items = $orderModel->getItems($order['id']); ?>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                                                        <td><?= formatPrice($item['product_price']) ?></td>
                                                        <td><?= $item['quantity'] ?></td>
                                                        <td><?= formatPrice($item['product_price'] * $item['quantity']) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Holatni o'zgartirish + Tarix -->
                                    <div>
                                        <h4>Holatni o'zgartirish</h4>
                                        <form method="POST" class="status-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" class="form-control">
                                                <?php foreach ($tabs as $key => $label): ?>
                                                    <?php if ($key != 'all'): ?>
                                                        <option value="<?= $key ?>" <?= $order['status'] == $key ? 'selected' : '' ?>>
                                                            <?= $label ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="comment" class="form-control" 
                                                   placeholder="Izoh (ixtiyoriy)" style="margin-top:8px">
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm" style="margin-top:8px">
                                                <i class="fas fa-check"></i> Yangilash
                                            </button>
                                        </form>

                                        <h4 style="margin-top:16px">Holat tarixi</h4>
                                        <div class="timeline small">
                                            <?php $history = $orderModel->getStatusHistory($order['id']); ?>
                                            <?php foreach ($history as $h): ?>
                                                <div class="timeline-item">
                                                    <div class="timeline-dot"></div>
                                                    <div class="timeline-content">
                                                        <p><?= htmlspecialchars($h['comment']) ?></p>
                                                        <small><?= $h['created_at'] ?></small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Xarita -->
                                <?php if ($order['latitude'] && $order['longitude']): ?>
                                <div style="margin-top:12px">
                                    <h4>Mijoz manzili xaritada</h4>
                                    <div id="map-<?= $order['id'] ?>" style="height:250px;border-radius:12px;margin-top:8px;"></div>
                                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                                    <script>
                                    setTimeout(function() {
                                        const map<?= $order['id'] ?> = L.map('map-<?= $order['id'] ?>')
                                            .setView([<?= $order['latitude'] ?>, <?= $order['longitude'] ?>], 15);
                                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
                                            .addTo(map<?= $order['id'] ?>);
                                        L.marker([<?= $order['latitude'] ?>, <?= $order['longitude'] ?>])
                                            .addTo(map<?= $order['id'] ?>);
                                    }, 200);
                                    </script>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="10" class="text-center">Buyurtmalar yo'q</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JavaScript -->
<script>
function toggleOrderDetails(orderId) {
    const row = document.getElementById('order-details-' + orderId);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
