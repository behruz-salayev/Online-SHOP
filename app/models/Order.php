<?php
/**
 * Order - Buyurtmalar bilan ishlash modeli
 * 
 * Vazifalari:
 * - Yangi buyurtma yaratish
 * - Buyurtmalarni ko'rish (foydalanuvchi, sotuvchi, admin)
 * - Buyurtma holatini yangilash
 * - Buyurtma tarixini saqlash
 * - Statistik ma'lumotlar
 */

class Order
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    /** Holat nomlarining o'zbekcha tarjimalari */
    private array $statusLabels = [
        'pending'    => 'Kutilmoqda',
        'confirmed'  => 'Tasdiqlandi',
        'processing' => 'Tayyorlanmoqda',
        'shipped'    => 'Yo\'lda',
        'delivered'  => 'Yetkazildi',
        'cancelled'  => 'Bekor qilindi'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Yangi buyurtma yaratish
     * 
     * Bu funksiya:
     * 1. Savatdagi mahsulotlarni oladi
     * 2. Umumiy summani hisoblaydi
     * 3. Buyurtma yaratadi
     * 4. Har bir mahsulot uchun order_item yaratadi
     * 5. Mahsulot stock dan ayiradi
     * 6. Holat tarixiga yozadi
     * 7. Savatni tozalaydi
     * 
     * @param int $userId Foydalanuvchi ID si
     * @param array $data Buyurtma ma'lumotlari
     * @return int Buyurtma ID si
     * @throws Exception Agar savat bo'sh bo'lsa
     */
    public function create(int $userId, array $data): int
    {
        $cartModel = new Cart();
        $cartItems = $cartModel->getItems();

        if (empty($cartItems)) {
            throw new \Exception('Savat bo\'sh');
        }

        // Umumiy summani hisoblash
        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $totalPrice += $item['price'] * $item['quantity'];
        }

        // Foydalanuvchi IP manzili
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        // 1. Buyurtmani yaratish
        $this->db->query(
            "INSERT INTO orders (user_id, total_price, region_id, district_id, address, 
                                latitude, longitude, phone, ip_address, payment_method)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $totalPrice,
                $data['region_id'],
                $data['district_id'],
                $data['address'],
                $data['latitude'] ?? null,
                $data['longitude'] ?? null,
                $data['phone'],
                $ipAddress,
                $data['payment_method']
            ],
            'idiissssss'
        );

        $orderId = $this->db->lastInsertId();

        // 2. Har bir mahsulot uchun order_item yaratish + stock dan ayirish
        foreach ($cartItems as $item) {
            // Order item yaratish
            $this->db->query(
                "INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, seller_id)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $item['price'],
                    $item['quantity'],
                    $item['seller_id'] ?? null
                ],
                'iisdis'
            );

            // Stock dan ayirish (agar yetarlicha bo'lsa)
            $this->db->query(
                "UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?",
                [$item['quantity'], $item['id'], $item['quantity']],
                'iii'
            );
        }

        // 3. Holat tarixiga yozish
        $this->db->query(
            "INSERT INTO order_status_history (order_id, status, comment) 
             VALUES (?, 'pending', 'Buyurtma yaratildi')",
            [$orderId], 'i'
        );

        // 4. Savatni tozalash
        $cartModel->clear();

        return $orderId;
    }

    /**
     * Foydalanuvchining barcha buyurtmalarini olish
     */
    public function getByUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT o.*, r.name_uz as region_name, d.name_uz as district_name
             FROM orders o
             LEFT JOIN regions r ON o.region_id = r.id
             LEFT JOIN districts d ON o.district_id = d.id
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC",
            [$userId], 'i'
        );
    }

    /**
     * Sotuvchining buyurtmalarini olish
     * (faqat o'z mahsulotlari bo'lgan buyurtmalar)
     */
    public function getBySeller(int $sellerId, ?string $status = null): array
    {
        $sql = "SELECT DISTINCT o.*, u.full_name, u.email, u.phone as user_phone,
                       r.name_uz as region_name, d.name_uz as district_name
                FROM orders o
                JOIN order_items oi ON oi.order_id = o.id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN regions r ON o.region_id = r.id
                LEFT JOIN districts d ON o.district_id = d.id
                WHERE oi.seller_id = ?";
        $params = [$sellerId];
        $types = 'i';

        if ($status && $status !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        $sql .= " ORDER BY o.created_at DESC";
        return $this->db->fetchAll($sql, $params, $types);
    }

    /**
     * Bitta buyurtmani ID bo'yicha olish
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT o.*, u.full_name, u.email, 
                    r.name_uz as region_name, d.name_uz as district_name
             FROM orders o
             JOIN users u ON o.user_id = u.id
             LEFT JOIN regions r ON o.region_id = r.id
             LEFT JOIN districts d ON o.district_id = d.id
             WHERE o.id = ?",
            [$id], 'i'
        );
    }

    /**
     * Buyurtmadagi mahsulotlarni olish
     */
    public function getItems(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ?",
            [$orderId], 'i'
        );
    }

    /**
     * Buyurtma holati tarixini olish
     */
    public function getStatusHistory(int $orderId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM order_status_history 
             WHERE order_id = ? 
             ORDER BY created_at ASC",
            [$orderId], 'i'
        );
    }

    /**
     * Barcha buyurtmalarni olish (admin uchun)
     * Status bo'yicha filtrlash mumkin
     */
    public function getAll(?string $status = null): array
    {
        $sql = "SELECT o.*, u.full_name, u.email, u.phone as user_phone,
                       r.name_uz as region_name, d.name_uz as district_name
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN regions r ON o.region_id = r.id
                LEFT JOIN districts d ON o.district_id = d.id";
        $params = [];
        $types = '';

        if ($status && $status !== 'all') {
            $sql .= " WHERE o.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        $sql .= " ORDER BY o.created_at DESC";
        return $this->db->fetchAll($sql, $params, $types);
    }

    /**
     * Buyurtma holatini o'zgartirish va tarixga yozish
     * 
     * @param int $id Buyurtma ID si
     * @param string $status Yangi holat
     * @param string $comment Izoh (ixtiyoriy)
     */
    public function updateStatus(int $id, string $status, string $comment = ''): void
    {
        // Holatni yangilash
        $this->db->query(
            "UPDATE orders SET status = ? WHERE id = ?",
            [$status, $id], 'si'
        );

        // Tarixga yozish
        $label = $this->statusLabels[$status] ?? $status;
        $commentText = $comment ?: "Holat o'zgartirildi: {$label}";

        $this->db->query(
            "INSERT INTO order_status_history (order_id, status, comment, created_by)
             VALUES (?, ?, ?, ?)",
            [$id, $status, $commentText, $_SESSION['user_id'] ?? 0], 'issi'
        );
    }

    /**
     * Buyurtma holati nomini olish
     */
    public function getStatusLabel(string $status): string
    {
        return $this->statusLabels[$status] ?? $status;
    }

    /**
     * Sotuvchining daromad statistikasi (kunlik, haftalik, oylik)
     * 
     * @param int $sellerId Sotuvchi ID si
     * @return array Kunlik, haftalik va oylik daromad/zarar ma'lumotlari
     */
    public function getSellerRevenueStats(int $sellerId): array
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-d', strtotime('first day of this month'));

        $sql = "SELECT 
                    SUM(CASE WHEN o.status NOT IN ('cancelled') THEN oi.product_price * oi.quantity ELSE 0 END) as income,
                    SUM(CASE WHEN o.status = 'cancelled' THEN oi.product_price * oi.quantity ELSE 0 END) as loss
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.seller_id = ? AND DATE(o.created_at) >= ?";

        $getPeriod = function(string $startDate) use ($sql, $sellerId): array {
            $row = $this->db->fetchOne($sql, [$sellerId, $startDate], 'is');
            $income = (float)($row['income'] ?? 0);
            $loss = (float)($row['loss'] ?? 0);
            return [
                'income' => $income,
                'loss'   => $loss,
                'net'    => $income - $loss,
            ];
        };

        $allTimeRow = $this->db->fetchOne(
            "SELECT 
                SUM(CASE WHEN o.status NOT IN ('cancelled') THEN oi.product_price * oi.quantity ELSE 0 END) as income,
                SUM(CASE WHEN o.status = 'cancelled' THEN oi.product_price * oi.quantity ELSE 0 END) as loss
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE oi.seller_id = ?",
            [$sellerId], 'i'
        );
        $allTimeIncome = (float)($allTimeRow['income'] ?? 0);
        $allTimeLoss = (float)($allTimeRow['loss'] ?? 0);

        return [
            'today'    => $getPeriod($today),
            'week'     => $getPeriod($weekStart),
            'month'    => $getPeriod($monthStart),
            'all_time' => [
                'income' => $allTimeIncome,
                'loss'   => $allTimeLoss,
                'net'    => $allTimeIncome - $allTimeLoss,
            ],
        ];
    }

    /**
     * Umumiy statistika (admin dashboard uchun)
     * 
     * @return array Jami buyurtmalar, kutilayotganlar, daromad, mahsulotlar
     */
    public function getStats(): array
    {
        $total = $this->db->fetchOne("SELECT COUNT(*) as count FROM orders");
        $pending = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'"
        );
        $revenue = $this->db->fetchOne(
            "SELECT COALESCE(SUM(total_price), 0) as total 
             FROM orders WHERE status NOT IN ('cancelled')"
        );
        $products = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products WHERE status = 'active'"
        );

        return [
            'total_orders'    => (int)($total['count'] ?? 0),
            'pending_orders'  => (int)($pending['count'] ?? 0),
            'total_revenue'   => (float)($revenue['total'] ?? 0),
            'total_products'  => (int)($products['count'] ?? 0),
        ];
    }

    public function getMonthlyRevenue(): array
    {
        return $this->db->fetchAll(
            "SELECT MONTH(created_at) as month, 
                    COALESCE(SUM(total_price), 0) as total
             FROM orders 
             WHERE status NOT IN ('cancelled') 
               AND YEAR(created_at) = YEAR(NOW())
             GROUP BY MONTH(created_at)
             ORDER BY month ASC"
        );
    }

    public function getDailyOrderStats(): array
    {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM orders 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC"
        );
    }
}
