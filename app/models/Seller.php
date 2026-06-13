<?php
/**
 * Seller - Sotuvchilar profili bilan ishlash modeli
 * 
 * Sotuvchi arizasi tasdiqlangandan so'ng,
 * seller jadvaliga yoziladi va foydalanuvchi role i 'seller' qilib o'zgartiriladi.
 */

class Seller
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Yangi sotuvchi profili yaratish
     * 
     * @param array $data Sotuvchi ma'lumotlari
     * @return int Yangi sotuvchi ID si
     */
    public function create(array $data): int
    {
        $this->db->query(
            "INSERT INTO sellers (user_id, business_name, business_description, phone, 
                                 region_id, district_id, address, logo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'],
                $data['business_name'],
                $data['business_description'],
                $data['phone'],
                $data['region_id'],
                $data['district_id'],
                $data['address'],
                $data['logo'] ?? null,
            ],
            'isssiiss'
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Sotuvchi profilini tahrirlash
     */
    public function update(int $id, array $data): void
    {
        $this->db->query(
            "UPDATE sellers SET 
                business_name = ?, business_description = ?, phone = ?, 
                region_id = ?, district_id = ?, address = ?, logo = ? 
             WHERE id = ?",
            [
                $data['business_name'],
                $data['business_description'],
                $data['phone'],
                $data['region_id'],
                $data['district_id'],
                $data['address'],
                $data['logo'] ?? null,
                $id,
            ],
            'sssiiisi'
        );
    }

    /**
     * Foydalanuvchi ID si bo'yicha sotuvchini olish
     * 
     * @param int $userId Foydalanuvchi ID si
     * @return array|null Sotuvchi profili yoki null
     */
    public function getByUserId(int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT s.*, u.full_name as owner_name, u.email as owner_email, 
                    u.phone as owner_phone, r.name_uz as region_name, 
                    d.name_uz as district_name
             FROM sellers s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN regions r ON s.region_id = r.id
             LEFT JOIN districts d ON s.district_id = d.id
             WHERE s.user_id = ?",
            [$userId], 'i'
        );
    }

    /**
     * Sotuvchi ID si bo'yicha olish
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT s.*, u.full_name as owner_name, u.email as owner_email, 
                    u.phone as owner_phone, r.name_uz as region_name, 
                    d.name_uz as district_name
             FROM sellers s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN regions r ON s.region_id = r.id
             LEFT JOIN districts d ON s.district_id = d.id
             WHERE s.id = ?",
            [$id], 'i'
        );
    }

    /**
     * Barcha sotuvchilarni olish
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT s.*, u.full_name as owner_name, u.email as owner_email,
                    r.name_uz as region_name, d.name_uz as district_name
             FROM sellers s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN regions r ON s.region_id = r.id
             LEFT JOIN districts d ON s.district_id = d.id
             ORDER BY s.created_at DESC"
        );
    }

    /**
     * Sotuvchining faol mahsulotlar sonini hisoblash
     * 
     * @param int $sellerId Sotuvchi ID si
     * @return int Mahsulotlar soni
     */
    public function countProducts(int $sellerId): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products 
             WHERE seller_id = ? AND status = 'active'",
            [$sellerId], 'i'
        );
        return (int)($row['count'] ?? 0);
    }
}
