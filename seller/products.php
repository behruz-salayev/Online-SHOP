<?php
/**
 * seller/products.php - Mahsulotlarni boshqarish (Sotuvchi)
 * 
 * Vazifalari:
 * - Barcha mahsulotlar ro'yxati
 * - Mahsulotni o'chirish
 */

require_once __DIR__ . '/../config/config.php';
User::requireLogin();
User::requireSeller();

$productModel = new Product();
$sellerUserId = $_SESSION['user_id'];

// Mahsulotni o'chirish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    ensure_csrf();
    $productId = (int)($_POST['product_id'] ?? 0);
    $product = $productModel->getBySeller($sellerUserId, $productId);
    if ($product) {
        $productModel->delete($productId);
        flash('success', 'Mahsulot o\'chirildi.');
    } else {
        flash('error', 'Mahsulot topilmadi yoki sizga tegishli emas.');
    }
    redirect('seller/products.php');
}

$products = $productModel->getAllBySeller($sellerUserId);
$title = 'Mahsulotlar';
require_once __DIR__ . '/../includes/seller_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-box"></i> Mahsulotlar</h1>
    <a href="<?= SITE_URL ?>/seller/add_product.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Yangi mahsulot
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Rasm</th>
                    <th>Nomi</th>
                    <th>Kategoriya</th>
                    <th>Narx</th>
                    <th>Soni</th>
                    <th>Holat</th>
                    <th>Tasdiq</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td>#<?= $product['id'] ?></td>
                        <td>
                            <?php if ($product['image']): ?>
                                <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($product['image']) ?>"
                                     style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                        <td><?= formatPrice($product['price']) ?></td>
                        <td><?= $product['stock'] ?></td>
                        <td><span class="status-badge status-<?= htmlspecialchars($product['status']) ?>"><?= $product['status'] == 'active' ? 'Faol' : 'Faol emas' ?></span></td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($product['approval_status']) ?>">
                                <?= $product['approval_status'] == 'approved' ? 'Tasdiqlangan' : ($product['approval_status'] == 'pending' ? 'Kutilmoqda' : 'Rad etilgan') ?>
                            </span>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="<?= SITE_URL ?>/seller/add_product.php?edit=<?= $product['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Mahsulotni o\'chirmoqchimisiz?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" name="delete_product" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <tr><td colspan="9" class="text-center">Mahsulotlar topilmadi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/seller_footer.php'; ?>
