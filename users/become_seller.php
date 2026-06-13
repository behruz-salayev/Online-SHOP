<?php
/**
 * become_seller.php - Sotuvchi bo'lish sahifasi
 * 
 * Foydalanuvchi sotuvchi bo'lish uchun ariza topshiradi.
 * Ma'lumotlar admin tomonidan tekshiriladi va tasdiqlanadi.
 */

require_once __DIR__ . '/../config/config.php';

// Kirish talab qilinadi
User::requireLogin();

// Admin sotuvchi bo'la olmaydi
if (User::isAdmin()) {
    flash('error', 'Adminlar sotuvchi bo\'la olmaydi. Siz bozorni boshqarish uchun admin panelidan foydalaning.');
    redirect('admin/index.php');
}

// Agar allaqachon sotuvchi bo'lsa
if (User::isSeller()) {
    flash('success', 'Siz allaqachon sotuvchi hisobingizga ega bo\'lgansiz.');
    redirect('seller/index.php');
}

$userModel = new User();
$requestModel = new SellerRequest();
$regionModel = new Region();
$regions = $regionModel->getAll();
$request = $requestModel->getByUserId($_SESSION['user_id']);

$title = 'Sotuvchi bo\'lish';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Non-breadcrumbs -->
<div class="breadcrumbs">
    <a href="<?= SITE_URL ?>/index.php">Bosh sahifa</a> <span>/</span>
    <span>Sotuvchi bo'lish</span>
</div>

<h1 class="page-title"><i class="fas fa-store"></i> Sotuvchi bo'lish</h1>

<div class="section seller-application">
    <!-- Ariza holati -->
    <?php if ($request && $request['status'] === 'pending'): ?>
        <div class="alert alert-info">
            <i class="fas fa-clock"></i>
            Sizning arizangiz ko'rib chiqilmoqda.
        </div>
    <?php elseif ($request && $request['status'] === 'rejected'): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i>
            So'rovingiz rad etildi. Iltimos, ma'lumotlarni tekshirib qayta yuboring.
        </div>
    <?php endif; ?>

    <!-- Ariza formasi -->
    <div class="form-card">
        <h2><i class="fas fa-clipboard-list"></i> Sotuvchi arizasi</h2>
        <p>Sotuvchi bo'lish uchun quyidagi ma'lumotlarni to'ldiring. Ma'lumotlar admin tomonidan tasdiqlanadi.</p>

        <form method="POST" action="<?= SITE_URL ?>/includes/seller_request.inc.php" 
              enctype="multipart/form-data" class="form-grid">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label>Biznes nomi</label>
                <input type="text" name="business_name" required class="form-control" 
                       placeholder="Do'kon yoki brend nomi" 
                       value="<?= htmlspecialchars(old_flash('business_name')) ?>">
            </div>
            
            <div class="form-group">
                <label>Biznes tavsifi</label>
                <textarea name="business_description" required class="form-control" rows="4" 
                          placeholder="Qisqacha biznesingiz haqida ma'lumot"><?= htmlspecialchars(old_flash('business_description')) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Telefon raqam</label>
                <input type="text" name="phone" required class="form-control" 
                       placeholder="+998901234567" 
                       value="<?= htmlspecialchars(old_flash('phone')) ?>">
            </div>
            
            <div class="form-group">
                <label>Viloyat</label>
                <select name="region_id" id="sellerRegion" required class="form-control" 
                        onchange="loadSellerDistricts(this.value)">
                    <option value="">Viloyatni tanlang</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?= $region['id'] ?>" 
                                <?= (old_flash('region_id') == $region['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($region['name_uz']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Tuman</label>
                <select name="district_id" id="sellerDistrict" required class="form-control">
                    <option value="">Avval viloyatni tanlang</option>
                </select>
            </div>
            
            <div class="form-group full-width">
                <label>Manzil</label>
                <textarea name="address" required class="form-control" rows="3" 
                          placeholder="Ko'cha, uy, shahar"><?= htmlspecialchars(old_flash('address')) ?></textarea>
            </div>
            
            <div class="form-group full-width">
                <label>Logo yoki tasvir (ixtiyoriy)</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
            
            <div class="form-actions">
                <button type="submit" name="submit_seller_request" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Arizani yuborish
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript: Tumanlarni yuklash -->
<script>
function loadSellerDistricts(regionId) {
    const select = document.getElementById('sellerDistrict');
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
