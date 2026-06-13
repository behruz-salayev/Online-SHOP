<?php
/**
 * Cart - Savat bilan ishlash modeli
 * 
 * Savat sessiya asosida ishlaydi (ma'lumotlar bazasida emas).
 * Foydalanuvchi saytga kirgan yoki kirmaganligidan qat'iy nazar ishlaydi.
 * 
 * Sessiya tuzilishi: $_SESSION['cart'][product_id] = quantity
 */

class Cart
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Savatdagi mahsulotlarning umumiy sonini qaytaradi
     * 
     * @return int Mahsulotlar soni
     */
    public function getCount(): int
    {
        if (!isset($_SESSION['cart'])) return 0;
        return array_sum($_SESSION['cart']);
    }

    /**
     * Savatdagi mahsulotlarning umumiy narxini hisoblash
     * 
     * @return float Umumiy summa
     */
    public function getTotal(): float
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    /**
     * Savatdagi barcha mahsulotlarni to'liq ma'lumotlari bilan olish
     * Ma'lumotlar bazasidan mahsulotlarning narx va nomlarini oladi
     * 
     * @return array Mahsulotlar massivi
     */
    public function getItems(): array
    {
        $items = [];
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
            return $items;
        }

        // Savatdagi ID lar bo'yicha mahsulotlarni olish
        $ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
         $products = $this->db->fetchAll(
            "SELECT * FROM products 
             WHERE id IN ($placeholders) 
             AND status = 'active'",
            $ids, str_repeat('i', count($ids))
        );

        // Mahsulotga quantity ni qo'shamiz
        foreach ($products as $product) {
            $product['quantity'] = $_SESSION['cart'][$product['id']];
            $items[] = $product;
        }
        
        return $items;
    }

    /**
     * Mahsulotni savatga qo'shish
     * Agar mahsulot avval qo'shilgan bo'lsa, sonini oshiradi
     * 
     * @param int $productId Mahsulot ID si
     * @param int $quantity Necha dona
     * @return bool
     */
    public function add(int $productId, int $quantity = 1): bool
    {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        return true;
    }

    /**
     * Mahsulot sonini o'zgartirish
     * 
     * @param int $productId Mahsulot ID si
     * @param int $quantity Yangi son (0 bo'lsa mahsulot o'chiriladi)
     */
    public function updateQuantity(int $productId, int $quantity): void
    {
        if (isset($_SESSION['cart'][$productId])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$productId]);
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
        }
    }

    /**
     * Mahsulotni savatdan o'chirish
     */
    public function remove(int $productId): void
    {
        unset($_SESSION['cart'][$productId]);
    }

    /**
     * Savatni tozalash
     */
    public function clear(): void
    {
        $_SESSION['cart'] = [];
    }
}
