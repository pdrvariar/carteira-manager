<?php
// app/controllers/RebalanceController.php

namespace App\Controllers;

use App\Models\Wallet;
use App\Services\RebalanceService;
use App\Core\Session;
use App\Core\Auth;

class RebalanceController {
    private $walletModel;
    private $rebalanceService;
    private $params;

    public function __construct($params = []) {
        $this->walletModel = new Wallet();
        $this->rebalanceService = new RebalanceService();
        $this->params = $params;
        Session::start();
    }

    public function index() {
        Auth::checkAuthentication();
        $walletId = $this->params['wallet_id'] ?? null;

        if (!$walletId) {
            Session::setFlash('error', 'Carteira não especificada.');
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        $wallet = $this->walletModel->findById($walletId);

        if (!$wallet || $wallet['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        require_once __DIR__ . '/../views/rebalance/index.php';
    }

    public function calculate() {
        Auth::checkAuthentication();
        $walletId = $this->params['wallet_id'] ?? null;

        if (!$walletId) {
            Session::setFlash('error', 'Carteira não especificada.');
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        $wallet = $this->walletModel->findById($walletId);

        if (!$wallet || $wallet['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Token de segurança inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
            }

            // Obter composição do formulário
            $newComposition = [];
            $availableCash = (float)str_replace(',', '.', $_POST['available_cash'] ?? '0');

            if (isset($_POST['tickers']) && is_array($_POST['tickers'])) {
                foreach ($_POST['tickers'] as $index => $ticker) {
                    $ticker = trim($ticker);
                    $allocation = (float)str_replace(',', '.', $_POST['allocations'][$index] ?? '0');

                    if (!empty($ticker) && $allocation > 0) {
                        $newComposition[$ticker] = $allocation;
                    }
                }
            }

            if (empty($newComposition)) {
                Session::setFlash('error', 'Informe pelo menos uma ação com percentual alvo.');
                header('Location: /index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
                exit;
            }

            // Calcular rebalanceamento
            $result = $this->rebalanceService->calculateRebalance(
                $walletId,
                $newComposition,
                $availableCash
            );

            require_once __DIR__ . '/../views/rebalance/result.php';
            exit;
        }

        header('Location: /index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
        exit;
    }

    public function execute() {
        Auth::checkAuthentication();
        $walletId = $this->params['wallet_id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $walletId) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Token de segurança inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
            }

            // Em uma implementação real, aqui você processaria as transações
            // Por enquanto, apenas simulamos
            $result = $this->rebalanceService->executeRebalance(
                $walletId,
                json_decode($_POST['instructions'] ?? '[]', true),
                $_SESSION['user_id']
            );

            if ($result['success']) {
                Session::setFlash('success', 'Rebalanceamento aplicado com sucesso!');
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao aplicar rebalanceamento.');
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
        exit;
    }
}