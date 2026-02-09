<?php
// app/models/WalletStock.php

namespace App\Models;

use App\Core\Database;

class WalletStock {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByWalletId($walletId) {
        $sql = "SELECT ws.*, 
                       COALESCE(ws.last_market_price, 0) as last_price,
                       (ws.quantity * ws.average_cost_per_share) as total_invested,
                       (ws.quantity * COALESCE(ws.last_market_price, 0)) as current_value,
                       ((COALESCE(ws.last_market_price, 0) - ws.average_cost_per_share) / ws.average_cost_per_share * 100) as percent_change
                FROM wallet_stocks ws 
                WHERE ws.wallet_id = ? 
                ORDER BY ws.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId]);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $sql = "SELECT ws.*, w.name as wallet_name, w.user_id 
                FROM wallet_stocks ws 
                JOIN wallets w ON ws.wallet_id = w.id 
                WHERE ws.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO wallet_stocks 
                (wallet_id, ticker, quantity, average_cost_per_share, total_cost, target_allocation) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['wallet_id'],
            $data['ticker'],
            $data['quantity'],
            $data['average_cost_per_share'],
            $data['total_cost'],
            $data['target_allocation']
        ]);

        if ($success) {
            $this->updateWalletTotals($data['wallet_id']);
        }

        return $success;
    }

    public function update($id, $data) {
        $sql = "UPDATE wallet_stocks SET 
                ticker = ?, 
                quantity = ?, 
                average_cost_per_share = ?, 
                total_cost = ?,
                target_allocation = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['ticker'],
            $data['quantity'],
            $data['average_cost_per_share'],
            $data['total_cost'],
            $data['target_allocation'],
            $id
        ]);

        if ($success) {
            $stock = $this->findById($id);
            $this->updateWalletTotals($stock['wallet_id']);
        }

        return $success;
    }

    public function delete($id) {
        $stock = $this->findById($id);
        if (!$stock) return false;

        $sql = "DELETE FROM wallet_stocks WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([$id]);

        if ($success) {
            $this->updateWalletTotals($stock['wallet_id']);
        }

        return $success;
    }

    public function updateMarketPrice($id, $price) {
        $sql = "UPDATE wallet_stocks SET 
                last_market_price = ?,
                last_market_price_updated = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$price, $id]);
    }

    public function updateAllPricesInWallet($walletId, $prices) {
        foreach ($prices as $stockId => $price) {
            $this->updateMarketPrice($stockId, $price);
        }

        $this->updateWalletTotals($walletId);
        return true;
    }

    public function updateWalletTotals($walletId) {
        // Calcular totais da carteira
        $sql = "UPDATE wallets w
                SET total_invested = (
                    SELECT COALESCE(SUM(total_cost), 0) 
                    FROM wallet_stocks 
                    WHERE wallet_id = w.id
                ),
                current_value = (
                    SELECT COALESCE(SUM(quantity * COALESCE(last_market_price, average_cost_per_share)), 0)
                    FROM wallet_stocks 
                    WHERE wallet_id = w.id
                ),
                profit_loss = (
                    SELECT COALESCE(SUM(quantity * COALESCE(last_market_price, average_cost_per_share) - total_cost), 0)
                    FROM wallet_stocks 
                    WHERE wallet_id = w.id
                ),
                updated_at = NOW()
                WHERE w.id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$walletId]);
    }

    public function getWalletSummary($walletId) {
        $sql = "SELECT 
                    COUNT(*) as total_stocks,
                    SUM(quantity) as total_quantity,
                    SUM(total_cost) as total_invested,
                    SUM(quantity * COALESCE(last_market_price, average_cost_per_share)) as current_value,
                    SUM(quantity * COALESCE(last_market_price, average_cost_per_share) - total_cost) as total_pl
                FROM wallet_stocks 
                WHERE wallet_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId]);
        return $stmt->fetch();
    }

    public function findByTickerAndWallet($ticker, $walletId) {
        $sql = "SELECT * FROM wallet_stocks 
            WHERE ticker = ? AND wallet_id = ? 
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ticker, $walletId]);
        return $stmt->fetch();
    }
}