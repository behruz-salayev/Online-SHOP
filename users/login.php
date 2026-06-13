<?php
/**
 * login.php - Tizimga kirish sahifasi
 * 
 * Foydalanuvchi email va parol orqali tizimga kiradi.
 * Agar allaqachon kirgan bo'lsa, bosh sahifaga yo'naltiradi.
 */

require_once __DIR__ . '/../config/config.php';

// Agar allaqachon kirgan bo'lsa
if (User::isLoggedIn()) {
    redirect('index.php');
}

$title = 'Kirish';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-sign-in-alt"></i>
            <h2>Tizimga kirish</h2>
            <p>Hisobingizga kiring va xarid qilishni boshlang</p>
        </div>

        <!-- Xatoliklar -->
        <?php
        $errors = flash('login_errors');
        if ($errors):
        ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Kirish formasi -->
        <form method="POST" action="<?= SITE_URL ?>/includes/login.inc.php" class="auth-form">
            <?= csrf_field() ?>
            
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" required class="form-control" 
                       placeholder="email@example.com" 
                       value="<?= htmlspecialchars(old_flash('email')) ?>">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Parol</label>
                <input type="password" name="password" required class="form-control" 
                       placeholder="Parolingizni kiriting">
            </div>
            
            <button type="submit" name="login" class="btn btn-primary btn-block btn-auth">
                Kirish
            </button>
        </form>

        <div class="auth-footer">
            <p>Hisobingiz yo'qmi? <a href="<?= SITE_URL ?>/users/register.php">Ro'yxatdan o'tish</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
