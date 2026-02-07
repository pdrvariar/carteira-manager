<?php
// app/controllers/ProjectController.php

namespace App\Controllers;

use App\Models\Project;
use App\Core\Session;
use App\Core\Auth;

class ProjectController {
    private $projectModel;
    private $params;

    public function __construct($params = []) {
        $this->projectModel = new Project();
        $this->params = $params;
        Session::start();
    }

    public function index() {
        Auth::checkAuthentication();

        $userId = Auth::getCurrentUserId();
        $projects = $this->projectModel->getUserProjects($userId, true);
        $stats = $this->projectModel->getProjectStats($userId);

        require_once __DIR__ . '/../views/project/index.php';
    }

    public function create() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('project/create'));
            }

            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => trim($_POST['name']),
                'description' => !empty(trim($_POST['description'] ?? '')) ? trim($_POST['description']) : null,
                'project_status' => $_POST['project_status'] ?? 'planning',
                'project_start_date' => $_POST['project_start_date'],
                'project_end_date' => $_POST['project_end_date'] ?? null
            ];

            // Validar data de início
            if (empty($data['project_start_date'])) {
                Session::setFlash('error', 'A data de início é obrigatória.');
                require_once __DIR__ . '/../views/project/create.php';
                return;
            }

            if ($this->projectModel->create($data)) {
                Session::setFlash('success', 'Projeto criado com sucesso!');
                header('Location: /index.php?url=' . obfuscateUrl('project'));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao criar projeto.');
            }
        }

        require_once __DIR__ . '/../views/project/create.php';
    }

    public function view() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('project'));
            exit;
        }

        $project = $this->projectModel->findById($id);

        // Verificar permissões
        if (!$project || $project['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('project'));
            exit;
        }

        require_once __DIR__ . '/../views/project/view.php';
    }

    public function edit() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        $project = $this->projectModel->findById($id);

        // Verificar permissões
        if (!$project || $project['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('project'));
            exit;
        }

        require_once __DIR__ . '/../views/project/edit.php';
    }

    public function update() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('project/edit/' . $id));
            }

            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => trim($_POST['name']),
                'description' => !empty(trim($_POST['description'] ?? '')) ? trim($_POST['description']) : null,
                'project_status' => $_POST['project_status'] ?? 'planning',
                'project_start_date' => $_POST['project_start_date'],
                'project_end_date' => $_POST['project_end_date'] ?? null
            ];

            // Validar data de início
            if (empty($data['project_start_date'])) {
                Session::setFlash('error', 'A data de início é obrigatória.');
                header('Location: /index.php?url=' . obfuscateUrl('project/edit/' . $id));
                exit;
            }

            if ($this->projectModel->update($id, $data)) {
                Session::setFlash('success', 'Projeto atualizado com sucesso!');
                header('Location: /index.php?url=' . obfuscateUrl('project/view/' . $id));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao atualizar projeto.');
                header('Location: /index.php?url=' . obfuscateUrl('project/edit/' . $id));
                exit;
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('project'));
        exit;
    }

    public function delete() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($id) {
            $project = $this->projectModel->findById($id);

            if ($project && $project['user_id'] == $_SESSION['user_id']) {
                if ($this->projectModel->delete($id, $_SESSION['user_id'])) {
                    Session::setFlash('success', 'Projeto arquivado com sucesso.');
                } else {
                    Session::setFlash('error', 'Erro ao arquivar projeto.');
                }
            } else {
                Session::setFlash('error', 'Projeto não encontrado ou acesso negado.');
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('project'));
        exit;
    }

    public function restore() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($id) {
            $project = $this->projectModel->findById($id);

            if ($project && $project['user_id'] == $_SESSION['user_id']) {
                if ($this->projectModel->restore($id, $_SESSION['user_id'])) {
                    Session::setFlash('success', 'Projeto restaurado com sucesso.');
                } else {
                    Session::setFlash('error', 'Erro ao restaurar projeto.');
                }
            } else {
                Session::setFlash('error', 'Projeto não encontrado ou acesso negado.');
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('project'));
        exit;
    }
}
?>