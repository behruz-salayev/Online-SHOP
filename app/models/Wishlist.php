<?php
/**
 * Wishlist - Sevimlilar (istaklar ro'yxati) bilan ishlash modeli
 * 
 * Foydalanuvchi o'ziga yoqqan mahsulotlarni sevimlilarga qo'shishi mumkin.
 * Ma'lumotlar bazasida saqlanadi va faqat tizimga kirgan foydalanuvchilar uchun mavjud.
 */

class Wishlist
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Sevimlilarga qo'shish yoki olib tashlash (toggle)
     * Agar mahsulot sevimlilarda bo'lsa - olib tashlaydi
     * Agar mahsulot sevimlilarda bo'lmasa - qo'shadi
     * 
     * @param int $productId Mahsulot ID si
     * @return array Natija (success, action, message)
     */
    public function toggle(int $productId): array
    {
        // Faqat tizimga kirganlar uchun
        if (!User::isLoggedIn()) {
            return ['success' => false, 'message' => 'Iltimos, avval tizimga kiring'];
        }

        $userId = $_SESSION['user_id'];
        
        // Mahsulot sevimlilarda bormi?
        $existing = $this->db->fetchOne(
            "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
            [$userId, $productId], 'ii'
        );

        if ($existing) {
            // Bor - o'chiramiz
            $this->db->query(
                "DELETE FROM wishlist WHERE id = ?", 
                [$existing['id']], 'i'
            );
            return ['success' => true, 'action' => 'removed'];
        }

        // Yo'q - qo'shamiz
        $this->db->query(
            "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)",
            [$userId, $productId], 'ii'
        );
        
        return ['success' => true, 'action' => 'added'];
    }

    /**
     * Mahsulot sevimlilarda borligini tekshirish
     * 
     * @param int $productId Mahsulot ID si
     * @return bool
     */
    public function isInWishlist(int $productId): bool
    {
        if (!User::isLoggedIn()) return false;

        $row = $this->db->fetchOne(
            "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
            [$_SESSION['user_id'], $productId], 'ii'
        );
        
        return $row !== null;
    }

    /**
     * Foydalanuvchining barcha sevimli mahsulotlarini olish
     * 
     * @return array Mahsulotlar massivi
     */
    public function getUserWishlist(): array
    {
        if (!User::isLoggedIn()) return [];

        return $this->db->fetchAll(
            "SELECT w.product_id, p.*, c.name as category_name
             FROM wishlist w
             JOIN products p ON w.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE w.user_id = ?
             ORDER BY w.created_at DESC",
            [$_SESSION['user_id']], 'i'
        );
    }

    /**
     * Sevimlilar sonini olish (badge uchun)
     * 
     * @return int
     */
    public function getCount(): int
    {
        if (!User::isLoggedIn()) return 0;

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?",
            [$_SESSION['user_id']], 'i'
        );
        
        return (int)($row['count'] ?? 0);
    }

    /**
     * Mahsulotni sevimlilardan olib tashlash
     */
    public function remove(int $productId): void
    {
        if (!User::isLoggedIn()) return;
        
        $this->db->query(
            "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
            [$_SESSION['user_id'], $productId], 'ii'
        );
    }
}
