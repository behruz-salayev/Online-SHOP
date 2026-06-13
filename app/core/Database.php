<?php
/**
 * Database - Ma'lumotlar bazasi bilan ishlash classi
 * 
 * Singleton (yakka) pattern asosida yaratilgan.
 * Bir vaqtning o'zida faqat bitta ulanish ishlaydi.
 * MySQLi dan foydalaniladi.
 */

class Database
{
    /** @var Database|null Yakka o'zini saqlash uchun */
    private static ?Database $instance = null;
    
    /** @var mysqli MySQLi ulanish obyekti */
    private mysqli $connection;

    /**
     * Konstruktor - Ma'lumotlar bazasiga ulanishni yaratadi
     * XAMPP uchun maxsus socket ulanishi ham qo'llab-quvvatlanadi
     */
    private function __construct()
    {
        // XAMPP socket faylining joylashuvi
        $xamppSocket = '/opt/lampp/var/mysql/mysql.sock';
        $connection = null;

        // Avval oddiy usulda ulanishga harakat qilamiz
        try {
            $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        } catch (Exception $e) {
            // Agar XAMPP ishlatilayotgan bo'lsa, socket orqali ulanish
            if (file_exists($xamppSocket)) {
                $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306, $xamppSocket);
            }
        }

        // Agar ulanish muvaffaqiyatsiz bo'lsa, xatolik chiqaramiz
        if (!$connection || $connection->connect_error) {
            throw new Exception(
                'Ma\'lumotlar bazasiga ulanishda xatolik: ' . 
                ($connection ? $connection->connect_error : 'Ulanish topilmadi')
            );
        }

        // O'zbekcha belgilar uchun utf8mb4 kodlashini o'rnatamiz
        $this->connection = $connection;
        $this->connection->set_charset('utf8mb4');
    }

    /**
     * Database klassining yagona nusxasini qaytaradi
     * Agar mavjud bo'lmasa, yangisini yaratadi
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * SQL so'rovni bajarish
     * 
     * @param string $sql SQL so'rov (parametrlar uchun ? belgisi ishlatiladi)
     * @param array $params Parametrlar massivi
     * @param string $types Parametr turlari (i=int, s=string, d=double)
     * @return mysqli_stmt|false Tayyorlangan so'rov natijasi
     * 
     * Xavfsizlik: SQL injection hujumining oldini olish uchun
     * tayyorlangan so'rovlardan (prepared statements) foydalaniladi
     */
    public function query(string $sql, array $params = [], string $types = ''): mysqli_stmt|false
    {
        // SQL so'rovni tayyorlaymiz
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception(
                'So\'rov xatoligi: ' . $this->connection->error . 
                ' [SQL: ' . $sql . ']'
            );
        }

        // Agar parametrlar mavjud bo'lsa, ularni bog'laymiz
        if (!empty($params)) {
            // Agar turlar ko'rsatilmagan bo'lsa, hammasini string deb olib ketamiz
            if (empty($types)) {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }

        // So'rovni bajarish
        $stmt->execute();
        return $stmt;
    }

    /**
     * Bitta qatorni qaytaradi (assotsiativ massiv)
     * 
     * @param string $sql SQL so'rov
     * @param array $params Parametrlar
     * @param string $types Parametr turlari
     * @return array|null Topilgan qator yoki null
     */
    public function fetchOne(string $sql, array $params = [], string $types = ''): ?array
    {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }

    /**
     * Barcha qatorlarni qaytaradi (assotsiativ massivlar massivi)
     * 
     * @param string $sql SQL so'rov
     * @param array $params Parametrlar
     * @param string $types Parametr turlari
     * @return array Topilgan qatorlar massivi
     */
    public function fetchAll(string $sql, array $params = [], string $types = ''): array
    {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Oxirgi qo'shilgan yozuvning ID sini qaytaradi
     */
    public function lastInsertId(): int
    {
        return $this->connection->insert_id;
    }

    /**
     * Oxirgi so'rovda ta'sirlangan qatorlar sonini qaytaradi
     */
    public function affectedRows(): int
    {
        return $this->connection->affected_rows;
    }

    /**
     * Matnni xavfsiz qilish (maxsus belgilarni tozalash)
     * 
     * @param string $value Tozalanadigan matn
     * @return string Xavfsiz matn
     */
    public function escape(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }
}
