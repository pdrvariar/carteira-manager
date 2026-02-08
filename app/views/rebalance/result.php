<?php
// app/views/rebalance/result.php

use App\Core\Session;

$title = 'Resultado do Rebalanceamento: ' . htmlspecialchars($wallet['name']);
$additional_css = '
    <link rel="stylesheet" href="/css/wallet_stocks.css">
    <style>
        .step-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s ease;
        }
        .step-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }
        .action-sell { border-left-color: #dc3545 !important; }
        .action-buy { border-left-color: #198754 !important; }
        .action-keep { border-left-color: #6c757d !important; }
        .action-adjust { border-left-color: #ffc107 !important; }
        .action-review { border-left-color: #0dcaf0 !important; }
        .metric-card {
            border-radius: 12px;
            overflow: hidden;
        }
        .metric-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }
        .transaction-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border: 1px solid #e9ecef;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        .lot-breakdown {
            border-left: 3px solid #0d6efd;
            padding-left: 10px;
            margin: 5px 0;
        }
        .lot-full {
            background-color: rgba(13, 110, 253, 0.1);
            border-radius: 4px;
            padding: 2px 6px;
            margin-right: 5px;
        }
        .lot-fractional {
            background-color: rgba(32, 201, 151, 0.1);
            border-radius: 4px;
            padding: 2px 6px;
            margin-right: 5px;
        }
        .value-diff-positive {
            color: #198754;
            font-weight: bold;
        }
        .value-diff-negative {
            color: #dc3545;
            font-weight: bold;
        }
        .value-diff-neutral {
            color: #6c757d;
        }
        .lot-split {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 10px;
            margin: 5px 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
';
ob_start();
?>

    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-arrow-left-right me-2 text-primary"></i>Plano de Rebalanceamento
                    </h2>
                    <p class="text-muted small mb-0">
                        <?= htmlspecialchars($wallet['name']) ?> •
                        Siga os passos abaixo para ajustar sua carteira
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/index.php?url=<?= obfuscateUrl('rebalance/index/' . $wallet['id']) ?>"
                       class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-arrow-left me-2"></i> Voltar
                    </a>
                    <button class="btn btn-primary rounded-pill px-4" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i> Imprimir Plano
                    </button>
                </div>
            </div>

            <!-- Resumo Executivo -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-clipboard-check me-2"></i>
                                Resumo do Rebalanceamento
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="metric-icon bg-primary bg-opacity-10 text-primary me-3">
                                            <i class="bi bi-cash-stack fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Total Atual</small>
                                            <h4 class="fw-bold mb-0">R$ <?= number_format($result['current_total_value'], 2, ',', '.') ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="metric-icon bg-success bg-opacity-10 text-success me-3">
                                            <i class="bi bi-graph-up-arrow fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Novo Total</small>
                                            <h4 class="fw-bold mb-0">R$ <?= number_format($result['new_total_value'], 2, ',', '.') ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="metric-icon
                                        <?= $result['value_difference'] > 0 ? 'bg-success bg-opacity-10 text-success' :
                                                ($result['value_difference'] < 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-secondary bg-opacity-10 text-secondary') ?>
                                        me-3">
                                            <i class="bi bi-arrow-left-right fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Diferença</small>
                                            <h4 class="fw-bold mb-0
                                            <?= $result['value_difference'] > 0 ? 'text-success' :
                                                    ($result['value_difference'] < 0 ? 'text-danger' : 'text-secondary') ?>">
                                                R$ <?= number_format($result['value_difference'], 2, ',', '.') ?>
                                            </h4>
                                            <small class="text-muted">
                                                <?= number_format(($result['value_difference'] / $result['current_total_value']) * 100, 2, ',', '.') ?>%
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="metric-icon bg-info bg-opacity-10 text-info me-3">
                                            <i class="bi bi-arrow-left-right fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Transações Necessárias</small>
                                            <h4 class="fw-bold mb-0"><?= $result['metrics']['transactions_count'] ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="metric-icon bg-warning bg-opacity-10 text-warning me-3">
                                            <i class="bi bi-graph-up-arrow fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Eficiência do Ajuste</small>
                                            <h4 class="fw-bold mb-0"><?= number_format($result['metrics']['execution_efficiency'] ?? 0, 1) ?>%</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="metric-icon
                                        <?= $result['metrics']['net_cash_flow'] > 0 ? 'bg-success bg-opacity-10 text-success' :
                                                ($result['metrics']['net_cash_flow'] < 0 ? 'bg-danger bg-opacity-10 text-danger' : 'bg-secondary bg-opacity-10 text-secondary') ?>
                                        me-3">
                                            <i class="bi bi-wallet fs-4"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Fluxo Líquido</small>
                                            <h4 class="fw-bold mb-0
                                            <?= $result['metrics']['net_cash_flow'] > 0 ? 'text-success' :
                                                    ($result['metrics']['net_cash_flow'] < 0 ? 'text-danger' : 'text-secondary') ?>">
                                                R$ <?= number_format($result['metrics']['net_cash_flow'], 2, ',', '.') ?>
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="transaction-summary rounded-3 p-3 mt-3">
                                <div class="row text-center">
                                    <div class="col">
                                        <div class="text-danger">
                                            <i class="bi bi-arrow-down-right fs-1"></i>
                                            <div class="fw-bold fs-4">R$ <?= number_format($result['metrics']['total_sales'], 2, ',', '.') ?></div>
                                            <small>Vendas Totais</small>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="text-primary">
                                            <i class="bi bi-arrow-right fs-1"></i>
                                            <div class="fw-bold fs-4">R$ <?= number_format($result['value_difference'], 2, ',', '.') ?></div>
                                            <small>Diferença de Valor</small>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="text-success">
                                            <i class="bi bi-arrow-up-right fs-1"></i>
                                            <div class="fw-bold fs-4">R$ <?= number_format($result['metrics']['total_purchases'], 2, ',', '.') ?></div>
                                            <small>Compras Totais</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($result['value_difference'] != 0): ?>
                                <div class="alert
                            <?= $result['value_difference'] > 0 ? 'alert-success' : 'alert-warning' ?>
                            border-0 bg-opacity-10 mt-3">
                                    <div class="d-flex">
                                        <i class="bi
                                    <?= $result['value_difference'] > 0 ? 'bi-arrow-up-circle' : 'bi-arrow-down-circle' ?>
                                    fs-4 me-3
                                    <?= $result['value_difference'] > 0 ? 'text-success' : 'text-warning' ?>"></i>
                                        <div>
                                            <h6 class="fw-bold mb-1">
                                                <?= $result['value_difference'] > 0 ? 'Aumento do Valor Total' : 'Redução do Valor Total' ?>
                                            </h6>
                                            <p class="mb-0 small">
                                                <?php if ($result['value_difference'] > 0): ?>
                                                    Você está aumentando o valor total da carteira em
                                                    <strong>R$ <?= number_format($result['value_difference'], 2, ',', '.') ?></strong>
                                                    (<?= number_format(($result['value_difference'] / $result['current_total_value']) * 100, 2, ',', '.') ?>%).
                                                    Isso exigirá recursos adicionais.
                                                <?php else: ?>
                                                    Você está reduzindo o valor total da carteira em
                                                    <strong>R$ <?= number_format(abs($result['value_difference']), 2, ',', '.') ?></strong>
                                                    (<?= number_format(abs($result['value_difference'] / $result['current_total_value']) * 100, 2, ',', '.') ?>%).
                                                    Você terá recursos disponíveis após as vendas.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white">
                            <h5 class="fw-bold mb-0">
                                <i class="bi bi-lightning-charge me-2 text-warning"></i>
                                Ações Rápidas
                            </h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-3">
                                <p class="small text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Execute as transações na ordem sugerida para otimizar os recursos.
                                </p>
                            </div>

                            <div class="mt-auto">
                                <form method="POST"
                                      action="/index.php?url=<?= obfuscateUrl('rebalance/execute/' . $wallet['id']) ?>"
                                      onsubmit="return confirm('Deseja realmente aplicar este rebalanceamento? Esta ação atualizará sua carteira.')">
                                    <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">
                                    <input type="hidden" name="instructions" value="<?= htmlspecialchars(json_encode($result['instructions'])) ?>">

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg rounded-pill">
                                            <i class="bi bi-check-circle me-2"></i>
                                            Aplicar Rebalanceamento
                                        </button>
                                        <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/index/' . $wallet['id']) ?>"
                                           class="btn btn-outline-primary rounded-pill">
                                            <i class="bi bi-eye me-2"></i>
                                            Ver Carteira Atual
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Passo a Passo -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-list-ol me-2"></i>
                        Instruções Passo a Passo
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($result['instructions']['steps'])): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">Nenhum ajuste necessário!</h4>
                            <p class="text-muted">Sua carteira já está alinhada com a composição desejada.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($result['instructions']['steps'] as $step): ?>
                                <div class="col-lg-6 fade-in">
                                    <div class="card step-card h-100 border-0 shadow-sm action-<?= $step['action'] ?>">
                                        <div class="card-body">
                                            <div class="d-flex">
                                                <div class="me-3">
                                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                                         style="width: 40px; height: 40px;">
                                                        <span class="fw-bold"><?= $step['step'] ?></span>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="fw-bold mb-0">
                                                            <i class="bi <?= $step['icon'] ?> me-2 text-<?= $step['color'] ?>"></i>
                                                            <?= ucfirst($step['action']) ?>
                                                        </h6>
                                                        <span class="badge bg-<?= $step['color'] ?> bg-opacity-10 text-<?= $step['color'] ?>">
                                                        <?= strtoupper($step['action']) ?>
                                                    </span>
                                                    </div>
                                                    <p class="mb-3"><?= $step['description'] ?></p>

                                                    <?php if (isset($step['details']['reason'])): ?>
                                                        <div class="alert alert-<?= $step['color'] ?> bg-opacity-10 border-0 small">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            <?= $step['details']['reason'] ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <!-- Detalhes específicos por ação -->
                                                    <?php if ($step['action'] === 'sell' && isset($step['details']['full_lot_quantity'])): ?>
                                                        <div class="lot-split">
                                                            <h6 class="fw-bold mb-2 small">Divisão por Tipo de Lote:</h6>
                                                            <div class="row small">
                                                                <?php if ($step['details']['full_lot_quantity'] > 0): ?>
                                                                    <div class="col-6">
                                                                        <div class="d-flex align-items-center mb-2">
                                                                    <span class="lot-full me-2">
                                                                        <i class="bi bi-box-seam"></i>
                                                                    </span>
                                                                            <div>
                                                                                <div class="fw-bold"><?= number_format($step['details']['full_lot_quantity']) ?></div>
                                                                                <small class="text-muted"><?= $step['details']['full_lot_ticker'] ?></small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <?php if ($step['details']['fractional_quantity'] > 0): ?>
                                                                    <div class="col-6">
                                                                        <div class="d-flex align-items-center mb-2">
                                                                    <span class="lot-fractional me-2">
                                                                        <i class="bi bi-scissors"></i>
                                                                    </span>
                                                                            <div>
                                                                                <div class="fw-bold"><?= number_format($step['details']['fractional_quantity']) ?></div>
                                                                                <small class="text-muted"><?= $step['details']['fractional_ticker'] ?? $step['details']['ticker'].'F' ?></small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <div class="col-12">
                                                                    <hr class="my-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <small class="text-muted">Total de Cotas</small>
                                                                        <span class="fw-bold"><?= number_format($step['details']['quantity']) ?></span>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between">
                                                                        <small class="text-muted">Valor Total</small>
                                                                        <span class="fw-bold text-danger">
                                                                        R$ <?= number_format($step['details']['total_sale'], 2, ',', '.') ?>
                                                                    </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php elseif ($step['action'] === 'buy_new' || $step['action'] === 'buy_additional'): ?>
                                                        <div class="lot-split">
                                                            <h6 class="fw-bold mb-2 small">Divisão por Tipo de Lote:</h6>
                                                            <div class="row small">
                                                                <?php if ($step['details']['full_lot_quantity'] > 0): ?>
                                                                    <div class="col-6">
                                                                        <div class="d-flex align-items-center mb-2">
                                                                    <span class="lot-full me-2">
                                                                        <i class="bi bi-box-seam"></i>
                                                                    </span>
                                                                            <div>
                                                                                <div class="fw-bold"><?= number_format($step['details']['full_lot_quantity']) ?></div>
                                                                                <small class="text-muted"><?= $step['details']['full_lot_ticker'] ?></small>
                                                                                <div class="text-muted smaller">
                                                                                    (<?= number_format($step['details']['full_lot_quantity'] / 100, 0) ?> lotes)
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <?php if ($step['details']['fractional_quantity'] > 0): ?>
                                                                    <div class="col-6">
                                                                        <div class="d-flex align-items-center mb-2">
                                                                    <span class="lot-fractional me-2">
                                                                        <i class="bi bi-scissors"></i>
                                                                    </span>
                                                                            <div>
                                                                                <div class="fw-bold"><?= number_format($step['details']['fractional_quantity']) ?></div>
                                                                                <small class="text-muted"><?= $step['details']['fractional_ticker'] ?? $step['details']['ticker'].'F' ?></small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>

                                                                <div class="col-12">
                                                                    <hr class="my-2">
                                                                    <div class="d-flex justify-content-between">
                                                                        <small class="text-muted">Total Necessário</small>
                                                                        <span class="fw-bold"><?= number_format($step['details']['quantity_needed']) ?></span>
                                                                    </div>
                                                                    <div class="d-flex justify-content-between">
                                                                        <small class="text-muted">Valor Total</small>
                                                                        <span class="fw-bold text-success">
                                                                        R$ <?= number_format($step['details']['total_cost'], 2, ',', '.') ?>
                                                                    </span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <?php if ($step['details']['execution'] === 'partial'): ?>
                                                                <div class="alert alert-warning border-0 small mt-2 mb-0">
                                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                                    <strong>Compra parcial:</strong>
                                                                    Apenas <?= number_format($step['details']['quantity_executed']) ?> cotas serão compradas
                                                                    (<?= number_format($step['details']['full_lot_executed']) ?> lote cheio +
                                                                    <?= number_format($step['details']['fractional_executed']) ?> fracionário)
                                                                    devido a limitação de recursos.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comparação de Composição -->
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-pie-chart me-2"></i>
                                Composição Atual (R$ <?= number_format($result['current_total_value'], 2, ',', '.') ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($result['current_composition'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                        <tr>
                                            <th>Ação</th>
                                            <th class="text-end">Quantidade</th>
                                            <th class="text-end">Valor</th>
                                            <th class="text-end">%</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($result['current_composition'] as $ticker => $data):
                                            $percent = $result['current_total_value'] > 0 ?
                                                    ($data['current_value'] / $result['current_total_value']) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                        <?= $ticker ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold"><?= number_format($data['current_quantity']) ?></td>
                                                <td class="text-end">R$ <?= number_format($data['current_value'], 2, ',', '.') ?></td>
                                                <td class="text-end">
                                                    <span class="badge bg-light text-dark">
                                                        <?= number_format($percent, 1) ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                        <tr>
                                            <th>TOTAL</th>
                                            <th class="text-end">
                                                <?= array_sum(array_column($result['current_composition'], 'current_quantity')) ?>
                                            </th>
                                            <th class="text-end fw-bold">
                                                R$ <?= number_format($result['current_total_value'], 2, ',', '.') ?>
                                            </th>
                                            <th class="text-end fw-bold">100%</th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-pie-chart" style="font-size: 2rem;"></i>
                                    <p class="mt-2">Nenhuma ação na carteira atual</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white">
                            <h6 class="fw-bold mb-0">
                                <i class="bi bi-pie-chart-fill me-2 text-success"></i>
                                Nova Composição (R$ <?= number_format($result['new_total_value'], 2, ',', '.') ?>)
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($result['target_composition'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                        <tr>
                                            <th>Ação</th>
                                            <th class="text-end">Valor Alvo</th>
                                            <th class="text-end">% Alvo</th>
                                            <th class="text-end">Ajuste</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($result['target_composition'] as $ticker => $data):
                                            $currentValue = $result['current_composition'][$ticker]['current_value'] ?? 0;
                                            $targetValue = $data['target_value'];
                                            $difference = $targetValue - $currentValue;
                                            $status = abs($difference) < 1 ? 'ok' : ($difference > 0 ? 'buy' : 'sell');
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-success bg-opacity-10 text-success">
                                                        <?= $ticker ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold">
                                                    R$ <?= number_format($targetValue, 2, ',', '.') ?>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-success bg-opacity-25 text-success">
                                                        <?= number_format($data['target_percent'], 1) ?>%
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($status === 'ok'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check"></i> OK
                                                        </span>
                                                    <?php elseif ($status === 'buy'): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="bi bi-arrow-up"></i> +R$ <?= number_format($difference, 2, ',', '.') ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">
                                                            <i class="bi bi-arrow-down"></i> -R$ <?= number_format(abs($difference), 2, ',', '.') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                        <tr>
                                            <th>TOTAL</th>
                                            <th class="text-end fw-bold">
                                                R$ <?= number_format($result['new_total_value'], 2, ',', '.') ?>
                                            </th>
                                            <th class="text-end fw-bold">100%</th>
                                            <th class="text-end"></th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-pie-chart" style="font-size: 2rem;"></i>
                                    <p class="mt-2">Nenhuma ação definida na nova composição</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dicas Finais -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white">
                    <h6 class="fw-bold mb-0">
                        <i class="bi bi-lightbulb me-2 text-warning"></i>
                        Dicas para Execução
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <i class="bi bi-1-circle text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">Divisão Automática</h6>
                                    <p class="small text-muted mb-0">
                                        O sistema já calculou a divisão entre lotes cheios e fracionários para cada ação.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <i class="bi bi-2-circle text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">Ordem das Transações</h6>
                                    <p class="small text-muted mb-0">
                                        Execute as vendas primeiro para gerar recursos, depois as compras.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <i class="bi bi-3-circle text-primary fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold">Preços de Mercado</h6>
                                    <p class="small text-muted mb-0">
                                        Execute durante o horário de negociação (10h-17h) para melhores preços.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 bg-opacity-10 mt-3">
                        <div class="d-flex">
                            <i class="bi bi-info-circle-fill text-info fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Exemplo Prático</h6>
                                <p class="small mb-0">
                                    <strong>Para 147 ITUBs necessários:</strong><br>
                                    • Compre 100 ITUB4 (1 lote cheio) no mercado à vista<br>
                                    • Compre 47 ITUB4F no mercado fracionário<br>
                                    • Total: 147 ações exatas para atingir a alocação desejada
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 bg-opacity-10">
                        <div class="d-flex">
                            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-3"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Importante</h6>
                                <p class="small mb-0">
                                    Este é um plano sugerido baseado em preços atuais. Os preços podem variar durante a execução.
                                    Recomendamos executar todas as transações no mesmo dia para minimizar riscos de mercado.
                                    Considere taxas de corretagem na execução das ordens.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animar elementos ao carregar
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>