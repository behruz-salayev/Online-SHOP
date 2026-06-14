<?php
/**
 * seller/add_product.php - Mahsulot qo'shish/tahrirlash (Sotuvchi)
 * 
 * Vazifalari:
 * - Yangi mahsulot yaratish
 * - Mavjud mahsulotni tahrirlash
 * - Rasm yuklash
 * - Xususiyatlar qo'shish
 */

require_once __DIR__ . '/../config/config.php';
User::requireLogin();
User::requireSeller();

$productModel = new Product();
$categoryModel = new Category();
$sellerUserId = $_SESSION['user_id'];

$editProduct = null;
$editId = (int)($_GET['edit'] ?? 0);
if ($editId) {
    $editProduct = $productModel->getBySeller($sellerUserId, $editId);
    if (!$editProduct) {
        flash('error', 'Mahsulot topilmadi yoki sizga tegishli emas.');
        redirect('seller/products.php');
    }
}

// POST so'rovni qayta ishlash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    ensure_csrf();

    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $oldPrice = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $specs = [];
    if (!empty($_POST['spec_key'])) {
        foreach ($_POST['spec_key'] as $index => $key) {
            $key = trim($key);
            $value = trim($_POST['spec_value'][$index] ?? '');
            if ($key !== '' && $value !== '') {
                $specs[$key] = $value;
            }
        }
    }
    $specsJson = !empty($specs) ? json_encode($specs, JSON_UNESCAPED_UNICODE) : null;

    $image = $editProduct['image'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $image = uniqid('product_') . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $image);
        }
    }

    $data = [
        'category_id' => $categoryId,
        'seller_id' => $sellerUserId,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'old_price' => $oldPrice,
        'stock' => $stock,
        'image' => $image,
        'specs' => $specsJson,
        'is_featured' => 0,
        'status' => 'active',
        'approval_status' => 'approved',
    ];

    if ($editProduct) {
        $productModel->update($editProduct['id'], $data);
        flash('success', 'Mahsulotingiz muvaffaqiyatli saqlandi.');
    } else {
        $productModel->create($data);
        flash('success', 'Mahsulot muvaffaqiyatli yaratildi va bozorga qo\'yildi.');
    }
    redirect('seller/products.php');
}

$categories = $categoryModel->getAll();
$title = $editProduct ? 'Mahsulotni tahrirlash' : 'Mahsulot qo\'shish';
require_once __DIR__ . '/../includes/seller_header.php';
?>

<div class="admin-header">
    <h1><i class="fas <?= $editProduct ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
        <?= $editProduct ? 'Mahsulotni tahrirlash' : 'Yangi mahsulot qo\'shish' ?></h1>
</div>

<div class="admin-card">
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="form-group">
                <label>Kategoriya</label>
                <select name="category_id" required class="form-control">
                    <option value="">Kategoriyani tanlang</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"
                            <?= $editProduct && $editProduct['category_id'] == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Mahsulot nomi</label>
                <input type="text" name="name" required class="form-control"
                       value="<?= $editProduct ? htmlspecialchars($editProduct['name']) : '' ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Narx (so'm)</label>
                <input type="number" name="price" required class="form-control" step="0.01" min="0"
                       value="<?= $editProduct ? $editProduct['price'] : '' ?>">
            </div>
            <div class="form-group">
                <label>Eski narx (chegirma bo'lsa)</label>
                <input type="number" name="old_price" class="form-control" step="0.01" min="0"
                       value="<?= $editProduct && $editProduct['old_price'] ? $editProduct['old_price'] : '' ?>">
            </div>
            <div class="form-group">
                <label>Soni (stock)</label>
                <input type="number" name="stock" required class="form-control" min="0"
                       value="<?= $editProduct ? $editProduct['stock'] : '0' ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Mahsulot tavsifi</label>
            <textarea name="description" class="form-control" rows="4"><?= $editProduct ? htmlspecialchars($editProduct['description']) : '' ?></textarea>
        </div>

        <div class="form-group">
            <label>Rasm</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <?php if ($editProduct && $editProduct['image']): ?>
                <div style="margin-top:8px">
                    <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($editProduct['image']) ?>"
                         style="max-height:100px;border-radius:8px;">
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label>Xususiyatlar (ixtiyoriy)</label>
            <div id="specsContainer">
                <?php
                $existingSpecs = $editProduct && !empty($editProduct['specs']) ? json_decode($editProduct['specs'], true) : [];
                if (!empty($existingSpecs) && is_array($existingSpecs)):
                    foreach ($existingSpecs as $key => $value):
                ?>
                    <div class="form-row spec-row">
                        <input type="text" name="spec_key[]" class="form-control" placeholder="Xususiyat nomi"
                               value="<?= htmlspecialchars($key) ?>">
                        <input type="text" name="spec_value[]" class="form-control" placeholder="Qiymat"
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
                <i class="fas fa-plus"></i> Xususiyat qo'shish
            </button>
        </div>

        <button type="submit" name="save_product" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $editProduct ? 'Yangilash' : 'Yaratish' ?>
        </button>
        <?php if ($editProduct): ?>
            <a href="<?= SITE_URL ?>/seller/products.php" class="btn btn-outline">Bekor qilish</a>
        <?php endif; ?>
    </form>
</div>

<script>
function addSpec() {
    const container = document.getElementById('specsContainer');
    const row = document.createElement('div');
    row.className = 'form-row spec-row';
    row.innerHTML = `
        <input type="text" name="spec_key[]" class="form-control" placeholder="Xususiyat nomi">
        <input type="text" name="spec_value[]" class="form-control" placeholder="Qiymat">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(row);
}
</script>

<?php require_once __DIR__ . '/../includes/seller_footer.php'; ?>
