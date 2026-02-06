<?php
// app/models/Wallet.php

namespace App\Models;

use App\Core\Database;

class Wallet {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserWallets($userId, $onlyActive = true) {
        $sql = "SELECT * FROM wallets WHERE user_id = ?";

        if ($onlyActive) {
            $sql .= " AND status = 'active'";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $sql = "SELECT w.*, u.username, u.email 
                FROM wallets w 
                JOIN users u ON w.user_id = u.id 
                WHERE w.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO wallets (user_id, name, description, status) 
                VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active'
        ]);
    }

    public function update($id, $data) {
        $sql = "UPDATE wallets SET 
                name = ?, 
                description = ?, 
                status = ?,
                updated_at = NOW()
                WHERE id = ? AND user_id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $id,
            $data['user_id']
        ]);
    }

    public function delete($id, $userId) {
        // Exclusão lógica (inativação)
        $sql = "UPDATE wallets SET status = 'disabled' WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    public function getTotalCount($userId = null) {
        if ($userId) {
            $sql = "SELECT COUNT(*) as total FROM wallets WHERE user_id = ? AND status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM wallets WHERE status = 'active'";
            $stmt = $this->db->query($sql); // query() é do PDO, não da classe Database, mas Database::getConnection retorna PDO
        }
        return $stmt->fetch()['total'];
    }
}
