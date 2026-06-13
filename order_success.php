<?php
/**
 * order_success.php - Buyurtma tasdiqlash sahifasi
 * 
 * Buyurtma muvaffaqiyatli yaratilgandan so'ng
 * buyurtma tafsilotlari ko'rsatiladi.
 */

require_once __DIR__ . '/config/config.php';

// Kirish talab qilinadi
User::requireLogin();

// Buyurtma ID sini olish
$orderId = (int)($_GET['id'] ?? 0);
$orderModel = new Order();
$order = $orderModel->getById($orderId);

// Buyurtma topilmasa yoki boshqa foydalanuvchining buyurtmasi bo'lsa
if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    flash('error', 'Buyurtma topilmadi');
    redirect('index.php');
}

$items = $orderModel->getItems($orderId);
$statusHistory = $orderModel->getStatusHistory($orderId);
$title = 'Buyurtma #' . $orderId;

require_once __DIR__ . '/includes/header.php';
?>

<!-- Non-breadcrumbs -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a> <span>/</span>
    <span>Buyurtma #<?= $orderId ?></span>
</div>

<!-- Muvaffaqiyat xabari -->
<div class="order-success">
    <div class="success-header">
        <i class="fas fa-check-circle"></i>
        <h1>Buyurtmangiz qabul qilindi!</h1>
        <p>Buyurtma raqami: <strong>#<?= $orderId ?></strong></p>
    </div>

    <div class="order-details">
        <!-- Buyurtma haqida -->
        <div class="order-detail-section">
            <h3><i class="fas fa-info-circle"></i> Buyurtma haqida</h3>
            <table class="detail-table">
                <tr>
                    <td>Holati:</td>
                    <td><span class="status-badge status-<?= $order['status'] ?>">
                        <?= $orderModel->getStatusLabel($order['status']) ?>
                    </span></td>
                </tr>
                <tr>
                    <td>To'lov turi:</td>
                    <td>
                        <?= $order['payment_method'] == 'cash' ? 'Naqd pul' : 
                            ($order['payment_method'] == 'card' ? 'Plastik karta' : 'Pul o\'tkazmasi') ?>
                    </td>
                </tr>
                <tr><td>Summa:</td><td><strong><?= formatPrice($order['total_price']) ?></strong></td></tr>
                <tr><td>Yaratilgan vaqt:</td><td><?= $order['created_at'] ?></td></tr>
            </table>
        </div>

        <!-- Yetkazib berish -->
        <div class="order-detail-section">
            <h3><i class="fas fa-map-marker-alt"></i> Yetkazib berish</h3>
            <table class="detail-table">
                <tr><td>Viloyat:</td><td><?= htmlspecialchars($order['region_name']) ?></td></tr>
                <tr><td>Tuman:</td><td><?= htmlspecialchars($order['district_name']) ?></td></tr>
                <tr><td>Manzil:</td><td><?= htmlspecialchars($order['address']) ?></td></tr>
                <tr><td>Telefon:</td><td><?= htmlspecialchars($order['phone']) ?></td></tr>
                <?php if ($order['latitude'] && $order['longitude']): ?>
                    <tr>
                        <td>Xaritada:</td>
                        <td>
                            <a href="https://www.openstreetmap.org/?mlat=<?= $order['latitude'] ?>&mlon=<?= $order['longitude'] ?>&zoom=15" 
                               target="_blank" class="btn btn-sm btn-outline">
                                <i class="fas fa-map"></i> Xaritada ko'rish
                            </a>
                        </td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <!-- Mahsulotlar -->
        <div class="order-detail-section">
            <h3><i class="fas fa-box"></i> Mahsulotlar</h3>
            <table class="items-table">
                <thead>
                    <tr><th>Mahsulot</th><th>Narx</th><th>Soni</th><th>Summa</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= formatPrice($item['product_price']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= formatPrice($item['product_price'] * $item['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Jami:</strong></td>
                        <td><strong><?= formatPrice($order['total_price']) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Holat tarixi -->
        <div class="order-detail-section">
            <h3><i class="fas fa-clock"></i> Buyurtma holati tarixi</h3>
            <div class="timeline">
                <?php foreach ($statusHistory as $history): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <p><?= htmlspecialchars($history['comment']) ?></p>
                            <small><?= $history['created_at'] ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tugmalar -->
    <div class="success-actions">
        <a href="<?= SITE_URL ?>/users/profile.php" class="btn btn-primary">
            <i class="fas fa-list"></i> Mening buyurtmalarim
        </a>
        <a href="<?= SITE_URL ?>/index.php" class="btn btn-outline">
            <i class="fas fa-shopping-bag"></i> Xaridni davom ettirish
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
