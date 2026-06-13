<?php
require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$settingsModel = new Settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    ensure_csrf();
    $settingsModel->update([
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_description' => trim($_POST['site_description'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'currency' => trim($_POST['currency'] ?? 'so\'m'),
        'delivery_fee' => trim($_POST['delivery_fee'] ?? '0'),
        'free_delivery_min' => trim($_POST['free_delivery_min'] ?? '0'),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
    ]);
    $_SESSION['success'] = 'Sozlamalar saqlandi!';
    redirect('admin/settings.php');
}

$settings = $settingsModel->getAll();
$title = 'Sozlamalar';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-cog"></i> Sayt sozlamalari</h1>
</div>

<div class="admin-card">
    <form method="POST">
        <?= csrf_field() ?>
        
        <h3><i class="fas fa-globe"></i> Umumiy sozlamalar</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Sayt nomi</label>
                <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Valyuta</label>
                <input type="text" name="currency" class="form-control" value="<?= htmlspecialchars($settings['currency'] ?? 'so\'m') ?>">
            </div>
            <div class="form-group full-width">
                <label>Sayt tavsifi</label>
                <textarea name="site_description" class="form-control" rows="2"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
            </div>
        </div>

        <h3 style="margin-top:20px;"><i class="fas fa-address-card"></i> Kontakt ma'lumotlar</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
            </div>
        </div>

        <h3 style="margin-top:20px;"><i class="fas fa-truck"></i> Yetkazib berish</h3>
        <div class="form-grid">
            <div class="form-group">
                <label>Yetkazib berish narxi (so'm)</label>
                <input type="number" name="delivery_fee" class="form-control" value="<?= htmlspecialchars($settings['delivery_fee'] ?? '15000') ?>">
            </div>
            <div class="form-group">
                <label>Bepul yetkazib berish (min. summa)</label>
                <input type="number" name="free_delivery_min" class="form-control" value="<?= htmlspecialchars($settings['free_delivery_min'] ?? '500000') ?>">
            </div>
        </div>

        <h3 style="margin-top:20px;"><i class="fas fa-shield-alt"></i> Xavfsizlik</h3>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?>>
                <span>Texnik ish rejimi (sayt vaqtincha yopiq)</span>
            </label>
        </div>

        <div style="margin-top:20px;">
            <button type="submit" name="save_settings" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Sozlamalarni saqlash
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
