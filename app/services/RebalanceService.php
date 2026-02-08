<?php
// app/services/RebalanceService.php

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
     * @param float $availableCash Caixa disponível para compras (opcional)
     * @return array Instruções de rebalanceamento
     */
    public function calculateRebalance($walletId, $newComposition, $availableCash = 0) {
        // 1. Obter carteira atual
        $currentStocks = $this->walletStockModel->findByWalletId($walletId);

        // 2. Obter preços atuais de mercado
        $currentPrices = $this->getCurrentPrices($currentStocks);

        // 3. Calcular valor total da carteira
        $totalValue = $this->calculateTotalValue($currentStocks, $currentPrices);
        $totalValue += $availableCash;

        // 4. Normalizar percentuais da nova composição para somar 100%
        $newComposition = $this->normalizeAllocation($newComposition);

        // 5. Calcular valores alvo para cada ação
        $targetValues = $this->calculateTargetValues($newComposition, $totalValue);

        // 6. Calcular valores atuais
        $currentValues = $this->calculateCurrentValues($currentStocks, $currentPrices);

        // 7. Gerar instruções de rebalanceamento
        $instructions = $this->generateInstructions(
            $currentStocks,
            $currentPrices,
            $currentValues,
            $targetValues,
            $availableCash
        );

        // 8. Calcular métricas
        $metrics = $this->calculateMetrics($instructions, $totalValue, $availableCash);

        return [
            'instructions' => $instructions,
            'metrics' => $metrics,
            'current_composition' => $currentValues,
            'target_composition' => $targetValues,
            'total_value' => $totalValue,
            'available_cash' => $availableCash
        ];
    }

    /**
     * Obter preços atuais das ações
     */
    private function getCurrentPrices($stocks) {
        $apiClient = new OPLabAPIClient();
        $prices = [];

        foreach ($stocks as $stock) {
            try {
                $price = $apiClient->getPrice($stock['ticker']);
                $prices[$stock['ticker']] = $price ?: $stock['last_price'];
            } catch (\Exception $e) {
                // Se não conseguir preço atual, usa o último preço conhecido
                $prices[$stock['ticker']] = $stock['last_price'] ?? $stock['average_cost_per_share'];
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
    private function calculateTargetValues($newComposition, $totalValue) {
        $targetValues = [];

        foreach ($newComposition as $ticker => $percent) {
            $targetValues[$ticker] = [
                'target_percent' => $percent,
                'target_value' => ($totalValue * $percent) / 100,
                'target_quantity' => 0 // Será calculado depois com o preço
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
    private function generateInstructions($currentStocks, $currentPrices, $currentValues, $targetValues, $availableCash) {
        $instructions = [
            'sell' => [],
            'buy' => [],
            'keep' => [],
            'steps' => []
        ];

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
                    'total_sale' => $data['current_quantity'] * $data['current_price']
                ];

                // Adicionar caixa disponível
                $availableCash += $data['current_value'];
            }
        }

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
                        'current_allocation' => ($currentValue / ($currentValue + $availableCash)) * 100,
                        'target_allocation' => $target['target_percent']
                    ];
                } elseif ($difference < 0) {
                    // Precisamos vender (valor atual > valor alvo)
                    $quantityToSell = abs(ceil($difference / $price));

                    // Ajustar para lotes padrão (se não for fracionária)
                    if ($this->isFullLotStock($ticker)) {
                        $quantityToSell = $this->adjustToStandardLots($quantityToSell, 'sell');
                    }

                    if ($quantityToSell > 0) {
                        $instructions['sell'][] = [
                            'ticker' => $ticker,
                            'action' => 'sell_partial',
                            'quantity' => $quantityToSell,
                            'current_quantity' => $current['current_quantity'],
                            'remaining_quantity' => $current['current_quantity'] - $quantityToSell,
                            'reason' => sprintf(
                                'Reduzir alocação de %.1f%% para %.1f%%',
                                ($currentValue / ($currentValue + $availableCash)) * 100,
                                $target['target_percent']
                            ),
                            'price' => $price,
                            'total_sale' => $quantityToSell * $price
                        ];

                        $availableCash += $quantityToSell * $price;
                    }
                } else {
                    // Precisamos comprar (valor atual < valor alvo)
                    $instructions['buy'][] = [
                        'ticker' => $ticker,
                        'action' => 'buy_additional',
                        'quantity_needed' => ceil($difference / $price),
                        'current_quantity' => $current['current_quantity'],
                        'value_needed' => $difference,
                        'reason' => sprintf(
                            'Aumentar alocação de %.1f%% para %.1f%%',
                            ($currentValue / ($currentValue + $availableCash)) * 100,
                            $target['target_percent']
                        ),
                        'price' => $price,
                        'priority' => 2 // Prioridade média
                    ];
                }
            } else {
                // Nova ação que não está na carteira atual
                $instructions['buy'][] = [
                    'ticker' => $ticker,
                    'action' => 'buy_new',
                    'quantity_needed' => 0, // Será calculado depois
                    'value_needed' => $target['target_value'],
                    'reason' => 'Nova ação na composição',
                    'price' => $currentPrices[$ticker] ?? $this->estimatePrice($ticker),
                    'priority' => 1 // Prioridade alta
                ];
            }
        }

        // FASE 3: Ordenar compras por prioridade e disponibilidade de caixa
        $instructions = $this->prioritizeBuys($instructions, $availableCash);

        // FASE 4: Gerar passos sequenciais
        $instructions['steps'] = $this->generateSteps($instructions);

        return $instructions;
    }

    /**
     * Verificar se a ação opera em lotes padrão (100 unidades)
     */
    private function isFullLotStock($ticker) {
        // Lista de ações que normalmente operam em lotes de 100
        // Na prática, isso poderia vir de uma tabela de configuração
        $fullLotStocks = ['PETR4', 'VALE3', 'ITUB4', 'BBDC4', 'BBAS3', 'SANB11'];

        // Ações fracionárias geralmente têm 'F' no final ou são ETFs
        if (strpos($ticker, 'F') !== false || strpos($ticker, '11') !== false || strpos($ticker, 'ETF') !== false) {
            return false;
        }

        return in_array($ticker, $fullLotStocks);
    }

    /**
     * Ajustar quantidade para lotes padrão
     */
    private function adjustToStandardLots($quantity, $action = 'buy') {
        $standardLot = 100;

        if ($action === 'sell') {
            // Para venda, podemos vender qualquer quantidade que seja múltiplo de 100
            // ou o restante se for menos de 100
            if ($quantity < $standardLot) {
                return $quantity; // Vende tudo se for menos de 100
            }
            return floor($quantity / $standardLot) * $standardLot;
        } else {
            // Para compra, sempre comprar em múltiplos de 100
            return ceil($quantity / $standardLot) * $standardLot;
        }
    }

    /**
     * Estimar preço para ações novas
     */
    private function estimatePrice($ticker) {
        // Tenta obter da API, senão usa um valor padrão
        try {
            $apiClient = new OPLabAPIClient();
            return $apiClient->getPrice($ticker) ?? 50.00;
        } catch (\Exception $e) {
            return 50.00; // Preço estimado padrão
        }
    }

    /**
     * Priorizar ordens de compra baseado no caixa disponível
     */
    private function prioritizeBuys($instructions, &$availableCash) {
        // Ordenar compras por prioridade (1 = mais alta)
        usort($instructions['buy'], function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        // Calcular quantidades viáveis para compra
        foreach ($instructions['buy'] as &$buy) {
            $quantityNeeded = ceil($buy['value_needed'] / $buy['price']);

            // Ajustar para lotes se necessário
            if ($this->isFullLotStock($buy['ticker'])) {
                $quantityNeeded = $this->adjustToStandardLots($quantityNeeded, 'buy');
            }

            $buy['quantity_needed'] = $quantityNeeded;
            $buy['total_cost'] = $quantityNeeded * $buy['price'];

            // Verificar se há caixa suficiente
            if ($availableCash >= $buy['total_cost']) {
                $buy['feasible'] = true;
                $buy['execution'] = 'full';
                $availableCash -= $buy['total_cost'];
            } elseif ($availableCash > 0) {
                // Compra parcial com caixa disponível
                $maxQuantity = floor($availableCash / $buy['price']);
                if ($this->isFullLotStock($buy['ticker'])) {
                    $maxQuantity = floor($maxQuantity / 100) * 100;
                }

                if ($maxQuantity > 0) {
                    $buy['feasible'] = true;
                    $buy['execution'] = 'partial';
                    $buy['quantity_executed'] = $maxQuantity;
                    $buy['partial_cost'] = $maxQuantity * $buy['price'];
                    $availableCash -= $buy['partial_cost'];
                } else {
                    $buy['feasible'] = false;
                    $buy['execution'] = 'insufficient_cash';
                }
            } else {
                $buy['feasible'] = false;
                $buy['execution'] = 'no_cash';
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
            return sprintf(
                'Vender %d cotas de %s (ficará com %d) por R$ %.2f cada',
                $sell['quantity'],
                $sell['ticker'],
                $sell['remaining_quantity'],
                $sell['price']
            );
        }
    }

    /**
     * Gerar descrição para ajuste
     */
    private function getAdjustDescription($sell) {
        return sprintf(
            'Ajustar %s: vender %d cotas para atingir alocação alvo',
            $sell['ticker'],
            $sell['quantity']
        );
    }

    /**
     * Gerar descrição para compra
     */
    private function getBuyDescription($buy) {
        if ($buy['execution'] === 'partial') {
            return sprintf(
                'Comprar %d cotas de %s (parcialmente - necessárias: %d) por R$ %.2f cada',
                $buy['quantity_executed'],
                $buy['ticker'],
                $buy['quantity_needed'],
                $buy['price']
            );
        } else {
            return sprintf(
                'Comprar %d cotas de %s por R$ %.2f cada (Total: R$ %.2f)',
                $buy['quantity_needed'],
                $buy['ticker'],
                $buy['price'],
                $buy['total_cost']
            );
        }
    }

    /**
     * Calcular métricas do rebalanceamento
     */
    private function calculateMetrics($instructions, $totalValue, $availableCash) {
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

        $remainingCash = $availableCash + $totalSales - $totalPurchases;

        return [
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'net_cash_flow' => $totalSales - $totalPurchases,
            'remaining_cash' => $remainingCash,
            'transactions_count' => count($instructions['sell']) + count($instructions['buy']),
            'efficiency' => $totalValue > 0 ? (($totalValue - abs($remainingCash)) / $totalValue) * 100 : 0
        ];
    }

    /**
     * Aplicar rebalanceamento (executar transações)
     */
    public function executeRebalance($walletId, $instructions, $userId) {
        // Esta função seria chamada após o usuário confirmar as transações
        // Aqui você implementaria a lógica para atualizar o banco de dados

        // Por enquanto, retornamos um resumo simulado
        return [
            'success' => true,
            'message' => 'Rebalanceamento simulado com sucesso',
            'summary' => [
                'transactions_executed' => count($instructions['sell']) + count($instructions['buy']),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    }
}