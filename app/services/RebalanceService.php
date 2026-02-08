<?php
// app/services/RebalanceService.php (atualizado)

namespace App\Services;

use App\Core\Database;
use App\Models\WalletStock;

class RebalanceService {
    private $db;
    private $walletStockModel;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->walletStockModel = new WalletStock();
    }

    /**
     * Calcula as transações necessárias para rebalancear a carteira
     *
     * @param int $walletId ID da carteira
     * @param array $newComposition Nova composição [ticker => percentual]
     * @param float $newTotalValue Valor total da nova composição
     * @return array Instruções de rebalanceamento
     */
    public function calculateRebalance($walletId, $newComposition, $newTotalValue) {
        // 1. Obter carteira atual
        $currentStocks = $this->walletStockModel->findByWalletId($walletId);

        // 2. Obter preços atuais de mercado
        $currentPrices = $this->getCurrentPrices($currentStocks);

        // 3. Calcular valor total da carteira ATUAL
        $currentTotalValue = $this->calculateTotalValue($currentStocks, $currentPrices);

        // 4. Normalizar percentuais da nova composição para somar 100%
        $newComposition = $this->normalizeAllocation($newComposition);

        // 5. Calcular valores alvo para cada ação baseado no NOVO valor total
        $targetValues = $this->calculateTargetValues($newComposition, $newTotalValue);

        // 6. Calcular valores atuais
        $currentValues = $this->calculateCurrentValues($currentStocks, $currentPrices);

        // 7. Gerar instruções de rebalanceamento
        $instructions = $this->generateInstructions(
            $currentStocks,
            $currentPrices,
            $currentValues,
            $targetValues,
            $currentTotalValue,
            $newTotalValue
        );

        // 8. Calcular métricas
        $metrics = $this->calculateMetrics($instructions, $currentTotalValue, $newTotalValue);

        return [
            'instructions' => $instructions,
            'metrics' => $metrics,
            'current_composition' => $currentValues,
            'target_composition' => $targetValues,
            'current_total_value' => $currentTotalValue,
            'new_total_value' => $newTotalValue,
            'value_difference' => $newTotalValue - $currentTotalValue
        ];
    }

    /**
     * Obter preços atuais das ações (tanto lote cheio quanto fracionário)
     */
    private function getCurrentPrices($stocks) {
        $apiClient = new OPLabAPIClient();
        $prices = [];

        foreach ($stocks as $stock) {
            $ticker = $stock['ticker'];
            try {
                // Tenta obter preço do lote cheio
                $price = $apiClient->getPrice($ticker);
                $prices[$ticker] = $price ?: $stock['last_price'];

                // Também tenta obter preço do fracionário se disponível
                $fractionalTicker = $this->getFractionalTicker($ticker);
                if ($fractionalTicker) {
                    $fractionalPrice = $apiClient->getPrice($fractionalTicker);
                    if ($fractionalPrice) {
                        $prices[$fractionalTicker] = $fractionalPrice;
                    }
                }
            } catch (\Exception $e) {
                $prices[$ticker] = $stock['last_price'] ?? $stock['average_cost_per_share'];
            }
        }

        return $prices;
    }

    /**
     * Calcular valor total da carteira
     */
    private function calculateTotalValue($stocks, $prices) {
        $total = 0;

        foreach ($stocks as $stock) {
            $price = $prices[$stock['ticker']] ?? $stock['average_cost_per_share'];
            $total += $stock['quantity'] * $price;
        }

        return $total;
    }

    /**
     * Normalizar alocações para somar 100%
     */
    private function normalizeAllocation($allocation) {
        $total = array_sum($allocation);

        if ($total == 0) {
            return $allocation;
        }

        $normalized = [];
        foreach ($allocation as $ticker => $percent) {
            $normalized[$ticker] = ($percent / $total) * 100;
        }

        return $normalized;
    }

    /**
     * Calcular valores alvo para cada ação
     */
    private function calculateTargetValues($newComposition, $newTotalValue) {
        $targetValues = [];

        foreach ($newComposition as $ticker => $percent) {
            $targetValues[$ticker] = [
                'target_percent' => $percent,
                'target_value' => ($newTotalValue * $percent) / 100
            ];
        }

        return $targetValues;
    }

    /**
     * Calcular valores atuais de cada ação
     */
    private function calculateCurrentValues($stocks, $prices) {
        $currentValues = [];

        foreach ($stocks as $stock) {
            $price = $prices[$stock['ticker']] ?? $stock['average_cost_per_share'];
            $currentValues[$stock['ticker']] = [
                'current_quantity' => $stock['quantity'],
                'current_price' => $price,
                'current_value' => $stock['quantity'] * $price,
                'average_cost' => $stock['average_cost_per_share'],
                'stock_id' => $stock['id']
            ];
        }

        return $currentValues;
    }

    /**
     * Gerar instruções passo a passo
     */
    private function generateInstructions($currentStocks, $currentPrices, $currentValues, $targetValues, $currentTotalValue, $newTotalValue) {
        $instructions = [
            'sell' => [],
            'buy' => [],
            'keep' => [],
            'steps' => []
        ];

        $valueDifference = $newTotalValue - $currentTotalValue;

        // FASE 1: Vender ações que não estão na nova composição
        foreach ($currentValues as $ticker => $data) {
            if (!isset($targetValues[$ticker])) {
                $instructions['sell'][] = [
                    'ticker' => $ticker,
                    'action' => 'sell_all',
                    'quantity' => $data['current_quantity'],
                    'current_value' => $data['current_value'],
                    'reason' => 'Ação removida da nova composição',
                    'price' => $data['current_price'],
                    'total_sale' => $data['current_value']
                ];
            }
        }

        // Calcular valor líquido após vendas de ações removidas
        $salesFromRemoved = array_sum(array_column($instructions['sell'], 'total_sale'));
        $availableForReallocation = $currentTotalValue - $salesFromRemoved + $valueDifference;

        // FASE 2: Ajustar ações que permanecem na carteira
        foreach ($targetValues as $ticker => $target) {
            if (isset($currentValues[$ticker])) {
                $current = $currentValues[$ticker];
                $currentValue = $current['current_value'];
                $targetValue = $target['target_value'];
                $price = $current['current_price'];

                $difference = $targetValue - $currentValue;

                if (abs($difference) < 1) { // Menos de R$ 1 de diferença
                    $instructions['keep'][] = [
                        'ticker' => $ticker,
                        'action' => 'keep',
                        'current_quantity' => $current['current_quantity'],
                        'reason' => 'Alocação já está dentro do tolerável',
                        'current_value' => $currentValue,
                        'target_value' => $targetValue
                    ];
                } elseif ($difference < 0) {
                    // Precisamos vender (valor atual > valor alvo)
                    $quantityToSell = ceil(abs($difference) / $price);

                    if ($quantityToSell > 0) {
                        // Calcular divisão entre lotes cheios e fracionários
                        $split = $this->splitIntoFullAndFractional($ticker, $quantityToSell);

                        $instructions['sell'][] = [
                            'ticker' => $ticker,
                            'action' => 'sell_partial',
                            'quantity' => $quantityToSell,
                            'full_lot_quantity' => $split['full'],
                            'fractional_quantity' => $split['fractional'],
                            'current_quantity' => $current['current_quantity'],
                            'remaining_quantity' => $current['current_quantity'] - $quantityToSell,
                            'reason' => sprintf(
                                'Reduzir alocação de R$ %.2f para R$ %.2f',
                                $currentValue,
                                $targetValue
                            ),
                            'price' => $price,
                            'total_sale' => $quantityToSell * $price,
                            'full_lot_ticker' => $ticker,
                            'fractional_ticker' => $this->getFractionalTicker($ticker)
                        ];
                    }
                } else {
                    // Precisamos comprar (valor atual < valor alvo)
                    $quantityNeeded = ceil($difference / $price);

                    // Calcular divisão entre lotes cheios e fracionários
                    $split = $this->splitIntoFullAndFractional($ticker, $quantityNeeded);

                    $instructions['buy'][] = [
                        'ticker' => $ticker,
                        'action' => 'buy_additional',
                        'quantity_needed' => $quantityNeeded,
                        'full_lot_quantity' => $split['full'],
                        'fractional_quantity' => $split['fractional'],
                        'current_quantity' => $current['current_quantity'],
                        'value_needed' => $difference,
                        'reason' => sprintf(
                            'Aumentar alocação de R$ %.2f para R$ %.2f',
                            $currentValue,
                            $targetValue
                        ),
                        'price' => $price,
                        'total_cost' => $quantityNeeded * $price,
                        'full_lot_ticker' => $ticker,
                        'fractional_ticker' => $this->getFractionalTicker($ticker),
                        'priority' => 2 // Prioridade média
                    ];
                }
            } else {
                // Nova ação que não está na carteira atual
                $price = $currentPrices[$ticker] ?? $this->estimatePrice($ticker);
                $quantityNeeded = ceil($target['target_value'] / $price);

                // Calcular divisão entre lotes cheios e fracionários
                $split = $this->splitIntoFullAndFractional($ticker, $quantityNeeded);

                $instructions['buy'][] = [
                    'ticker' => $ticker,
                    'action' => 'buy_new',
                    'quantity_needed' => $quantityNeeded,
                    'full_lot_quantity' => $split['full'],
                    'fractional_quantity' => $split['fractional'],
                    'value_needed' => $target['target_value'],
                    'reason' => 'Nova ação na composição',
                    'price' => $price,
                    'total_cost' => $quantityNeeded * $price,
                    'full_lot_ticker' => $ticker,
                    'fractional_ticker' => $this->getFractionalTicker($ticker),
                    'priority' => 1 // Prioridade alta
                ];
            }
        }

        // FASE 3: Ordenar compras por prioridade e disponibilidade
        $instructions = $this->prioritizeBuys($instructions, $availableForReallocation);

        // FASE 4: Gerar passos sequenciais
        $instructions['steps'] = $this->generateSteps($instructions);

        return $instructions;
    }

    /**
     * Dividir quantidade em lotes cheios e fracionários
     */
    private function splitIntoFullAndFractional($ticker, $quantity) {
        // Se não for ação de lote cheio, tudo é fracionário
        if (!$this->isFullLotStock($ticker)) {
            return [
                'full' => 0,
                'fractional' => $quantity
            ];
        }

        $fullLots = floor($quantity / 100);
        $fractional = $quantity % 100;

        return [
            'full' => $fullLots * 100,
            'fractional' => $fractional
        ];
    }

    /**
     * Obter ticker do fracionário
     */
    private function getFractionalTicker($ticker) {
        // Para ações que terminam com número, adiciona 'F' no final
        // Exemplo: PETR4 -> PETR4F, ITUB4 -> ITUB4F
        if (preg_match('/^[A-Z]{4}\d$/', $ticker)) {
            return $ticker . 'F';
        }
        return null;
    }

    /**
     * Verificar se a ação opera em lotes padrão (100 unidades)
     */
    private function isFullLotStock($ticker) {
        // Ações que normalmente operam em lotes de 100
        // Na prática, isso poderia vir de uma tabela de configuração
        $fullLotPatterns = ['/^[A-Z]{4}\d$/', '/^[A-Z]{4}3$/', '/^[A-Z]{4}4$/'];

        foreach ($fullLotPatterns as $pattern) {
            if (preg_match($pattern, $ticker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Priorizar ordens de compra baseado na disponibilidade
     */
    private function prioritizeBuys($instructions, &$availableForReallocation) {
        // Ordenar compras por prioridade (1 = mais alta)
        usort($instructions['buy'], function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // Calcular quantidades viáveis para compra
        foreach ($instructions['buy'] as &$buy) {
            // Verificar se há fundos suficientes
            if ($availableForReallocation >= $buy['total_cost']) {
                $buy['feasible'] = true;
                $buy['execution'] = 'full';
                $availableForReallocation -= $buy['total_cost'];
            } elseif ($availableForReallocation > 0) {
                // Compra parcial com fundos disponíveis
                $maxQuantity = floor($availableForReallocation / $buy['price']);

                // Recalcular divisão para a quantidade parcial
                $split = $this->splitIntoFullAndFractional($buy['ticker'], $maxQuantity);

                if ($split['full'] + $split['fractional'] > 0) {
                    $buy['feasible'] = true;
                    $buy['execution'] = 'partial';
                    $buy['quantity_executed'] = $split['full'] + $split['fractional'];
                    $buy['full_lot_executed'] = $split['full'];
                    $buy['fractional_executed'] = $split['fractional'];
                    $buy['partial_cost'] = $buy['quantity_executed'] * $buy['price'];
                    $availableForReallocation -= $buy['partial_cost'];
                } else {
                    $buy['feasible'] = false;
                    $buy['execution'] = 'insufficient_funds';
                }
            } else {
                $buy['feasible'] = false;
                $buy['execution'] = 'no_funds';
            }
        }

        return $instructions;
    }

    /**
     * Gerar passos sequenciais para o usuário
     */
    private function generateSteps($instructions) {
        $steps = [];
        $stepNumber = 1;

        // Passo 1: Vender ações que serão removidas
        if (!empty($instructions['sell'])) {
            foreach ($instructions['sell'] as $sell) {
                $steps[] = [
                    'step' => $stepNumber++,
                    'action' => 'sell',
                    'description' => $this->getSellDescription($sell),
                    'details' => $sell,
                    'icon' => 'bi-cash',
                    'color' => 'danger'
                ];
            }
        }

        // Passo 2: Ajustar ações existentes (vender excesso)
        $sellAdjustments = array_filter($instructions['sell'], function($s) {
            return $s['action'] === 'sell_partial';
        });

        foreach ($sellAdjustments as $sell) {
            $steps[] = [
                'step' => $stepNumber++,
                'action' => 'adjust',
                'description' => $this->getAdjustDescription($sell),
                'details' => $sell,
                'icon' => 'bi-arrow-down-right',
                'color' => 'warning'
            ];
        }

        // Passo 3: Comprar ações novas
        $newBuys = array_filter($instructions['buy'], function($b) {
            return $b['action'] === 'buy_new' && $b['feasible'];
        });

        foreach ($newBuys as $buy) {
            $steps[] = [
                'step' => $stepNumber++,
                'action' => 'buy_new',
                'description' => $this->getBuyDescription($buy),
                'details' => $buy,
                'icon' => 'bi-plus-circle',
                'color' => 'success'
            ];
        }

        // Passo 4: Comprar adicional em ações existentes
        $additionalBuys = array_filter($instructions['buy'], function($b) {
            return $b['action'] === 'buy_additional' && $b['feasible'];
        });

        foreach ($additionalBuys as $buy) {
            $steps[] = [
                'step' => $stepNumber++,
                'action' => 'buy_additional',
                'description' => $this->getBuyDescription($buy),
                'details' => $buy,
                'icon' => 'bi-arrow-up-right',
                'color' => 'info'
            ];
        }

        // Passo 5: Manter ações inalteradas
        if (!empty($instructions['keep'])) {
            $steps[] = [
                'step' => $stepNumber++,
                'action' => 'review',
                'description' => 'Revisar ações mantidas inalteradas',
                'details' => $instructions['keep'],
                'icon' => 'bi-check-circle',
                'color' => 'secondary'
            ];
        }

        return $steps;
    }

    /**
     * Gerar descrição para venda
     */
    private function getSellDescription($sell) {
        if ($sell['action'] === 'sell_all') {
            return sprintf(
                'Vender todas as %d cotas de %s por R$ %.2f cada (Total: R$ %.2f)',
                $sell['quantity'],
                $sell['ticker'],
                $sell['price'],
                $sell['total_sale']
            );
        } else {
            $desc = sprintf(
                'Vender %d cotas de %s (ficará com %d) por R$ %.2f cada',
                $sell['quantity'],
                $sell['ticker'],
                $sell['remaining_quantity'],
                $sell['price']
            );

            // Adicionar detalhes sobre lotes cheios/fracionários
            if ($sell['full_lot_quantity'] > 0) {
                $desc .= sprintf(' - %d em lote cheio', $sell['full_lot_quantity']);
            }
            if ($sell['fractional_quantity'] > 0) {
                $desc .= sprintf(' - %d no fracionário', $sell['fractional_quantity']);
            }

            return $desc;
        }
    }

    /**
     * Gerar descrição para ajuste
     */
    private function getAdjustDescription($sell) {
        $desc = sprintf(
            'Ajustar %s: vender %d cotas para atingir alocação alvo',
            $sell['ticker'],
            $sell['quantity']
        );

        if ($sell['full_lot_quantity'] > 0 || $sell['fractional_quantity'] > 0) {
            $desc .= ' (';
            if ($sell['full_lot_quantity'] > 0) {
                $desc .= sprintf('%d lote cheio', $sell['full_lot_quantity'] / 100);
            }
            if ($sell['fractional_quantity'] > 0) {
                if ($sell['full_lot_quantity'] > 0) $desc .= ' + ';
                $desc .= sprintf('%d fracionário', $sell['fractional_quantity']);
            }
            $desc .= ')';
        }

        return $desc;
    }

    /**
     * Gerar descrição para compra
     */
    private function getBuyDescription($buy) {
        $desc = '';

        if ($buy['execution'] === 'partial') {
            $desc = sprintf(
                'Comprar %d cotas de %s (parcialmente - necessárias: %d) por R$ %.2f cada',
                $buy['quantity_executed'],
                $buy['ticker'],
                $buy['quantity_needed'],
                $buy['price']
            );
        } else {
            $desc = sprintf(
                'Comprar %d cotas de %s por R$ %.2f cada (Total: R$ %.2f)',
                $buy['quantity_needed'],
                $buy['ticker'],
                $buy['price'],
                $buy['total_cost']
            );
        }

        // Adicionar detalhes sobre lotes cheios/fracionários
        if ($buy['full_lot_quantity'] > 0 || $buy['fractional_quantity'] > 0) {
            $desc .= ' - Composição: ';
            if ($buy['full_lot_quantity'] > 0) {
                $desc .= sprintf('%d em lote cheio (%s)', $buy['full_lot_quantity'], $buy['full_lot_ticker']);
            }
            if ($buy['fractional_quantity'] > 0) {
                if ($buy['full_lot_quantity'] > 0) $desc .= ' + ';
                $desc .= sprintf('%d no fracionário (%s)', $buy['fractional_quantity'], $buy['fractional_ticker'] ?? $buy['ticker'].'F');
            }
        }

        return $desc;
    }

    /**
     * Calcular métricas do rebalanceamento
     */
    private function calculateMetrics($instructions, $currentTotalValue, $newTotalValue) {
        $totalSales = 0;
        $totalPurchases = 0;

        foreach ($instructions['sell'] as $sell) {
            $totalSales += $sell['total_sale'] ?? 0;
        }

        foreach ($instructions['buy'] as $buy) {
            if ($buy['feasible']) {
                $totalPurchases += $buy['total_cost'] ?? $buy['partial_cost'] ?? 0;
            }
        }

        $valueDifference = $newTotalValue - $currentTotalValue;
        $netFlow = $totalPurchases - $totalSales;

        return [
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'net_cash_flow' => $netFlow,
            'value_difference' => $valueDifference,
            'transactions_count' => count($instructions['sell']) + count($instructions['buy']),
            'execution_efficiency' => $newTotalValue > 0 ? (($newTotalValue - abs($valueDifference - $netFlow)) / $newTotalValue) * 100 : 0
        ];
    }

    /**
     * Estimar preço para ações novas
     */
    private function estimatePrice($ticker) {
        try {
            $apiClient = new OPLabAPIClient();
            return $apiClient->getPrice($ticker) ?? 50.00;
        } catch (\Exception $e) {
            return 50.00;
        }
    }
    /**
     * Aplicar rebalanceamento (executar transações)
     */
    /**
     * Aplicar rebalanceamento (executar transações)
     */
    public function executeRebalance($walletId, $instructions, $targetComposition, $userId) {
        try {
            // Iniciar transação no banco de dados
            $this->db->beginTransaction();

            // 1. Processar vendas
            foreach ($instructions['sell'] as $sellInstruction) {
                $this->processSale($sellInstruction, $walletId, $userId);
            }

            // 2. Processar compras
            foreach ($instructions['buy'] as $buyInstruction) {
                if ($buyInstruction['feasible'] ?? false) {
                    $this->processPurchase($buyInstruction, $walletId, $userId);
                }
            }

            // 3. Atualizar targets (percentuais de alocação) de todas as ações
            $this->updateTargetAllocations($walletId, $targetComposition);

            // 4. Atualizar composição da carteira
            $this->updateWalletComposition($walletId);

            // 5. Registrar log do rebalanceamento
            $logId = $this->logRebalancement($walletId, $userId, $instructions);

            // 6. Validar soma dos targets
            $this->validateTargetSum($walletId);

            // Commit da transação
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Rebalanceamento aplicado com sucesso!',
                'log_id' => $logId,
                'summary' => [
                    'transactions_executed' => count($instructions['sell']) + count(array_filter($instructions['buy'], fn($b) => $b['feasible'] ?? false)),
                    'timestamp' => date('Y-m-d H:i:s'),
                    'wallet_id' => $walletId
                ]
            ];

        } catch (\Exception $e) {
            // Rollback em caso de erro
            $this->db->rollBack();

            error_log("Erro ao executar rebalanceamento: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao aplicar rebalanceamento: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verificar se a soma dos targets está próxima de 100%
     */
    private function validateTargetSum($walletId) {
        $sql = "SELECT SUM(target_allocation) as total FROM wallet_stocks WHERE wallet_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId]);
        $result = $stmt->fetch();

        $total = $result['total'] ?? 0;
        $totalPercent = $total * 100;

        if (abs($totalPercent - 100) > 1) { // Tolerância de 1%
            error_log("Aviso: Soma dos targets da carteira $walletId está em " . number_format($totalPercent, 2) . "%");
        }

        return $totalPercent;
    }

    /**
     * Atualizar targets (percentuais de alocação) de todas as ações
     */
    private function updateTargetAllocations($walletId, $targetComposition) {
        if (empty($targetComposition)) {
            error_log("Aviso: targetComposition vazio para carteira $walletId");
            return;
        }

        foreach ($targetComposition as $ticker => $data) {
            if (!isset($data['target_percent'])) {
                error_log("Aviso: target_percent não definido para $ticker");
                continue;
            }

            // Converter percentual para decimal (25% → 0.25)
            $targetAllocation = $data['target_percent'] / 100;

            // Verificar se a ação existe na carteira
            $sql = "SELECT id FROM wallet_stocks WHERE wallet_id = ? AND ticker = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$walletId, $ticker]);
            $stock = $stmt->fetch();

            if ($stock) {
                // Atualizar target_allocation
                $sql = "UPDATE wallet_stocks 
                    SET target_allocation = ?, updated_at = NOW() 
                    WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$targetAllocation, $stock['id']]);

                error_log("Atualizado target_allocation de $ticker para $targetAllocation (".$data['target_percent']."%)");
            } else {
                error_log("Aviso: Ação $ticker não encontrada na carteira $walletId para atualizar target_allocation");
            }
        }
    }

    /**
     * Processar venda de ações
     */
    private function processSale($sellInstruction, $walletId, $userId) {
        if ($sellInstruction['action'] === 'sell_all') {
            // Remover ação completamente da carteira
            $sql = "DELETE FROM wallet_stocks WHERE wallet_id = ? AND ticker = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$walletId, $sellInstruction['ticker']]);

        } elseif ($sellInstruction['action'] === 'sell_partial') {
            // Reduzir quantidade da ação
            $sql = "UPDATE wallet_stocks 
                SET quantity = quantity - ?, 
                    total_cost = (quantity - ?) * average_cost_per_share,
                    updated_at = NOW()
                WHERE wallet_id = ? AND ticker = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $sellInstruction['quantity'],
                $sellInstruction['quantity'],
                $walletId,
                $sellInstruction['ticker']
            ]);
        }
    }

    /**
     * Processar compra de ações
     */
    /**
     * Processar compra de ações
     */
    private function processPurchase($buyInstruction, $walletId, $userId) {
        $quantity = $buyInstruction['quantity_executed'] ?? $buyInstruction['quantity_needed'] ?? 0;

        if ($quantity <= 0) {
            return;
        }

        // Verificar se a ação já existe na carteira
        $sql = "SELECT id, quantity, average_cost_per_share, target_allocation 
            FROM wallet_stocks 
            WHERE wallet_id = ? AND ticker = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId, $buyInstruction['ticker']]);
        $existingStock = $stmt->fetch();

        if ($existingStock) {
            // Atualizar ação existente - recalcular preço médio
            $oldQuantity = $existingStock['quantity'];
            $oldAverageCost = $existingStock['average_cost_per_share'];
            $newQuantity = $quantity;
            $newPrice = $buyInstruction['price'];

            // Calcular novo preço médio ponderado
            $totalCost = ($oldQuantity * $oldAverageCost) + ($newQuantity * $newPrice);
            $totalQuantity = $oldQuantity + $newQuantity;
            $newAverageCost = $totalCost / $totalQuantity;

            $sql = "UPDATE wallet_stocks 
                SET quantity = ?,
                    average_cost_per_share = ?,
                    total_cost = ?,
                    last_market_price = ?,
                    updated_at = NOW()
                WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $totalQuantity,
                $newAverageCost,
                $totalQuantity * $newAverageCost,
                $buyInstruction['price'],
                $existingStock['id']
            ]);
        } else {
            // Inserir nova ação
            $totalCost = $quantity * $buyInstruction['price'];

            // NOTA: target_allocation será atualizado posteriormente no updateTargetAllocations
            $sql = "INSERT INTO wallet_stocks 
                (wallet_id, ticker, quantity, average_cost_per_share, 
                 total_cost, last_market_price, target_allocation) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $walletId,
                $buyInstruction['ticker'],
                $quantity,
                $buyInstruction['price'],
                $totalCost,
                $buyInstruction['price'],
                0.0 // Será atualizado posteriormente no updateTargetAllocations
            ]);
        }
    }

    /**
     * Atualizar composição da carteira após rebalanceamento
     */
    private function updateWalletComposition($walletId) {
        // 1. Recalcular totais da carteira
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
            updated_at = NOW()
            WHERE w.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId]);

        // 2. Remover ações com quantidade zero
        $sql = "DELETE FROM wallet_stocks WHERE wallet_id = ? AND quantity <= 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$walletId]);
    }

    /**
     * Registrar log do rebalanceamento
     */
    private function logRebalancement($walletId, $userId, $instructions) {
        $sql = "INSERT INTO rebalance_logs 
            (wallet_id, user_id, instructions, executed_at) 
            VALUES (?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $walletId,
            $userId,
            json_encode($instructions)
        ]);

        return $this->db->lastInsertId();
    }

}