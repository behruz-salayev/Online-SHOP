<?php
/**
 * seller/orders.php - Sotuvchi buyurtmalari
 * 
 * Vazifalari:
 * - Sotuvchiga tegishli mahsulotlar bo'lgan buyurtmalar
 * - Status bo'yicha filtrlash
 * - Buyurtma holatini o'zgartirish
 * - Buyurtma tarixini ko'rish
 * - Mijoz ma'lumotlari va xaritada manzil
 */

require_once __DIR__ . '/../config/config.php';
User::requireLogin();
User::requireSeller();

$orderModel = new Order();

// Buyurtma holatini o'zgartirish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    ensure_csrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    // Sotuvchi buyurtma holatini o'zgartira oladi
    $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($newStatus, $validStatuses)) {
        $orderModel->updateStatus($orderId, $newStatus, $comment);
        flash('success', "Buyurtma #{$orderId} holati o'zgartirildi.");
    }
    redirect('seller/orders.php');
}

$currentStatus = $_GET['status'] ?? 'all';
$orders = $orderModel->getBySeller($_SESSION['user_id'], $currentStatus);

$title = 'Buyurtmalar';
require_once __DIR__ . '/../includes/seller_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-truck"></i> Buyurtmalar</h1>
</div>

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

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mijoz</th>
                    <th>Telefon</th>
                    <th>Manzil</th>
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
                        <td><?= htmlspecialchars($order['phone'] ?? $order['user_phone'] ?? '-') ?></td>
                        <td>
                            <?= htmlspecialchars($order['region_name'] ?? '') ?>,
                            <?= htmlspecialchars($order['district_name'] ?? '') ?><br>
                            <small><?= htmlspecialchars($order['address']) ?></small>
                        </td>
                        <td><strong><?= formatPrice($order['total_price']) ?></strong></td>
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
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="toggleOrderDetails(<?= $order['id'] ?>)">
                                <i class="fas fa-eye"></i> Ko'rish
                            </button>
                        </td>
                    </tr>
                    <tr class="order-details-row" id="order-details-<?= $order['id'] ?>" style="display:none;" data-lat="<?= $order['latitude'] ?? '' ?>" data-lng="<?= $order['longitude'] ?? '' ?>">
                        <td colspan="9">
                            <div class="order-details-panel">
                                <div class="order-details-grid">
                                    <!-- Mahsulotlar -->
                                    <div>
                                        <h4><i class="fas fa-box"></i> Buyurtma mahsulotlari</h4>
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

                                    <!-- Holatni o'zgartirish va tarix -->
                                    <div>
                                        <h4><i class="fas fa-edit"></i> Holatni o'zgartirish</h4>
                                        <form method="POST" class="status-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" class="form-control" required>
                                                <option value="">-- Tanlang --</option>
                                                <?php
                                                $statusOptions = [
                                                    'pending' => 'Kutilmoqda',
                                                    'confirmed' => 'Tasdiqlandi',
                                                    'processing' => 'Tayyorlanmoqda',
                                                    'shipped' => 'Yo\'lda',
                                                    'delivered' => 'Yetkazildi',
                                                    'cancelled' => 'Bekor qilindi'
                                                ];
                                                foreach ($statusOptions as $key => $label):
                                                ?>
                                                    <option value="<?= $key ?>" <?= $order['status'] == $key ? 'disabled' : '' ?>>
                                                        <?= $label ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="text" name="comment" class="form-control"
                                                   placeholder="Izoh (ixtiyoriy)" style="margin-top:8px">
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm" style="margin-top:8px;">
                                                <i class="fas fa-check"></i> Yangilash
                                            </button>
                                        </form>

                                        <h4 style="margin-top:16px;"><i class="fas fa-history"></i> Holat tarixi</h4>
                                        <div class="timeline small">
                                            <?php $history = $orderModel->getStatusHistory($order['id']); ?>
                                            <?php if (!empty($history)): ?>
                                                <?php foreach ($history as $h): ?>
                                                    <div class="timeline-item">
                                                        <div class="timeline-dot"></div>
                                                        <div class="timeline-content">
                                                            <strong><?= $orderModel->getStatusLabel($h['status']) ?></strong>
                                                            <?php if ($h['comment']): ?>
                                                                <p><?= htmlspecialchars($h['comment']) ?></p>
                                                            <?php endif; ?>
                                                            <small><?= date('d.m.Y H:i', strtotime($h['created_at'])) ?></small>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted">Tarix yo'q</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Xarita va mijoz ma'lumotlari -->
                                <div style="margin-top:16px;">
                                    <h4><i class="fas fa-map-marked-alt"></i> Mijoz manzili</h4>
                                    <p>
                                        <strong>Manzil:</strong>
                                        <?= htmlspecialchars($order['region_name'] ?? '') ?>,
                                        <?= htmlspecialchars($order['district_name'] ?? '') ?>,
                                        <?= htmlspecialchars($order['address']) ?>
                                    </p>
                                    <?php if ($order['latitude'] && $order['longitude']): ?>
                                        <a href="https://www.openstreetmap.org/?mlat=<?= $order['latitude'] ?>&mlon=<?= $order['longitude'] ?>&zoom=15"
                                           target="_blank" class="btn btn-sm btn-outline">
                                            <i class="fas fa-external-link-alt"></i> Xaritada ochish
                                        </a>
                                        <div id="map-<?= $order['id'] ?>" style="height:220px;border-radius:12px;margin-top:8px;"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="9" class="text-center">Buyurtmalar yo'q</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
.order-details-row { display: none; }
</style>
<script>
function toggleOrderDetails(orderId) {
    const rows = document.querySelectorAll('.order-details-row');
    rows.forEach(function(r) {
        if (r.id !== 'order-details-' + orderId) {
            r.style.display = 'none';
        }
    });

    const row = document.getElementById('order-details-' + orderId);
    if (!row) return;

    const isHidden = row.style.display === 'none' || !row.style.display;
    row.style.display = isHidden ? 'table-row' : 'none';

    if (isHidden) {
        var lat = row.getAttribute('data-lat');
        var lng = row.getAttribute('data-lng');
        if (lat && lng && lat !== '') {
            setTimeout(function() {
                var mapId = 'map-' + orderId;
                var mapEl = document.getElementById(mapId);
                if (mapEl && !mapEl._leaflet_map) {
                    var map = L.map(mapId).setView([lat, lng], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
                    L.marker([lat, lng]).addTo(map);
                    mapEl._leaflet_map = true;
                }
            }, 300);
        }
    }
}
</script>

<?php require_once __DIR__ . '/../includes/seller_footer.php'; ?>
