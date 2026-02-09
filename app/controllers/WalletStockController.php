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

        // CALCULAR SOMA DAS ALOCAÇÕES
        $totalAllocation = 0;
        foreach ($stocks as $stock) {
            $totalAllocation += $stock['target_allocation'];
        }
        $totalAllocationPercent = $totalAllocation * 100;

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

// app/controllers/WalletStockController.php (adicionar após o método updatePrices)

    public function importCsv() {
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
            // Detectar caso de post_max_size excedido: PHP descarta $_POST e $_FILES
            $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
            if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
                $postMax = ini_get('post_max_size');
                Session::setFlash('error', 'O tamanho total do envio excede o limite post_max_size do PHP (' . $postMax . '). Reduza o arquivo ou aumente as configurações do servidor.');
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
                exit;
            }

            if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
                Session::setFlash('error', 'Token de segurança inválido.');
                redirectBack('/index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
            }

            // Verificar se um arquivo foi enviado
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $errorCode = $_FILES['csv_file']['error'] ?? 'N/A';
                $errorMessage = 'Erro no upload do arquivo (Código: ' . $errorCode . ').';

                switch ($errorCode) {
                    case UPLOAD_ERR_INI_SIZE:
                        $errorMessage .= ' O arquivo excede o limite de tamanho definido no servidor (upload_max_filesize).';
                        break;
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage .= ' O arquivo excede o limite de tamanho definido no formulário.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage .= ' O upload foi feito apenas parcialmente.';
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $errorMessage .= ' Nenhum arquivo foi enviado.';
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                        $errorMessage .= ' Pasta temporária ausente no servidor.';
                        break;
                    case UPLOAD_ERR_CANT_WRITE:
                        $errorMessage .= ' Falha ao escrever arquivo no disco. Verifique as permissões de escrita.';
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $errorMessage .= ' Uma extensão do PHP interrompeu o upload.';
                        break;
                    default:
                        $errorMessage .= ' Erro desconhecido durante o upload.';
                }

                Session::setFlash('error', $errorMessage);
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
                exit;
            }

            // Validar extensão
            $fileTmpPath = $_FILES['csv_file']['tmp_name'];
            $fileName = $_FILES['csv_file']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileExtension !== 'csv') {
                Session::setFlash('error', 'Formato de arquivo inválido. Apenas CSV é permitido.');
                header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
                exit;
            }

            // Processar o arquivo CSV
            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            if (($handle = fopen($fileTmpPath, 'r')) !== false) {
                // Tentar ler a primeira linha para verificar cabeçalho
                $header = fgetcsv($handle, 1000, ';');

                // Remover BOM se presente no primeiro campo
                if (isset($header[0])) {
                    $bom = pack('H*', 'EFBBBF');
                    $header[0] = preg_replace("/^$bom/", '', $header[0]);
                }

                // Verificar se o cabeçalho tem o formato esperado (flexível)
                $isValidFormat = false;
                if (is_array($header) && count($header) >= 3) {
                    $firstCol = strtoupper(trim($header[0]));
                    if ($firstCol === 'TICKER' || $firstCol === 'ATIVO') {
                        $isValidFormat = true;
                    }
                }

                // Se não for o formato esperado, voltar ao início e processar sem cabeçalho
                if (!$isValidFormat) {
                    rewind($handle);
                }

                // Processar cada linha
                while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                    // Remover BOM do primeiro campo se existir
                    if (isset($data[0])) {
                        $bom = pack('H*', 'EFBBBF');
                        $data[0] = preg_replace("/^$bom/", '', $data[0]);
                    }

                    // Ignorar linhas vazias ou mal formatadas
                    if (empty($data) || (count($data) === 1 && empty(trim($data[0] ?? '')))) {
                        continue;
                    }

                    if (count($data) === 4) {
                        $ticker = trim($data[0]);
                        $quantity = (int)trim($data[1]);
                        $averageCost = (float)str_replace(',', '.', trim($data[2]));
                        $targetAllocation = (float)str_replace(',', '.', trim($data[3]));

                        // Validar dados
                        if (empty($ticker) || $quantity <= 0 || $averageCost <= 0 || $targetAllocation <= 0) {
                            $errors[] = "Linha inválida: " . implode(';', $data);
                            $errorCount++;
                            continue;
                        }

                        // Converter alocação para decimal se necessário
                        if ($targetAllocation > 1) {
                            $targetAllocation = $targetAllocation / 100;
                        }

                        $totalCost = $quantity * $averageCost;

                        $stockData = [
                            'wallet_id' => $walletId,
                            'ticker' => $ticker,
                            'quantity' => $quantity,
                            'average_cost_per_share' => $averageCost,
                            'total_cost' => $totalCost,
                            'target_allocation' => $targetAllocation
                        ];

                        // Verificar se já existe esta ação na carteira
                        $existingStock = $this->walletStockModel->findByTickerAndWallet($ticker, $walletId);

                        if ($existingStock) {
                            // Atualizar ação existente
                            if ($this->walletStockModel->update($existingStock['id'], $stockData)) {
                                $successCount++;
                            } else {
                                $errors[] = "Erro ao atualizar ação {$ticker}";
                                $errorCount++;
                            }
                        } else {
                            // Criar nova ação
                            if ($this->walletStockModel->create($stockData)) {
                                $successCount++;
                            } else {
                                $errors[] = "Erro ao adicionar ação {$ticker}";
                                $errorCount++;
                            }
                        }
                    } else {
                        $errors[] = "Linha com formato inválido: " . implode(';', $data);
                        $errorCount++;
                    }
                }
                fclose($handle);
            }

            // Atualizar totais da carteira
            $this->walletStockModel->updateWalletTotals($walletId);

            // Mostrar resultados
            if ($successCount > 0) {
                Session::setFlash('success', "{$successCount} ação(ões) importadas com sucesso!");
            }
            if ($errorCount > 0) {
                Session::setFlash('warning', "{$errorCount} erro(s) durante a importação. " . implode(' ', $errors));
            }

            header('Location: /index.php?url=' . obfuscateUrl('wallet_stocks/index/' . $walletId));
            exit;
        }

        // Se não for POST, mostrar formulário
        require_once __DIR__ . '/../views/wallet_stocks/import_csv.php';
    }
}