<?php
require_once __DIR__ . '/../config/config.php';
User::requireAdmin();

$userModel = new User();

$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ensure_csrf();

    if (isset($_POST['change_role'])) {
        $userId = (int)$_POST['user_id'];
        $newRole = $_POST['role'];
        if (in_array($newRole, ['user', 'seller', 'admin'])) {
            $userModel->updateRole($userId, $newRole);
            $_SESSION['success'] = 'Foydalanuvchi roli o\'zgartirildi.';
        }
        redirect('admin/users.php');
    }

    if (isset($_POST['toggle_ban'])) {
        $userId = (int)$_POST['user_id'];
        $user = $userModel->getById($userId);
        if ($user) {
            $newStatus = $user['status'] === 'banned' ? 'active' : 'banned';
            $userModel->updateStatus($userId, $newStatus);
            $_SESSION['success'] = 'Foydalanuvchi holati o\'zgartirildi.';
        }
        redirect('admin/users.php');
    }
}

$users = $userModel->getAll();
if (!empty($search)) {
    $users = array_filter($users, fn($u) =>
        stripos($u['full_name'], $search) !== false ||
        stripos($u['email'], $search) !== false ||
        stripos($u['phone'], $search) !== false
    );
}

$title = 'Foydalanuvchilar';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-header">
    <h1><i class="fas fa-users"></i> Foydalanuvchilar</h1>
</div>

<div class="admin-card">
    <form method="GET" style="margin-bottom:16px">
        <div class="form-row">
            <div class="form-group" style="flex:1">
                <input type="text" name="search" class="form-control" placeholder="Ism, email yoki telefon bo'yicha qidirish..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group" style="display:flex;align-items:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Qidirish</button>
                <?php if ($search): ?>
                    <a href="users.php" class="btn btn-outline" style="margin-left:6px"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ism</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Roli</th>
                    <th>Holati</th>
                    <th>Ro'yxatdan o'tgan</th>
                    <th>Harakat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td>
                            <span class="status-badge <?= $u['role'] == 'admin' ? 'status-active' : ($u['role'] == 'seller' ? 'status-pending' : '') ?>">
                                <?= $u['role'] == 'admin' ? 'Admin' : ($u['role'] == 'seller' ? 'Sotuvchi' : 'Foydalanuvchi') ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= ($u['status'] ?? 'active') == 'banned' ? 'status-expired' : 'status-active' ?>">
                                <?= ($u['status'] ?? 'active') == 'banned' ? 'Bloklangan' : 'Faol' ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                        <td style="white-space:nowrap">
                            <button class="btn btn-sm btn-primary" onclick="toggleUserEdit(<?= $u['id'] ?>)"><i class="fas fa-edit"></i></button>
                            <form method="POST" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" name="toggle_ban" class="btn btn-sm <?= ($u['status'] ?? 'active') == 'banned' ? 'btn-success' : 'btn-danger' ?>"
                                    onclick="return confirm('Ishonchingiz komilmi?')">
                                    <i class="fas <?= ($u['status'] ?? 'active') == 'banned' ? 'fa-unlock' : 'fa-ban' ?>"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <tr class="order-details-row" id="user-edit-<?= $u['id'] ?>" style="display:none;">
                        <td colspan="8">
                            <div class="order-details-panel">
                                <h4><i class="fas fa-user-cog"></i> Rolni o'zgartirish: <?= htmlspecialchars($u['full_name']) ?></h4>
                                <form method="POST" style="display:flex;gap:8px;align-items:center;margin-top:8px;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <select name="role" class="form-control" style="width:auto;min-width:160px;">
                                        <option value="user" <?= $u['role'] == 'user' ? 'selected' : '' ?>>Foydalanuvchi</option>
                                        <option value="seller" <?= $u['role'] == 'seller' ? 'selected' : '' ?>>Sotuvchi</option>
                                        <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="change_role" class="btn btn-primary btn-sm">Saqlash</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-center">Foydalanuvchilar topilmadi</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleUserEdit(id) {
    const row = document.getElementById('user-edit-' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
