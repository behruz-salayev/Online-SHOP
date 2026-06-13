<?php
/**
 * seller/profile.php - Sotuvchi profil sozlamalari
 * 
 * Vazifalari:
 * - Biznes ma'lumotlarini tahrirlash
 * - Logo yuklash
 * - Manzil ma'lumotlari
 */

require_once __DIR__ . '/../config/config.php';
User::requireLogin();
User::requireSeller();

$sellerModel = new Seller();
$regionModel = new Region();
$seller = $sellerModel->getByUserId($_SESSION['user_id']);

if (!$seller) {
    flash('error', 'Sotuvchi profili topilmadi.');
    redirect('index.php');
}

$regions = $regionModel->getAll();

// POST so'rovni qayta ishlash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    ensure_csrf();

    $businessName = trim($_POST['business_name'] ?? '');
    $businessDescription = trim($_POST['business_description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $regionId = (int)($_POST['region_id'] ?? 0);
    $districtId = (int)($_POST['district_id'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $logo = $seller['logo'];

    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $logo = uniqid('seller_logo_') . '.' . $ext;
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }
            move_uploaded_file($_FILES['logo']['tmp_name'], UPLOAD_DIR . $logo);
        }
    }

    $sellerModel->update($seller['id'], [
        'business_name' => $businessName,
        'business_description' => $businessDescription,
        'phone' => $phone,
        'region_id' => $regionId,
        'district_id' => $districtId,
        'address' => $address,
        'logo' => $logo,
    ]);

    flash('success', 'Sotuvchi profili yangilandi.');
    redirect('seller/profile.php');
}

$title = 'Profil';
require_once __DIR__ . '/../includes/seller_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-user-cog"></i> Sotuvchi profili</h1>
</div>

<div class="admin-card">
    <form method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="form-group">
                <label>Biznes nomi</label>
                <input type="text" name="business_name" required class="form-control"
                       value="<?= htmlspecialchars($seller['business_name']) ?>">
            </div>
            <div class="form-group">
                <label>Telefon raqam</label>
                <input type="text" name="phone" required class="form-control"
                       value="<?= htmlspecialchars($seller['phone']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label>Biznes tavsifi</label>
            <textarea name="business_description" required class="form-control" rows="4"><?= htmlspecialchars($seller['business_description']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Viloyat</label>
                <select name="region_id" id="profileRegion" required class="form-control"
                        onchange="loadDistricts(this.value)">
                    <option value="">Viloyatni tanlang</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= $region['id'] ?>" <?= $seller['region_id'] == $region['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($region['name_uz']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tuman</label>
                <select name="district_id" id="profileDistrict" required class="form-control">
                    <option value=""><?= htmlspecialchars($seller['district_name'] ?? 'Tumanni tanlang') ?></option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Manzil</label>
            <textarea name="address" required class="form-control" rows="3"><?= htmlspecialchars($seller['address']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Logo</label>
            <input type="file" name="logo" class="form-control" accept="image/*">
            <?php if ($seller['logo']): ?>
                <div style="margin-top:8px">
                    <img src="<?= SITE_URL ?>/links/images/<?= htmlspecialchars($seller['logo']) ?>"
                         style="max-height:100px;border-radius:8px;">
                </div>
            <?php endif; ?>
        </div>

        <button type="submit" name="update_profile" class="btn btn-primary">
            <i class="fas fa-save"></i> Saqlash
        </button>
    </form>
</div>

<script>
function loadDistricts(regionId) {
    const select = document.getElementById('profileDistrict');
    select.innerHTML = '<option value="">Yuklanmoqda...</option>';
    fetch('<?= SITE_URL ?>/get_districts.php?region_id=' + regionId)
        .then(res => res.json())
        .then(data => {
            let html = '<option value="">Tumanni tanlang</option>';
            data.forEach(d => {
                html += '<option value="' + d.id + '">' + d.name_uz + '</option>';
            });
            select.innerHTML = html;
        });
}
window.addEventListener('load', function() {
    const regionSelect = document.getElementById('profileRegion');
    if (regionSelect.value) {
        loadDistricts(regionSelect.value);
    }
});
</script>

<?php require_once __DIR__ . '/../includes/seller_footer.php'; ?>
