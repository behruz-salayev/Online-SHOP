<?php
require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$reviewModel = new Review();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_csrf();

    if (isset($_POST['approve'])) {
        $reviewModel->setApprovalStatus((int)$_POST['review_id'], 'approved');
        $_SESSION['success'] = 'Sharh tasdiqlandi.';
    } elseif (isset($_POST['reject'])) {
        $reviewModel->setApprovalStatus((int)$_POST['review_id'], 'rejected');
        $_SESSION['success'] = 'Sharh rad etildi.';
    } elseif (isset($_POST['delete'])) {
        $reviewModel->delete((int)$_POST['review_id']);
        $_SESSION['success'] = 'Sharh o\'chirildi.';
    }
    redirect('admin/reviews.php');
}

$tab = $_GET['tab'] ?? 'pending';
$reviews = $tab === 'all' ? $reviewModel->getAll() : $reviewModel->getPending();

$title = 'Sharhlar';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-star"></i> Sharhlar</h1>
</div>

<div class="order-tabs">
    <a href="?tab=pending" class="tab-btn <?= $tab == 'pending' ? 'active' : '' ?>">Kutilayotgan</a>
    <a href="?tab=all" class="tab-btn <?= $tab == 'all' ? 'active' : '' ?>">Barchasi</a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mahsulot</th>
                    <th>Foydalanuvchi</th>
                    <th>Reyting</th>
                    <th>Sharh</th>
                    <th>Holat</th>
                    <th>Sana</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td>#<?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['product_name']) ?></td>
                        <td><?= htmlspecialchars($r['user_name']) ?></td>
                        <td>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="color:<?= $i <= $r['rating'] ? 'var(--warning)' : 'var(--border)' ?>;font-size:13px;"></i>
                            <?php endfor; ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars($r['comment'] ?? '-')) ?></td>
                        <td>
                            <span class="status-badge status-<?= $r['status'] ?>">
                                <?= $r['status'] == 'approved' ? 'Tasdiqlangan' : ($r['status'] == 'pending' ? 'Kutilmoqda' : 'Rad etilgan') ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($r['created_at'])) ?></td>
                        <td style="white-space:nowrap">
                            <?php if ($r['status'] == 'pending'): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="approve" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                </form>
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                <button type="submit" name="delete" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="8" class="text-center">Sharhlar topilmadi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
