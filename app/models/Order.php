<?php
// app/models/Order.php

namespace App\Models;

use App\Core\Database;

class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserOrders($userId, $filters = [], $limit = 10, $offset = 0) {
        $sql = "SELECT o.*, 
                       COUNT(oi.id) as items_count,
                       SUM(oi.total_price) as calculated_total
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id AND oi.deleted_at IS NULL
                WHERE o.user_id = ? AND o.deleted_at IS NULL";

        $params = [$userId];

        // Filtros
        if (!empty($filters['search'])) {
            $sql .= " AND (o.customer_name LIKE ? OR o.order_number LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND o.order_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND o.order_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " GROUP BY o.id";

        // Ordenação
        $allowedSort = ['created_at', 'order_date', 'customer_name', 'total_amount', 'status'];
        $sort = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'created_at';
        $order = strtoupper($filters['order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY o.{$sort} {$order}";

        // Paginação
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countUserOrders($userId, $filters = []) {
        $sql = "SELECT COUNT(*) FROM orders o WHERE o.user_id = ? AND o.deleted_at IS NULL";
        $params = [$userId];

        if (!empty($filters['search'])) {
            $sql .= " AND (o.customer_name LIKE ? OR o.order_number LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND o.order_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND o.order_date <= ?";
            $params[] = $filters['date_to'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function findById($id) {
        $sql = "SELECT o.*, u.username, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? AND o.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findWithItems($id) {
        $sql = "SELECT o.*, 
                       u.username,
                       oi.id as item_id,
                       oi.product_name,
                       oi.product_code,
                       oi.quantity,
                       oi.unit_price,
                       oi.total_price,
                       oi.description as item_description
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id AND oi.deleted_at IS NULL
                WHERE o.id = ? AND o.deleted_at IS NULL
                ORDER BY oi.created_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $result = $stmt->fetchAll();
        if (empty($result)) return null;

        // Organizar em estrutura master-detail
        $order = $result[0];
        $order['items'] = [];

        foreach ($result as $row) {
            if ($row['item_id']) {
                $order['items'][] = [
                    'id' => $row['item_id'],
                    'product_name' => $row['product_name'],
                    'product_code' => $row['product_code'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'total_price' => $row['total_price'],
                    'description' => $row['item_description']
                ];
            }
        }

        return $order;
    }

    public function create($data, $items = []) {
        $this->db->beginTransaction();

        try {
            // Inserir pedido
            $sql = "INSERT INTO orders (
                    user_id, order_number, customer_name, customer_email, 
                    customer_phone, customer_address, total_amount, status,
                    order_date, delivery_date, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $orderDate = $data['order_date'] ?? date('Y-m-d');
            $stmt->execute([
                $data['user_id'],
                $data['order_number'] ?? $this->generateOrderNumber(),
                $data['customer_name'],
                $data['customer_email'] ?? null,
                $data['customer_phone'] ?? null,
                $data['customer_address'] ?? null,
                $data['total_amount'] ?? 0,
                $data['status'] ?? 'pending',
                $orderDate,
                $data['delivery_date'] ?? null,
                $data['notes'] ?? null
            ]);

            $orderId = $this->db->lastInsertId();

            // Inserir itens do pedido
            if (!empty($items)) {
                $totalAmount = 0;
                foreach ($items as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    $totalAmount += $itemTotal;

                    $itemSql = "INSERT INTO order_items (
                                order_id, product_name, product_code, 
                                quantity, unit_price, total_price, description
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

                    $itemStmt = $this->db->prepare($itemSql);
                    $itemStmt->execute([
                        $orderId,
                        $item['product_name'],
                        $item['product_code'] ?? null,
                        $item['quantity'],
                        $item['unit_price'],
                        $itemTotal,
                        $item['description'] ?? null
                    ]);
                }

                // Atualizar total do pedido
                $updateSql = "UPDATE orders SET total_amount = ? WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$totalAmount, $orderId]);
            }

            $this->db->commit();
            return $orderId;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erro ao criar pedido: " . $e->getMessage());
            throw $e;
        }
    }

    public function update($id, $data, $items = []) {
        $this->db->beginTransaction();

        try {
            // Atualizar pedido
            $sql = "UPDATE orders SET 
                    customer_name = ?,
                    customer_email = ?,
                    customer_phone = ?,
                    customer_address = ?,
                    total_amount = ?,
                    status = ?,
                    order_date = ?,
                    delivery_date = ?,
                    notes = ?,
                    updated_at = NOW()
                    WHERE id = ? AND user_id = ? AND deleted_at IS NULL";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['customer_name'],
                $data['customer_email'] ?? null,
                $data['customer_phone'] ?? null,
                $data['customer_address'] ?? null,
                $data['total_amount'] ?? 0,
                $data['status'] ?? 'pending',
                $data['order_date'],
                $data['delivery_date'] ?? null,
                $data['notes'] ?? null,
                $id,
                $data['user_id']
            ]);

            // Remover itens antigos (soft delete)
            $deleteItemsSql = "UPDATE order_items SET deleted_at = NOW() WHERE order_id = ?";
            $deleteStmt = $this->db->prepare($deleteItemsSql);
            $deleteStmt->execute([$id]);

            // Inserir novos itens
            if (!empty($items)) {
                $totalAmount = 0;
                foreach ($items as $item) {
                    $itemTotal = $item['quantity'] * $item['unit_price'];
                    $totalAmount += $itemTotal;

                    $itemSql = "INSERT INTO order_items (
                                order_id, product_name, product_code, 
                                quantity, unit_price, total_price, description
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

                    $itemStmt = $this->db->prepare($itemSql);
                    $itemStmt->execute([
                        $id,
                        $item['product_name'],
                        $item['product_code'] ?? null,
                        $item['quantity'],
                        $item['unit_price'],
                        $itemTotal,
                        $item['description'] ?? null
                    ]);
                }

                // Atualizar total do pedido
                $updateSql = "UPDATE orders SET total_amount = ? WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([$totalAmount, $id]);
            }

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erro ao atualizar pedido: " . $e->getMessage());
            throw $e;
        }
    }

    public function duplicate($id, $userId) {
        $order = $this->findWithItems($id);
        
        if (!$order || $order['user_id'] != $userId) {
            return false;
        }

        // Preparar dados para o novo pedido
        $newData = [
            'user_id' => $userId,
            'customer_name' => $order['customer_name'] . ' (Cópia)',
            'customer_email' => $order['customer_email'],
            'customer_phone' => $order['customer_phone'],
            'customer_address' => $order['customer_address'],
            'status' => 'pending', // Novo pedido começa como pendente
            'order_date' => date('Y-m-d'),
            'delivery_date' => null,
            'notes' => $order['notes'],
            'total_amount' => $order['total_amount']
        ];

        // Preparar itens
        $newItems = [];
        if (!empty($order['items'])) {
            foreach ($order['items'] as $item) {
                $newItems[] = [
                    'product_name' => $item['product_name'],
                    'product_code' => $item['product_code'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'description' => $item['description']
                ];
            }
        }

        return $this->create($newData, $newItems);
    }

    public function delete($id, $userId) {
        // Exclusão lógica do pedido e itens
        $this->db->beginTransaction();

        try {
            $sql = "UPDATE orders SET deleted_at = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $userId]);

            // Também marcar itens como excluídos
            $itemsSql = "UPDATE order_items SET deleted_at = NOW() WHERE order_id = ?";
            $itemsStmt = $this->db->prepare($itemsSql);
            $itemsStmt->execute([$id]);

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function restore($id, $userId) {
        // Restaurar pedido
        $sql = "UPDATE orders SET deleted_at = NULL WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    public function getOrderStats($userId) {
        $sql = "SELECT 
                status,
                COUNT(*) as count,
                SUM(total_amount) as total_value
                FROM orders 
                WHERE user_id = ? AND deleted_at IS NULL
                GROUP BY status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $stats = $stmt->fetchAll();

        // Formatar status para exibição
        $statusLabels = [
            'pending' => 'Pendente',
            'processing' => 'Processando',
            'shipped' => 'Enviado',
            'delivered' => 'Entregue',
            'cancelled' => 'Cancelado'
        ];

        foreach ($stats as &$stat) {
            $stat['status_label'] = $statusLabels[$stat['status']] ?? $stat['status'];
            $stat['total_value_formatted'] = 'R$ ' . number_format($stat['total_value'], 2, ',', '.');
        }

        return $stats;
    }

    public function getMonthlySummary($userId, $months = 6) {
        $sql = "SELECT 
                DATE_FORMAT(order_date, '%Y-%m') as month,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount
                FROM orders 
                WHERE user_id = ? 
                AND deleted_at IS NULL
                AND order_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY month DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $months]);
        return $stmt->fetchAll();
    }

    private function generateOrderNumber() {
        $prefix = 'PED' . date('Ym');
        $sql = "SELECT COUNT(*) as count FROM orders WHERE order_number LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch();
        $nextNumber = ($result['count'] ?? 0) + 1;
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}