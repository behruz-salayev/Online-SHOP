<?php
/**
 * admin/products.php - Mahsulotlarni boshqarish (Admin)
 * 
 * Vazifalari:
 * - Mahsulotlarni ko'rish, qidirish va kategoriya bo'yicha filtrlash
 * - Mahsulotni tahrirlash
 * - Mahsulotni o'chirish
 * - Tasdiqlash holatini o'zgartirish
 */

require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$productModel = new Product();
$categoryModel = new Category();
$categories = $categoryModel->getAll();
$sellerModel = new Seller();
$sellers = $sellerModel->getAll();

// Qidirish va filtr parametrlari
$search = trim($_GET['search'] ?? '');
$categoryFilter = (int)($_GET['category_id'] ?? 0);

// Tahrirlash uchun mahsulotni olish
$editProduct = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $editProduct = $productModel->getById($editId, true);
}

// POST so'rovni qayta ishlash (faqat tahrirlash va o'chirish)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_csrf();
    
    // Mahsulotni tahrirlash (yangi yaratish YO'Q)
    if (isset($_POST['save_product'])) {
        if (!$editProduct) {
            redirect('admin/products.php');
        }
        
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $oldPrice = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
        $stock = (int)($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $status = $_POST['status'] ?? 'active';

        // Xususiyatlarni yig'ish
        $specs = [];
        if (!empty($_POST['spec_key'])) {
            foreach ($_POST['spec_key'] as $i => $key) {
                if (!empty($key) && isset($_POST['spec_value'][$i])) {
                    $specs[$key] = $_POST['spec_value'][$i];
                }
            }
        }
        $specsJson = !empty($specs) ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null;

        // Rasmni saqlash
        $image = $editProduct['image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $imageName = uniqid('product_') . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $imageName);
                $image = $imageName;
            }
        }

        $sellerId = !empty($_POST['seller_id']) ? (int)$_POST['seller_id'] : null;
        $approvalStatus = $_POST['approval_status'] ?? ($editProduct['approval_status'] ?? 'pending');

        $data = [
            'category_id' => $categoryId,
            'seller_id' => $sellerId,
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'old_price' => $oldPrice,
            'stock' => $stock,
            'image' => $image,
            'specs' => $specsJson,
            'is_featured' => $isFeatured,
            'status' => $status,
            'approval_status' => $approvalStatus,
        ];

        $productModel->update($editProduct['id'], $data);
        $_SESSION['success'] = 'Mahsulot yangilandi!';
        redirect('admin/products.php');
    }

    // Mahsulotni o'chirish
    if (isset($_POST['delete_product'])) {
        $id = (int)($_POST['product_id'] ?? 0);
        $productModel->delete($id);
        $_SESSION['success'] = 'Mahsulot o\'chirildi';
        redirect('admin/products.php');
    }
}

// Mahsulotlarni olish (filtrlash bilan)
$products = $productModel->getAllAdmin($search, '', '', $categoryFilter);
$title = 'Mahsulotlar';

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-box"></i> Mahsulotlar</h1>
</div>

<!-- Qidirish va filtr -->
<div class="admin-card">
    <form method="GET" class="form-row" style="gap:12px;align-items:end;">
        <div class="form-group" style="flex:1;">
            <label><i class="fas fa-search"></i> Qidirish</label>
            <input type="text" name="search" class="form-control" 
                   placeholder="Mahsulot nomi bo'yicha qidirish..." 
                   value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="form-group" style="flex:0 0 250px;">
            <label><i class="fas fa-th-large"></i> Kategoriya</label>
            <select name="category_id" class="form-control">
                <option value="">Barcha kategoriyalar</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryFilter === $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex:0 0 auto;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Qidirish
            </button>
            <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn-outline">
                <i class="fas fa-undo"></i>
            </a>
        </div>
    </form>
</div>

