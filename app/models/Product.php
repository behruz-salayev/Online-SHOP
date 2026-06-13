<?php
/**
 * Product - Mahsulotlar bilan ishlash modeli
 * 
 * Vazifalari:
 * - Mahsulotlarni ro'yxatga olish, ko'rish, tahrirlash, o'chirish
 * - Kategoriya bo'yicha filtrlash
 * - Qidirish va saralash
 * - Sahifalash (pagination)
 * - Tasdiqlash holati bilan ishlash (pending/approved/rejected)
 */

class Product
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Faol va tasdiqlangan mahsulotlarni olish
     * 
     * @param int|null $categoryId Kategoriya ID (ixtiyoriy)
     * @param string $search Qidiruv matni (ixtiyoriy)
     * @param string $sort Saralash (newest, price_asc, price_desc, name)
     * @param int $limit Sahifadagi mahsulotlar soni
     * @param int $offset Qancha mahsulotni tashlab ketish
     * @return array Mahsulotlar massivi
     */
    public function getAll(?int $categoryId = null, string $search = '', string $sort = 'newest', int $limit = 12, int $offset = 0): array
    {
        // Asosiy shart: faqat faol mahsulotlar
        $where = "WHERE p.status = 'active'";
        $params = [];
        $types = '';

        // Kategoriya bo'yicha filtrlash
        if ($categoryId) {
            $where .= " AND p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        // Qidiruv bo'yicha filtrlash
        if (!empty($search)) {
            $where .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR s.full_name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
        }

        // Saralash
        $order = match($sort) {
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name'       => 'p.name ASC',
            default      => 'p.created_at DESC',
        };

        // So'rov
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug, 
                       s.full_name as seller_name, s.id as seller_id
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN users s ON p.seller_id = s.id
                {$where}
                ORDER BY {$order}
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        return $this->db->fetchAll($sql, $params, $types);
    }

    /**
     * Faol mahsulotlar sonini hisoblash (sahifalash uchun)
     */
    public function countAll(?int $categoryId = null, string $search = ''): int
    {
        $where = "WHERE p.status = 'active'";
        $params = [];
        $types = '';

        if ($categoryId) {
            $where .= " AND p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        if (!empty($search)) {
            $where .= " AND (p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR s.full_name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
        }

        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users s ON p.seller_id = s.id
             {$where}",
            $params, $types
        );
        
        return $row['count'] ?? 0;
    }

    /**
     * Bitta mahsulotni ID bo'yicha olish
     * 
     * @param int $id Mahsulot ID si
     * @param bool $includeDraft Tasdiqlanmaganlarni ham qo'shish
     * @return array|null Mahsulot yoki null
     */
    public function getById(int $id, bool $includeDraft = false): ?array
    {
        $where = "p.id = ?";
        $params = [$id];
        $types = 'i';

        if (!$includeDraft) {
            $where .= " AND p.status = 'active'";
        }

        return $this->db->fetchOne(
            "SELECT p.*, c.name as category_name, c.slug as category_slug,
                    s.full_name as seller_name, s.email as seller_email, s.id as seller_id,
                    sel.business_name as seller_store_name, sel.logo as seller_logo, sel.id as seller_profile_id
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users s ON p.seller_id = s.id
             LEFT JOIN sellers sel ON s.id = sel.user_id
             WHERE {$where}",
            $params, $types
        );
    }

    /**
     * Mashhur (featured) mahsulotlarni olish
     */
    public function getFeatured(int $limit = 8): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, c.name as category_name, s.full_name as seller_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users s ON p.seller_id = s.id
              WHERE p.is_featured = 1 
                AND p.status = 'active' 
              ORDER BY p.created_at DESC 
              LIMIT ?",
            [$limit], 'i'
        );
    }

    /**
     * O'xshash mahsulotlarni olish (bir kategoriyadagi boshqa mahsulotlar)
     */
    public function getRelated(int $productId, int $categoryId, int $limit = 4): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, c.name as category_name, s.full_name as seller_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users s ON p.seller_id = s.id
             WHERE p.category_id = ? 
               AND p.id != ? 
               AND p.status = 'active'
              ORDER BY RAND() 
              LIMIT ?",
            [$categoryId, $productId, $limit], 'iii'
        );
    }

    /**
     * Yangi mahsulot yaratish
     * 
     * @param array $data Mahsulot ma'lumotlari
     * @return int Yangi mahsulot ID si
     */
    public function create(array $data): int
    {
        $slug = $this->generateSlug($data['name']);
        
        $this->db->query(
            "INSERT INTO products 
             (category_id, seller_id, name, slug, description, price, old_price, 
              stock, image, specs, is_featured, status, approval_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['category_id'],
                $data['seller_id'] ?? null,
                $data['name'],
                $slug,
                $data['description'] ?? '',
                $data['price'],
                $data['old_price'] ?? null,
                $data['stock'] ?? 0,
                $data['image'] ?? '',
                $data['specs'] ?? null,
                $data['is_featured'] ?? 0,
                $data['status'] ?? 'active',
                $data['approval_status'] ?? 'pending'
            ],
            'iisssddississ'
        );
        
        return $this->db->lastInsertId();
    }

    /**
     * Mahsulotni tahrirlash
     */
    public function update(int $id, array $data): void
    {
        $slug = $this->generateSlug($data['name'], $id);
        
        $this->db->query(
            "UPDATE products SET 
                category_id = ?, seller_id = ?, name = ?, slug = ?, description = ?,
                price = ?, old_price = ?, stock = ?, image = ?,
                specs = ?, is_featured = ?, status = ?, approval_status = ?
             WHERE id = ?",
            [
                $data['category_id'],
                $data['seller_id'] ?? null,
                $data['name'],
                $slug,
                $data['description'] ?? '',
                $data['price'],
                $data['old_price'] ?? null,
                $data['stock'] ?? 0,
                $data['image'] ?? '',
                $data['specs'] ?? null,
                $data['is_featured'] ?? 0,
                $data['status'] ?? 'active',
                $data['approval_status'] ?? 'pending',
                $id
            ],
            'iisssddississi'
        );
    }

    /**
     * Mahsulotni o'chirish (rasmni ham o'chiradi)
     */
    public function delete(int $id): void
    {
        // Avval rasmni o'chiramiz
        $product = $this->getById($id, true);
        if ($product && $product['image']) {
            $filePath = UPLOAD_DIR . $product['image'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        // Keyin mahsulotni o'chiramiz
        $this->db->query("DELETE FROM products WHERE id = ?", [$id], 'i');
    }

    // ===== Admin uchun funksiyalar =====

    /**
     * Barcha mahsulotlarni olish (admin paneli uchun)
     * Filtrlash va qidirish imkoniyati bilan
     */
    public function getAllAdmin(string $search = '', string $status = '', string $approvalStatus = '', int $categoryId = 0): array
    {
        $where = "WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($search)) {
            $where .= " AND (p.name LIKE ? OR p.id = ? OR s.full_name LIKE ? OR c.name LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params = array_merge($params, [$searchTerm, is_numeric($search) ? (int)$search : 0, $searchTerm, $searchTerm]);
            $types .= 'siss';
        }

        if ($categoryId > 0) {
            $where .= " AND p.category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }

        if (!empty($status)) {
            $where .= " AND p.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        if (!empty($approvalStatus)) {
            $where .= " AND p.approval_status = ?";
            $params[] = $approvalStatus;
            $types .= 's';
        }

        return $this->db->fetchAll(
            "SELECT p.*, c.name as category_name, s.full_name as seller_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN users s ON p.seller_id = s.id
             {$where}
             ORDER BY p.created_at DESC",
            $params, $types
        );
    }

    // ===== Sotuvchi uchun funksiyalar =====

    /**
     * Sotuvchining barcha mahsulotlarini olish
     */
    public function getAllBySeller(int $sellerId, string $approvalStatus = '', string $status = ''): array
    {
        $where = "WHERE p.seller_id = ?";
        $params = [$sellerId];
        $types = 'i';

        if (!empty($approvalStatus)) {
            $where .= " AND p.approval_status = ?";
            $params[] = $approvalStatus;
            $types .= 's';
        }

        if (!empty($status)) {
            $where .= " AND p.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        return $this->db->fetchAll(
            "SELECT p.*, c.name as category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             {$where}
             ORDER BY p.created_at DESC",
            $params, $types
        );
    }

    /**
     * Sotuvchining bitta mahsulotini olish
     * (faqat o'z mahsulotini ko'rishi mumkin)
     */
    public function getBySeller(int $sellerId, int $productId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM products WHERE id = ? AND seller_id = ?",
            [$productId, $sellerId], 'ii'
        );
    }

    /**
     * Mahsulotning tasdiqlash holatini o'zgartirish
     */
    public function setApprovalStatus(int $id, string $status): void
    {
        $this->db->query(
            "UPDATE products SET approval_status = ? WHERE id = ?",
            [$status, $id], 'si'
        );
    }

    /**
     * Tasdiqlash holati bo'yicha mahsulotlar sonini hisoblash
     */
    public function countByApprovalStatus(string $approvalStatus): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products WHERE approval_status = ?",
            [$approvalStatus], 's'
        );
        return (int)($row['count'] ?? 0);
    }

    /**
     * Noyob slug yaratish
     * Agar slug band bo'lsa, oxiriga raqam qo'shiladi
     * 
     * @param string $name Mahsulot nomi
     * @param int|null $excludeId Tahrirlashda o'zini hisobga olmaslik
     * @return string
     */
    private function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = slugify($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $sql = "SELECT id FROM products WHERE slug = ?";
            $params = [$slug];
            $types = 's';

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
                $types .= 'i';
            }

            $existing = $this->db->fetchOne($sql, $params, $types);
            if (!$existing) break;

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
