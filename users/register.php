<?php
/**
 * register.php - Ro'yxatdan o'tish sahifasi
 * 
 * Yangi foydalanuvchi ro'yxatdan o'tadi.
 * Agar allaqachon kirgan bo'lsa, bosh sahifaga yo'naltiradi.
 */

require_once __DIR__ . '/../config/config.php';

// Agar allaqachon kirgan bo'lsa
if (User::isLoggedIn()) {
    redirect('index.php');
}

$title = 'Ro\'yxatdan o\'tish';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <i class="fas fa-user-plus"></i>
            <h2>Ro'yxatdan o'tish</h2>
            <p>Yangi hisob yarating va xarid qilishni boshlang</p>
        </div>

        <!-- Xatoliklar -->
        <?php
        $errors = flash('register_errors');
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

        <!-- Ro'yxatdan o'tish formasi -->
        <form method="POST" action="<?= SITE_URL ?>/includes/signup.inc.php" class="auth-form">
            <?= csrf_field() ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> To'liq ismingiz</label>
                    <input type="text" name="full_name" required class="form-control" 
                           placeholder="Ism Familyangiz" 
                           value="<?= htmlspecialchars(old_flash('full_name')) ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Telefon raqam</label>
                    <input type="text" name="phone" required class="form-control" 
                           placeholder="+998901234567" 
                           value="<?= htmlspecialchars(old_flash('phone')) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" required class="form-control" 
                           placeholder="email@example.com" 
                           value="<?= htmlspecialchars(old_flash('email')) ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Parol <small>(kamida 6 belgi)</small></label>
                    <input type="password" name="password" required class="form-control" 
                           minlength="6" placeholder="Parolni kiriting">
                </div>
            </div>
            
            <button type="submit" name="signup" class="btn btn-primary btn-block btn-auth">
                Ro'yxatdan o'tish
            </button>
        </form>

        <div class="auth-footer">
            <p>Hisobingiz bormi? <a href="<?= SITE_URL ?>/users/login.php">Tizimga kirish</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