<?php if ($editProduct): ?>
<!-- Mahsulotni tahrirlash formasi -->
<div class="admin-card">
    <h2><i class="fas fa-edit"></i> Mahsulotni tahrirlash</h2>
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        
        <div class="form-row">
            <div class="form-group">
                <label>Kategoriya</label>
                <select name="category_id" required class="form-control">
                    <option value="">Kategoriyani tanlang</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" 
                            <?= ($editProduct['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Mahsulot nomi</label>
                <input type="text" name="name" required class="form-control" 
                       value="<?= htmlspecialchars($editProduct['name']) ?>">
            </div>
            <div class="form-group">
                <label>Sotuvchi</label>
                <select name="seller_id" class="form-control">
                    <option value="">Platforma mahsuloti</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?= $seller['id'] ?>" 
                            <?= ($editProduct['seller_id'] == $seller['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($seller['business_name']) ?> (<?= htmlspecialchars($seller['owner_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Narxi (so'm)</label>
                <input type="number" name="price" required class="form-control" step="0.01" min="0" 
                       value="<?= $editProduct['price'] ?>">
            </div>
            <div class="form-group">
                <label>Eski narx (agar chegirma bo'lsa)</label>
                <input type="number" name="old_price" class="form-control" step="0.01" min="0" 
                       value="<?= $editProduct['old_price'] ? $editProduct['old_price'] : '' ?>">
            </div>
            <div class="form-group">
                <label>Soni (stock)</label>
                <input type="number" name="stock" class="form-control" min="0" 
                       value="<?= $editProduct['stock'] ?>">
            </div>
            <div class="form-group">
                <label>Tasdiqlash holati</label>
                <select name="approval_status" class="form-control">
                    <option value="pending" <?= ($editProduct['approval_status'] === 'pending') ? 'selected' : '' ?>>Kutilmoqda</option>
                    <option value="approved" <?= ($editProduct['approval_status'] === 'approved') ? 'selected' : '' ?>>Tasdiqlangan</option>
                    <option value="rejected" <?= ($editProduct['approval_status'] === 'rejected') ? 'selected' : '' ?>>Rad etilgan</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Mahsulot haqida (tavsifi)</label>
            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($editProduct['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Rasm</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <?php if ($editProduct['image']): ?>
                <div style="margin-top:8px">
                    <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($editProduct['image']) ?>" 
                         style="max-height:80px;border-radius:8px;">
                </div>
            <?php endif; ?>
        </div>

        <!-- Xususiyatlar -->
        <div class="form-group">
            <label>Xususiyatlari (ixtiyoriy)</label>
            <div id="specsContainer">
                <?php
                $existingSpecs = json_decode($editProduct['specs'], true);
                if (!empty($existingSpecs)):
                    foreach ($existingSpecs as $key => $value):
                ?>
                    <div class="form-row spec-row">
                        <input type="text" name="spec_key[]" class="form-control" placeholder="Xususiyat nomi" 
                               value="<?= htmlspecialchars($key) ?>">
                        <input type="text" name="spec_value[]" class="form-control" placeholder="Qiymati" 
                               value="<?= htmlspecialchars($value) ?>">
                        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <div class="form-row spec-row">
                        <input type="text" name="spec_key[]" class="form-control" placeholder="Masalan: Rang">
                        <input type="text" name="spec_value[]" class="form-control" placeholder="Masalan: Qora">
                        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" class="btn btn-sm btn-outline" onclick="addSpec()" style="margin-top:8px">
                <i class="fas fa-plus"></i> Qo'shimcha xususiyat
            </button>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_featured" <?= $editProduct['is_featured'] ? 'checked' : '' ?>>
                    Mashhur mahsulot
                </label>
            </div>
            <div class="form-group">
                <label>Holati</label>
                <select name="status" class="form-control">
                    <option value="active" <?= ($editProduct['status'] == 'active') ? 'selected' : '' ?>>Faol</option>
                    <option value="inactive" <?= ($editProduct['status'] == 'inactive') ? 'selected' : '' ?>>Faol emas</option>
                </select>
            </div>
        </div>

        <button type="submit" name="save_product" class="btn btn-primary">
            <i class="fas fa-save"></i> Yangilash
        </button>
        <a href="<?= SITE_URL ?>/admin/products.php" class="btn btn-outline">Bekor qilish</a>
    </form>
</div>
<?php endif; ?>

<!-- Mahsulotlar ro'yxati -->
<div class="admin-card">
    <h2><i class="fas fa-list"></i> Barcha mahsulotlar</h2>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Rasm</th>
                    <th>Nomi</th>
                    <th>Kategoriya</th>
                    <th>Sotuvchi</th>
                    <th>Narx</th>
                    <th>Soni</th>
                    <th>Holat</th>
                    <th>Tasdiq</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td>
                            <?php if ($p['image']): ?>
                                <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($p['image']) ?>" 
                                     style="width:50px;height:50px;object-fit:cover;border-radius:8px;">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($p['name']) ?>
                            <?php if ($p['is_featured']): ?><span class="badge-featured">&#9733;</span><?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['seller_name'] ?? 'Admin') ?></td>
                        <td><?= formatPrice($p['price']) ?></td>
                        <td>
                            <span class="stock-badge <?= $p['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <?= $p['stock'] ?>
                            </span>
                        </td>
                        <td><span class="status-badge status-<?= $p['status'] ?>"><?= $p['status'] == 'active' ? 'Faol' : 'Faol emas' ?></span></td>
                        <td><span class="status-badge status-<?= htmlspecialchars($p['approval_status']) ?>"><?= htmlspecialchars($p['approval_status']) ?></span></td>
                        <td>
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('O\'chirilsinmi?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" name="delete_product" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JavaScript: Xususiyat qo'shish -->
<script>
function addSpec() {
    const container = document.getElementById('specsContainer');
    const row = document.createElement('div');
    row.className = 'form-row spec-row';
    row.innerHTML = `
        <input type="text" name="spec_key[]" class="form-control" placeholder="Xususiyat nomi">
        <input type="text" name="spec_value[]" class="form-control" placeholder="Qiymati">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(row);
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
