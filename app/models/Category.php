<?php
/**
 * Category - Kategoriyalar bilan ishlash modeli
 * 
 * Mahsulotlarni turlarga ajratish uchun ishlatiladi.
 * Masalan: iPhone, Samsung, Xiaomi, va hokazo.
 */

class Category
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Barcha kategoriyalarni olish (har biridagi mahsulotlar soni bilan)
     * 
     * @return array Kategoriyalar massivi
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM products 
                     WHERE category_id = c.id AND status = 'active') as product_count
             FROM categories c 
             ORDER BY c.name ASC"
        );
    }

    /**
     * Kategoriyani ID bo'yicha olish
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM categories WHERE id = ?", 
            [$id], 'i'
        );
    }

    /**
     * Kategoriyani slug bo'yicha olish
     */
    public function getBySlug(string $slug): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM categories WHERE slug = ?", 
            [$slug]
        );
    }

    /**
     * Yangi kategoriya yaratish
     * 
     * @param string $name Kategoriya nomi
     * @param string $description Tavsif
     * @param string $image Rasm
     * @return int Yangi kategoriya ID si
     */
    public function create(string $name, string $description = '', string $image = ''): int
    {
        $slug = slugify($name);
        $this->db->query(
            "INSERT INTO categories (name, slug, description, image) 
             VALUES (?, ?, ?, ?)",
            [$name, $slug, $description, $image]
        );
        return $this->db->lastInsertId();
    }

    /**
     * Kategoriyani tahrirlash
     */
    public function update(int $id, string $name, string $description = '', string $image = ''): void
    {
        $slug = slugify($name);
        $this->db->query(
            "UPDATE categories SET name = ?, slug = ?, description = ?, image = ? 
             WHERE id = ?",
            [$name, $slug, $description, $image, $id]
        );
    }

    /**
     * Kategoriyani o'chirish
     */
    public function delete(int $id): void
    {
        $this->db->query(
            "DELETE FROM categories WHERE id = ?", 
            [$id], 'i'
        );
    }
}
