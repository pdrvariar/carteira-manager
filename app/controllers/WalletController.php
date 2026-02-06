<?php
// app/controllers/WalletController.php

class WalletController {
    private $walletModel;
    private $params;

    public function __construct($params = []) {
        $this->walletModel = new Wallet();
        $this->params = $params;
        Session::start();
    }

    public function index() {
        Auth::checkAuthentication();

        $userId = Auth::getCurrentUserId();
        $wallets = $this->walletModel->getUserWallets($userId, true);

        require_once __DIR__ . '/../views/wallet/index.php';
    }

    public function create() {
        Auth::checkAuthentication();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('wallet/create'));
            }

            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? null,
                'status' => $_POST['status'] ?? 'active'
            ];

            if ($this->walletModel->create($data)) {
                Session::setFlash('success', 'Carteira criada com sucesso!');
                header('Location: /index.php?url=' . obfuscateUrl('wallet'));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao criar carteira.');
            }
        }

        require_once __DIR__ . '/../views/wallet/create.php';
    }

    public function view() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if (!$id) {
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        $wallet = $this->walletModel->findById($id);

        // Verificar permissões
        if (!$wallet || $wallet['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        require_once __DIR__ . '/../views/wallet/view.php';
    }

    public function edit() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        $wallet = $this->walletModel->findById($id);

        // Verificar permissões
        if (!$wallet || $wallet['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        require_once __DIR__ . '/../views/wallet/edit.php';
    }

    public function update() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Segurança: Token inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('wallet/edit/' . $id));
            }

            $data = [
                'user_id' => $_SESSION['user_id'],
                'name' => $_POST['name'],
                'description' => $_POST['description'] ?? null,
                'status' => $_POST['status'] ?? 'active'
            ];

            if ($this->walletModel->update($id, $data)) {
                Session::setFlash('success', 'Carteira atualizada com sucesso!');
                header('Location: /index.php?url=' . obfuscateUrl('wallet/view/' . $id));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao atualizar carteira.');
                header('Location: /index.php?url=' . obfuscateUrl('wallet/edit/' . $id));
                exit;
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('wallet'));
        exit;
    }

    public function delete() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($id) {
            $wallet = $this->walletModel->findById($id);

            if ($wallet && $wallet['user_id'] == $_SESSION['user_id']) {
                if ($this->walletModel->delete($id, $_SESSION['user_id'])) {
                    Session::setFlash('success', 'Carteira desativada com sucesso.');
                } else {
                    Session::setFlash('error', 'Erro ao desativar carteira.');
                }
            } else {
                Session::setFlash('error', 'Carteira não encontrada ou acesso negado.');
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('wallet'));
        exit;
    }
}
?>