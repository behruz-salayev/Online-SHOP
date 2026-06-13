<?php
/**
 * User - Foydalanuvchilar bilan ishlash modeli
 * 
 * Vazifalari:
 * - Ro'yxatdan o'tish va kirish
 * - Ruxsatlarni tekshirish (admin, sotuvchi, oddiy foydalanuvchi)
 * - Profilni tahrirlash
 * - Parolni o'zgartirish
 */

class User
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Yangi foydalanuvchini ro'yxatdan o'tkazish
     * 
     * @param string $name To'liq ism
     * @param string $email Email manzil
     * @param string $password Parol
     * @param string $phone Telefon raqam
     * @return array Natija (success va errors)
     */
    public function register(string $name, string $email, string $password, string $phone): array
    {
        // Ma'lumotlarni tekshirish
        $errors = [];
        if (empty($name)) $errors[] = 'Ism kiritilishi shart';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email noto\'g\'ri formatda';
        if (strlen($password) < 6) $errors[] = 'Parol kamida 6 belgidan iborat bo\'lishi kerak';
        if (empty($phone)) $errors[] = 'Telefon raqami kiritilishi shart';

        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        // Email bandligini tekshirish
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE email = ?",
            [$email], 's'
        );

        if ($existing) {
            return ['success' => false, 'errors' => ['Bu email allaqachon ro\'yxatdan o\'tgan']];
        }

        // Parolni xeshlash (bcrypt)
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Foydalanuvchini bazaga qo'shish
        $this->db->query(
            "INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)",
            [$name, $email, $phone, $hash], 'ssss'
        );

        // Avtomatik kirish
        $userId = $this->db->lastInsertId();
        $this->loginSession($userId, $name, 'user');

        return ['success' => true, 'user_id' => $userId];
    }

    /**
     * Foydalanuvchini tizimga kiritish
     * 
     * @param string $email Email
     * @param string $password Parol
     * @return array Natija (success, role, errors)
     */
    public function login(string $email, string $password): array
    {
        // Foydalanuvchini email bo'yicha qidirish
        $user = $this->db->fetchOne(
            "SELECT id, full_name, password, role FROM users WHERE email = ?",
            [$email], 's'
        );

        // Foydalanuvchi topilmadi yoki parol noto'g'ri
        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'errors' => ['Email yoki parol noto\'g\'ri']];
        }

        // Sessiyaga yozish
        $this->loginSession($user['id'], $user['full_name'], $user['role']);

        return ['success' => true, 'role' => $user['role']];
    }

    /**
     * Foydalanuvchi ma'lumotlarini sessiyaga saqlash
     */
    private function loginSession(int $userId, string $name, string $role): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = $role;
    }

    /**
     * Tizimdan chiqish - sessiyani tozalash
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    // ===== Ruxsat tekshirish funksiyalari =====

    /** Foydalanuvchi tizimga kirganmi? */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /** Foydalanuvchi adminmi? */
    public static function isAdmin(): bool
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /** Foydalanuvchi sotuvchimi? */
    public static function isSeller(): bool
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller';
    }

    /**
     * Kirish talab qilinadi
     * Agar foydalanuvchi kirmagan bo'lsa, login sahifasiga yo'naltiradi
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            $_SESSION['error'] = 'Iltimos, avval tizimga kiring';
            redirect('users/login.php');
        }
    }

    /**
     * Admin huquqi talab qilinadi
     */
    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            $_SESSION['error'] = 'Sizda bu sahifaga kirish huquqi yo\'q';
            redirect('index.php');
        }
    }

    /**
     * Sotuvchi huquqi talab qilinadi
     */
    public static function requireSeller(): void
    {
        if (!self::isSeller()) {
            $_SESSION['error'] = 'Sizda bu sahifaga kirish huquqi yo\'q';
            redirect('index.php');
        }
    }

    // ===== Profil boshqaruvi =====

    /**
     * Foydalanuvchini sotuvchi qilish (role ni o'zgartirish)
     */
    public function promoteToSeller(int $userId): void
    {
        $this->db->query(
            "UPDATE users SET role = 'seller' WHERE id = ?",
            [$userId], 'i'
        );
    }

    /**
     * Foydalanuvchini ID bo'yicha olish
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, full_name, email, phone, role, status, created_at FROM users WHERE id = ?",
            [$id], 'i'
        );
    }

    /**
     * Foydalanuvchining kutilayotgan sotuvchi arizasi borligini tekshirish
     */
    public function hasPendingSellerRequest(int $userId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT id FROM seller_requests WHERE user_id = ? AND status = 'pending'",
            [$userId], 'i'
        );
        return $row !== null;
    }

    /**
     * Profil ma'lumotlarini yangilash
     */
    public function updateProfile(int $id, string $name, string $phone): void
    {
        $this->db->query(
            "UPDATE users SET full_name = ?, phone = ? WHERE id = ?",
            [$name, $phone, $id], 'ssi'
        );
        $_SESSION['user_name'] = $name;
    }

    /**
     * Parolni o'zgartirish
     * 
     * @param int $id Foydalanuvchi ID si
     * @param string $oldPass Joriy parol
     * @param string $newPass Yangi parol
     * @return array Natija
     */
    public function updatePassword(int $id, string $oldPass, string $newPass): array
    {
        // Joriy parolni tekshirish
        $user = $this->db->fetchOne(
            "SELECT password FROM users WHERE id = ?", [$id], 'i'
        );
        
        if (!$user || !password_verify($oldPass, $user['password'])) {
            return ['success' => false, 'errors' => ['Joriy parol noto\'g\'ri']];
        }
        
        if (strlen($newPass) < 6) {
            return ['success' => false, 'errors' => ['Yangi parol kamida 6 belgi bo\'lishi kerak']];
        }

        // Yangi parolni saqlash
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $this->db->query(
            "UPDATE users SET password = ? WHERE id = ?", [$hash, $id], 'si'
        );
        
        return ['success' => true, 'errors' => []];
    }

    /**
     * Barcha foydalanuvchilarni olish (admin uchun)
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT id, full_name, email, phone, role, created_at, status
             FROM users ORDER BY created_at DESC"
        );
    }

    public function updateRole(int $id, string $role): void
    {
        $this->db->query(
            "UPDATE users SET role = ? WHERE id = ?",
            [$role, $id], 'si'
        );
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->db->query(
            "UPDATE users SET status = ? WHERE id = ?",
            [$status, $id], 'si'
        );
    }
}
