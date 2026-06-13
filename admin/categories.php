<?php
/**
 * admin/categories.php - Kategoriyalarni boshqarish (Admin)
 * 
 * Vazifalari:
 * - Yangi kategoriya yaratish
 * - Kategoriyani tahrirlash
 * - Kategoriyani o'chirish
 */

require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$categoryModel = new Category();

// POST so'rovni qayta ishlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_csrf();
    
    // Kategoriyani saqlash
    if (isset($_POST['save_category'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (!empty($name)) {
            if (!empty($_POST['edit_id'])) {
                $categoryModel->update((int)$_POST['edit_id'], $name, $description);
                $_SESSION['success'] = 'Kategoriya yangilandi!';
            } else {
                $categoryModel->create($name, $description);
                $_SESSION['success'] = 'Kategoriya yaratildi!';
            }
        }
        redirect('admin/categories.php');
    }

    // Kategoriyani o'chirish
    if (isset($_POST['delete_category'])) {
        $categoryModel->delete((int)$_POST['category_id']);
        $_SESSION['success'] = 'Kategoriya o\'chirildi';
        redirect('admin/categories.php');
    }
}

$categories = $categoryModel->getAll();
$title = 'Kategoriyalar';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-th-large"></i> Kategoriyalar</h1>
</div>

<!-- Yangi kategoriya qo'shish -->
<div class="admin-card">
    <h2><i class="fas fa-plus-circle"></i> Yangi kategoriya</h2>
    <form method="POST">
        <?= csrf_field() ?>
        <div class="form-row">
            <div class="form-group">
                <label>Kategoriya nomi</label>
                <input type="text" name="name" required class="form-control" placeholder="Kategoriya nomi">
            </div>
            <div class="form-group">
                <label>Tavsif</label>
                <input type="text" name="description" class="form-control" placeholder="Qisqacha tavsif">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
                <button type="submit" name="save_category" class="btn btn-primary">Qo'shish</button>
            </div>
        </div>
    </form>
</div>

<!-- Kategoriyalar ro'yxati -->
<div class="admin-card">
    <h2><i class="fas fa-list"></i> Barcha kategoriyalar</h2>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nomi</th>
                    <th>Slug</th>
                    <th>Tavsif</th>
                    <th>Mahsulotlar</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td>#<?= $cat['id'] ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars($cat['slug'] ?? '') ?></td>
                        <td><?= htmlspecialchars($cat['description'] ?? '') ?></td>
                        <td><?= $cat['product_count'] ?></td>
                        <td>
                            <form method="POST" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
