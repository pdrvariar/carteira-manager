<?php
// app/models/Project.php

namespace App\Models;

use App\Core\Database;

class Project {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getUserProjects($userId, $onlyActive = true) {
        $sql = "SELECT * FROM projects WHERE user_id = ?";

        if ($onlyActive) {
            $sql .= " AND deleted_at IS NULL";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $sql = "SELECT p.*, u.username, u.email 
                FROM projects p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.id = ? AND p.deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $sql = "INSERT INTO projects (user_id, name, description, project_status, project_start_date, project_end_date) 
                VALUES (?, ?, ?, ?, ?, ?)";

        // Converter string vazia para NULL
        $projectEndDate = (!empty($data['project_end_date']) && trim($data['project_end_date']) !== '')
            ? $data['project_end_date']
            : null;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['user_id'],
            $data['name'],
            $data['description'] ?? null,
            $data['project_status'] ?? 'planning',
            $data['project_start_date'],
            $projectEndDate
        ]);
    }

    public function update($id, $data) {
        $sql = "UPDATE projects SET 
                name = ?, 
                description = ?, 
                project_status = ?,
                project_start_date = ?,
                project_end_date = ?,
                updated_at = NOW()
                WHERE id = ? AND user_id = ? AND deleted_at IS NULL";

        // Converter string vazia para NULL
        $projectEndDate = (!empty($data['project_end_date']) && trim($data['project_end_date']) !== '')
            ? $data['project_end_date']
            : null;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['project_status'] ?? 'planning',
            $data['project_start_date'],
            $projectEndDate,
            $id,
            $data['user_id']
        ]);
    }

    public function delete($id, $userId) {
        // Exclusão lógica
        $sql = "UPDATE projects SET deleted_at = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    public function restore($id, $userId) {
        // Restaurar projeto
        $sql = "UPDATE projects SET deleted_at = NULL WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    public function getTotalCount($userId = null) {
        if ($userId) {
            $sql = "SELECT COUNT(*) as total FROM projects WHERE user_id = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $sql = "SELECT COUNT(*) as total FROM projects WHERE deleted_at IS NULL";
            $stmt = $this->db->query($sql);
        }
        return $stmt->fetch()['total'];
    }

    public function getProjectStats($userId) {
        $sql = "SELECT 
                project_status,
                COUNT(*) as count
                FROM projects 
                WHERE user_id = ? AND deleted_at IS NULL
                GROUP BY project_status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $stats = $stmt->fetchAll();

        // Formatar status para exibição
        $statusLabels = [
            'planning' => 'Planejamento',
            'started' => 'Em Andamento',
            'paused' => 'Pausado',
            'finished' => 'Concluído',
            'cancelled' => 'Cancelado'
        ];

        foreach ($stats as &$stat) {
            $stat['status_label'] = $statusLabels[$stat['project_status']] ?? $stat['project_status'];
        }

        return $stats;
    }
}
?>