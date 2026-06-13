<?php
/**
 * SellerRequest - Sotuvchi arizalari bilan ishlash modeli
 * 
 * Foydalanuvchilar sotuvchi bo'lish uchun ariza topshiradi.
 * Admin arizani ko'rib chiqadi va tasdiqlaydi yoki rad etadi.
 * Tasdiqlangandan so'ng seller jadvaliga yoziladi.
 */

class SellerRequest
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Yangi sotuvchi arizasi yaratish
     * 
     * @param array $data Ariza ma'lumotlari
     * @return int Ariza ID si
     */
    public function create(array $data): int
    {
        $this->db->query(
            "INSERT INTO seller_requests 
             (user_id, business_name, business_description, phone,
              region_id, district_id, address, logo, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['user_id'],
                $data['business_name'],
                $data['business_description'],
                $data['phone'],
                $data['region_id'],
                $data['district_id'],
                $data['address'],
                $data['logo'] ?? null,
                $data['status'] ?? 'pending',
            ],
            'isssiisss'
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Foydalanuvchining oxirgi arizasini olish
     */
    public function getByUserId(int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT sr.*, u.full_name as user_name, u.email as user_email,
                    r.name_uz as region_name, d.name_uz as district_name
             FROM seller_requests sr
             JOIN users u ON sr.user_id = u.id
             LEFT JOIN regions r ON sr.region_id = r.id
             LEFT JOIN districts d ON sr.district_id = d.id
             WHERE sr.user_id = ?
             ORDER BY sr.created_at DESC 
             LIMIT 1",
            [$userId], 'i'
        );
    }

    /**
     * Ariza ID si bo'yicha olish
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT sr.*, u.full_name as user_name, u.email as user_email,
                    r.name_uz as region_name, d.name_uz as district_name
             FROM seller_requests sr
             JOIN users u ON sr.user_id = u.id
             LEFT JOIN regions r ON sr.region_id = r.id
             LEFT JOIN districts d ON sr.district_id = d.id
             WHERE sr.id = ?",
            [$id], 'i'
        );
    }

    /**
     * Barcha arizalarni olish (admin uchun)
     * Status bo'yicha filtrlash mumkin
     * 
     * @param string $status Filtr (bo'sh bo'lsa hammasi)
     * @return array Arizalar massivi
     */
    public function getAll(string $status = ''): array
    {
        $where = '';
        $params = [];
        $types = '';

        if (!empty($status)) {
            $where = 'WHERE sr.status = ?';
            $params[] = $status;
            $types = 's';
        }

        return $this->db->fetchAll(
            "SELECT sr.*, u.full_name as user_name, u.email as user_email,
                    r.name_uz as region_name, d.name_uz as district_name
             FROM seller_requests sr
             JOIN users u ON sr.user_id = u.id
             LEFT JOIN regions r ON sr.region_id = r.id
             LEFT JOIN districts d ON sr.district_id = d.id
             {$where}
             ORDER BY sr.created_at DESC",
            $params, $types
        );
    }

    /**
     * Ariza holatini o'zgartirish (approve/reject)
     */
    public function updateStatus(int $id, string $status): void
    {
        $this->db->query(
            "UPDATE seller_requests SET status = ? WHERE id = ?",
            [$status, $id], 'si'
        );
    }

    /**
     * Foydalanuvchining kutilayotgan arizasi borligini tekshirish
     */
    public function hasPendingRequest(int $userId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT id FROM seller_requests 
             WHERE user_id = ? AND status = 'pending'",
            [$userId], 'i'
        );
        return $row !== null;
    }

    /**
     * Status bo'yicha arizalar soni
     */
    public function countByStatus(string $status): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM seller_requests WHERE status = ?",
            [$status], 's'
        );
        return (int)($row['count'] ?? 0);
    }
}
