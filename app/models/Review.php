<?php
class Review
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getByProduct(int $productId): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.full_name as user_name
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             WHERE r.product_id = ? AND r.status = 'approved'
             ORDER BY r.created_at DESC",
            [$productId], 'i'
        );
    }

    public function getById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT r.*, u.full_name as user_name, p.name as product_name
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             JOIN products p ON r.product_id = p.id
             WHERE r.id = ?",
            [$id], 'i'
        );
    }

    public function getAverage(int $productId): float
    {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(AVG(rating), 0) as avg_rating
             FROM reviews WHERE product_id = ? AND status = 'approved'",
            [$productId], 'i'
        );
        return round((float)($row['avg_rating'] ?? 0), 1);
    }

    public function getCount(int $productId): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM reviews WHERE product_id = ? AND status = 'approved'",
            [$productId], 'i'
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function getPending(): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.full_name as user_name, p.name as product_name
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             JOIN products p ON r.product_id = p.id
             WHERE r.status = 'pending'
             ORDER BY r.created_at DESC"
        );
    }

    public function getPendingCount(): int
    {
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) as cnt FROM reviews WHERE status = 'pending'"
        );
        return (int)($row['cnt'] ?? 0);
    }

    public function hasUserReviewed(int $productId, int $userId): bool
    {
        $row = $this->db->fetchOne(
            "SELECT id FROM reviews WHERE product_id = ? AND user_id = ?",
            [$productId, $userId], 'ii'
        );
        return $row !== null;
    }

    public function create(int $productId, int $userId, int $rating, ?string $comment): int
    {
        $this->db->query(
            "INSERT INTO reviews (product_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, 'pending')",
            [$productId, $userId, $rating, $comment], 'iiis'
        );
        return $this->db->lastInsertId();
    }

    public function setApprovalStatus(int $id, string $status): void
    {
        $this->db->query(
            "UPDATE reviews SET status = ? WHERE id = ?",
            [$status, $id], 'si'
        );
    }

    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, u.full_name as user_name, p.name as product_name
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             JOIN products p ON r.product_id = p.id
             ORDER BY r.created_at DESC"
        );
    }

    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM reviews WHERE id = ?", [$id], 'i');
    }
}
