<?php
class Settings
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        $rows = $this->db->fetchAll("SELECT `key`, `value` FROM settings");
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    }

    public function get(string $key, string $default = ''): string
    {
        $row = $this->db->fetchOne(
            "SELECT `value` FROM settings WHERE `key` = ?",
            [$key], 's'
        );
        return $row['value'] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $this->db->query(
            "INSERT INTO settings (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [$key, $value], 'ss'
        );
    }

    public function update(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}
