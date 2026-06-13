<?php
/**
 * Region - Viloyat va tumanlar bilan ishlash modeli
 * 
 * O'zbekiston viloyatlari va tumanlari ma'lumotlarini boshqaradi.
 * Buyurtma berishda yetkazib berish manzilini tanlash uchun ishlatiladi.
 */

class Region
{
    /** @var Database Ma'lumotlar bazasi ulanishi */
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Barcha viloyatlarni olish
     * 
     * @return array Viloyatlar massivi
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM regions ORDER BY name_uz ASC"
        );
    }

    /**
     * Viloyatni ID bo'yicha olish
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM regions WHERE id = ?", 
            [$id], 'i'
        );
    }

    /**
     * Viloyatga tegishli tumanlarni olish
     * 
     * @param int $regionId Viloyat ID si
     * @return array Tumanlar massivi
     */
    public function getDistricts(int $regionId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM districts WHERE region_id = ? ORDER BY name_uz ASC",
            [$regionId], 'i'
        );
    }
}
