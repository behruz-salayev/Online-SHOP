<?php
/**
 * admin/pending_products.php - Kutilayotgan mahsulotlar
 * 
 * Sotuvchilar tomonidan qo'shilgan va admin tasdig'ini
 * kutayotgan mahsulotlar ro'yxati.
 */

require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$productModel = new Product();

// Tasdiqlash yoki rad etish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['product_id'])) {
    ensure_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $productModel->setApprovalStatus($productId, 'approved');
        $_SESSION['success'] = 'Mahsulot tasdiqlandi.';
    } elseif ($action === 'reject') {
        $productModel->setApprovalStatus($productId, 'rejected');
        $_SESSION['success'] = 'Mahsulot rad etildi.';
    }
    redirect('admin/pending_products.php');
}

$pendingProducts = $productModel->getAllAdmin('', '', 'pending');
$title = 'Kutilayotgan mahsulotlar';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-clock"></i> Kutilayotgan mahsulotlar</h1>
</div>

<div class="alert alert-info" style="padding:12px 16px;background:#e8f4fd;border-radius:8px;margin-bottom:16px;border-left:4px solid #1a73e8;">
    <i class="fas fa-info-circle"></i> 
    Endi sotuvchilar mahsulotlarini admin tasdiqisiz bozorga qo'yishlari mumkin. Barcha yangi mahsulotlar avtomatik tasdiqlanadi.
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mahsulot</th>
                    <th>Sotuvchi</th>
                    <th>Kategoriya</th>
                    <th>Narx</th>
                    <th>Soni</th>
                    <th>Holat</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingProducts as $product): ?>
                    <tr>
                        <td>#<?= $product['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($product['name']) ?><br>
                            <small><?= htmlspecialchars($product['slug']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($product['seller_name'] ?? 'Admin') ?></td>
                        <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                        <td><?= formatPrice($product['price']) ?></td>
                        <td><?= $product['stock'] ?></td>
                        <td><span class="status-badge status-pending">Kutilmoqda</span></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Tasdiqlash</button>
                            </form>
                            <form method="POST" style="display:inline;margin-left:6px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Rad etish</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($pendingProducts)): ?>
                    <tr><td colspan="8" class="text-center">Kutilayotgan mahsulotlar topilmadi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
