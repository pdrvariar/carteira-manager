<?php
// app/controllers/WalletStockController.php

namespace App\Controllers;

use App\Models\Wallet;
use App\Models\WalletStock;
use App\Services\OPLabAPIClient;
use App\Core\Session;
use App\Core\Auth;

class WalletStockController {
    private $walletModel;
    private $walletStockModel;
    private $params;

    public function __construct($params = []) {
        $this->walletModel = new Wallet();
        $this->walletStockModel = new WalletStock();
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

        $stocks = $this->walletStockModel->findByWalletId($walletId);
        $summary = $this->walletStockModel->getWalletSummary($walletId);

        require_once __DIR__ . '/../views/wallet_stocks/index.php';
    }

    public function create() {
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
                redirectBack('/index.php?url=' . obfuscateUrl('wallet_stocks/create/' . $walletId));
            }

            $quantity = (int)$_POST['quantity'];
            $averageCost = (float)str_replace(',', '.', $_POST['average_cost_per_share']);
            $totalCost = $quantity * $averageCost;
            
            // CORREÇÃO: Dividir por 100 para converter porcentagem (50) em decimal (0.50)
            $targetAllocation = (float)$_POST['target_allocation'];
            if ($targetAllocation > 1) {
                $targetAllocation = $targetAllocation / 100;
            }

            $data = [
                'wallet_id' => $walletId,
                'ticker' => $_POST['ticker'],
                'quantity' => $quantity,
                'average_cost_per_share' => $averageCost,
                'total_cost' => $totalCost,
                'target_allocation' => $targetAllocation
            ];

            if ($this->walletStockModel->create($data)) {
                Session::setFlash('success', 'Ação adicionada à carteira com sucesso!');
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao adicionar ação.');
            }
        }

        require_once __DIR__ . '/../views/wallet_stocks/create.php';
    }

    public function edit() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        $stock = $this->walletStockModel->findById($id);

        if (!$stock || $stock['user_id'] != $_SESSION['user_id']) {
            Session::setFlash('error', 'Acesso negado.');
            header('Location: /index.php?url=' . obfuscateUrl('wallet'));
            exit;
        }

        require_once __DIR__ . '/../views/wallet_stocks/edit.php';
    }

    public function update() {
        Auth::checkAuthentication();
        $id = $this->params['id'] ?? null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Token de segurança inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('wallet_stocks/edit/' . $id));
            }

            $stock = $this->walletStockModel->findById($id);
            if (!$stock || $stock['user_id'] != $_SESSION['user_id']) {
                Session::setFlash('error', 'Acesso negado.');
                header('Location: /index.php?url=' . obfuscateUrl('wallet'));
                exit;
            }

            $quantity = (int)$_POST['quantity'];
            $averageCost = (float)str_replace(',', '.', $_POST['average_cost_per_share']);
            $totalCost = $quantity * $averageCost;

            // CORREÇÃO: Dividir por 100 para converter porcentagem (50) em decimal (0.50)
            $targetAllocation = (float)$_POST['target_allocation'];
            if ($targetAllocation > 1) {
                $targetAllocation = $targetAllocation / 100;
            }

            $data = [
                'ticker' => $_POST['ticker'],
                'quantity' => $quantity,
                'average_cost_per_share' => $averageCost,
                'total_cost' => $totalCost,
                'target_allocation' => $targetAllocation
            ];

            if ($this->walletStockModel->update($id, $data)) {
                Session::setFlash('success', 'Ação atualizada com sucesso!');
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $stock['wallet_id']));
                exit;
            } else {
                Session::setFlash('error', 'Erro ao atualizar ação.');
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/edit/' . $id));
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
            $stock = $this->walletStockModel->findById($id);

            if ($stock && $stock['user_id'] == $_SESSION['user_id']) {
                if ($this->walletStockModel->delete($id)) {
                    Session::setFlash('success', 'Ação removida da carteira com sucesso.');
                } else {
                    Session::setFlash('error', 'Erro ao remover ação.');
                }

                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $stock['wallet_id']));
                exit;
            }

            Session::setFlash('error', 'Ação não encontrada ou acesso negado.');
        }

        header('Location: /index.php?url=' . obfuscateUrl('wallet'));
        exit;
    }

    public function updatePrices() {
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

        $stocks = $this->walletStockModel->findByWalletId($walletId);
        $apiClient = new OPLabAPIClient();
        $updatedPrices = [];
        $updatedCount = 0;

        foreach ($stocks as $stock) {
            try {
                $currentPrice = $apiClient->getPrice($stock['ticker']);
                if ($currentPrice !== null) {
                    $this->walletStockModel->updateMarketPrice($stock['id'], $currentPrice);
                    $updatedPrices[$stock['id']] = $currentPrice;
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                error_log("Erro ao atualizar preço de {$stock['ticker']}: " . $e->getMessage());
            }
        }

        if ($updatedCount > 0) {
            $this->walletStockModel->updateAllPricesInWallet($walletId, $updatedPrices);
            Session::setFlash('success', "Preços atualizados para {$updatedCount} ação(ões).");
        } else {
            Session::setFlash('warning', 'Nenhum preço foi atualizado. Verifique os tickers.');
        }

        header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
        exit;
    }
}