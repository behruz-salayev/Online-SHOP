<?php
/**
 * admin/seller_requests.php - Sotuvchi arizalarini boshqarish
 * 
 * Admin:
 * - Barcha arizalarni ko'radi
 * - Arizani tasdiqlaydi yoki rad etadi
 * - Tasdiqlanganda seller yozuvi yaratiladi
 */

require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$sellerRequestModel = new SellerRequest();
$sellerModel = new Seller();
$userModel = new User();

// Arizani tasdiqlash yoki rad etish
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['request_id'])) {
    ensure_csrf();
    $action = $_POST['action'];
    $requestId = (int)($_POST['request_id'] ?? 0);
    $request = $sellerRequestModel->getById($requestId);

    if (!$request) {
        flash('error', 'Ariza topilmadi');
        redirect('admin/seller_requests.php');
    }

    if ($action === 'approve') {
        // Seller yozuvini yaratish (agar mavjud bo'lmasa)
        if (!$sellerModel->getByUserId($request['user_id'])) {
            $sellerModel->create([
                'user_id' => $request['user_id'],
                'business_name' => $request['business_name'],
                'business_description' => $request['business_description'],
                'phone' => $request['phone'],
                'region_id' => $request['region_id'],
                'district_id' => $request['district_id'],
                'address' => $request['address'],
                'logo' => $request['logo'],
            ]);
        }
        // Foydalanuvchini sotuvchi qilish
        $userModel->promoteToSeller($request['user_id']);
        $sellerRequestModel->updateStatus($requestId, 'approved');
        $_SESSION['success'] = 'Ariza tasdiqlandi va foydalanuvchi sotuvchi qilindi.';
    } elseif ($action === 'reject') {
        $sellerRequestModel->updateStatus($requestId, 'rejected');
        $_SESSION['success'] = 'Ariza rad etildi.';
    }

    redirect('admin/seller_requests.php');
}

$statusFilter = $_GET['status'] ?? '';
$requests = $sellerRequestModel->getAll($statusFilter);

$title = 'Sotuvchi arizalari';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-user-check"></i> Sotuvchi arizalari</h1>
</div>

<!-- Status filtr -->
<div class="order-tabs">
    <a href="?" class="tab-btn <?= $statusFilter === '' ? 'active' : '' ?>">Barchasi</a>
    <a href="?status=pending" class="tab-btn <?= $statusFilter === 'pending' ? 'active' : '' ?>">Kutilmoqda</a>
    <a href="?status=approved" class="tab-btn <?= $statusFilter === 'approved' ? 'active' : '' ?>">Tasdiqlangan</a>
    <a href="?status=rejected" class="tab-btn <?= $statusFilter === 'rejected' ? 'active' : '' ?>">Rad etilgan</a>
</div>

<!-- Arizalar jadvali -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Foydalanuvchi</th>
                    <th>Biznes nomi</th>
                    <th>Telefon</th>
                    <th>Manzil</th>
                    <th>Status</th>
                    <th>Sana</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <tr>
                        <td>#<?= $request['id'] ?></td>
                        <td>
                            <?= htmlspecialchars($request['user_name']) ?><br>
                            <small><?= htmlspecialchars($request['user_email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($request['business_name']) ?></td>
                        <td><?= htmlspecialchars($request['phone']) ?></td>
                        <td>
                            <?= htmlspecialchars($request['region_name'] ?? '-') ?>,
                            <?= htmlspecialchars($request['district_name'] ?? '-') ?><br>
                            <small><?= htmlspecialchars($request['address']) ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?= htmlspecialchars($request['status']) ?>">
                                <?= htmlspecialchars($request['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($request['created_at']) ?></td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">
                                        Tasdiqlash
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;margin-left:6px;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">
                                        Rad etish
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Harakat yo'q</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="8" class="text-center">Arizalar topilmadi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
