<?php
// app/controllers/RebalanceController.php (atualizado)

namespace App\Controllers;

use App\Models\Wallet;
use App\Models\WalletStock;
use App\Services\RebalanceService;
use App\Core\Session;
use App\Core\Auth;

class RebalanceController {
    private $walletModel;
    private $walletStockModel;
    private $rebalanceService;
    private $params;

    public function __construct($params = []) {
        $this->walletModel = new Wallet();
        $this->walletStockModel = new WalletStock();
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

        // Calcular valor total atual da carteira
        $currentStocks = $this->walletStockModel->findByWalletId($walletId);
        $currentTotalValue = 0;

        foreach ($currentStocks as $stock) {
            $currentTotalValue += $stock['total_cost'];
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
            $newTotalValue = (float)str_replace(['.', ','], ['', '.'], $_POST['new_total_value'] ?? '0');

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
                $newTotalValue
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

            // Decodificar instruções do formulário
            $instructions = json_decode($_POST['instructions'] ?? '[]', true);

            if (empty($instructions)) {
                Session::setFlash('error', 'Instruções de rebalanceamento inválidas.');
                header('Location: /index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
                exit;
            }

            // Executar rebalanceamento
            $result = $this->rebalanceService->executeRebalance(
                $walletId,
                $instructions,
                $_SESSION['user_id']
            );

            if ($result['success']) {
                Session::setFlash('success', $result['message']);
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
                exit;
            } else {
                Session::setFlash('error', $result['message']);
                header('Location: /index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
                exit;
            }
        }

        header('Location: /index.php?url=' . obfuscateUrl('rebalance/index/' . $walletId));
        exit;
    }
}