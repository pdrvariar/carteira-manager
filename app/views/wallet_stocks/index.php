<?php
// app/views/wallet_stocks/index.php

use App\Core\Session;

$title = 'Composição: ' . htmlspecialchars($wallet['name']);
$additional_css = '<link rel="stylesheet" href="/css/wallet_stocks.css">';
ob_start();
?>

    <div class="row">
        <div class="col-lg-12">
            <!-- Alerta de Alocação (aparece apenas se não estiver em 100%) -->
            <?php if (isset($totalAllocationPercent) && abs($totalAllocationPercent - 100) > 0.01): ?>
                <div class="alert
                    <?= $totalAllocationPercent > 100 ? 'alert-warning' : 'alert-info' ?>
                    border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center">
                    <div class="me-3">
                        <i class="bi
                            <?= $totalAllocationPercent > 100 ? 'bi-exclamation-triangle-fill' : 'bi-info-circle-fill' ?>
                            fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1">
                            <?= $totalAllocationPercent > 100 ? 'Alocação Excedida' : 'Alocação Incompleta' ?>
                        </h6>
                        <p class="mb-0 small">
                            A soma das alocações está em <strong><?= number_format($totalAllocationPercent, 1) ?>%</strong>.
                            <?php if ($totalAllocationPercent > 100): ?>
                                Você está alocando <strong><?= number_format($totalAllocationPercent - 100, 1) ?>%</strong> a mais do que o total disponível.
                                <br><small><strong>Dica:</strong> Ajuste as alocações das ações existentes ou remova alguma ação.</small>
                            <?php else: ?>
                                Ainda há <strong><?= number_format(100 - $totalAllocationPercent, 1) ?>%</strong> disponível para alocar.
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($totalAllocationPercent < 100): ?>
                        <div class="ms-auto">
                            <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/create/' . $wallet['id']) ?>"
                               class="btn btn-sm btn-info rounded-pill px-3">
                                <i class="bi bi-plus-lg me-1"></i>
                                Adicionar Ações
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-wallet me-2"></i><?= htmlspecialchars($wallet['name']) ?>
                    </h2>
                    <p class="text-muted small mb-0">
                        <i class="bi bi-pie-chart me-1"></i> Composição da Carteira
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/update_prices/' . $wallet['id']) ?>"
                       class="btn btn-outline-primary rounded-pill px-4">
                        <i class="bi bi-arrow-clockwise me-2"></i> Atualizar Preços
                    </a>
                    <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/create/' . $wallet['id']) ?>"
                       class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-plus-lg me-2"></i> Nova Ação
                    </a>
                </div>
            </div>

            <!-- Cards de Resumo -->
            <div class="row mb-4">
                <div class="col-xl col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total Investido</h6>
                                    <h5 class="fw-bold text-primary">R$ <?= number_format($summary['total_invested'] ?? 0, 2, ',', '.') ?></h5>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                    <i class="bi bi-cash-coin text-primary fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Valor Atual</h6>
                                    <h5 class="fw-bold text-success">R$ <?= number_format($summary['current_value'] ?? 0, 2, ',', '.') ?></h5>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                    <i class="bi bi-graph-up text-success fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Lucro/Prejuízo</h6>
                                    <h5 class="fw-bold <?= ($summary['total_pl'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                        R$ <?= number_format($summary['total_pl'] ?? 0, 2, ',', '.') ?>
                                    </h5>
                                    <?php
                                    $totalInvested = $summary['total_invested'] ?? 0;
                                    $totalPL = $summary['total_pl'] ?? 0;
                                    $totalPLPercent = $totalInvested > 0 ? ($totalPL / $totalInvested * 100) : 0;
                                    ?>
                                    <small class="text-muted">
                                        <?= ($totalPLPercent >= 0 ? '+' : '') . number_format($totalPLPercent, 2, ',', '.') ?>%
                                    </small>
                                </div>
                                <div class="<?= ($summary['total_pl'] ?? 0) >= 0 ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 rounded-circle p-3">
                                    <i class="bi <?= ($summary['total_pl'] ?? 0) >= 0 ? 'bi-arrow-up-right text-success' : 'bi-arrow-down-right text-danger' ?> fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-md-6 mb-4">
                    <div class="card border-0 shadow-sm rounded-4 metric-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-2">Total de Ações</h6>
                                    <h5 class="fw-bold text-info"><?= $summary['total_stocks'] ?? 0 ?></h5>
                                    <small class="text-muted"><?= $summary['total_quantity'] ?? 0 ?> cotas</small>
                                </div>
                                <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                    <i class="bi bi-bar-chart text-info fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Alocação Total -->
                <?php if (isset($totalAllocationPercent)): ?>
                    <div class="col-xl col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-4 metric-card h-100
                        <?=
                        abs($totalAllocationPercent - 100) < 0.01 ? 'allocation-perfect border-success' :
                                ($totalAllocationPercent > 100 ? 'allocation-over border-warning' : 'allocation-under border-info')
                        ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-2">Alocação Total</h6>
                                        <h5 class="fw-bold
                                        <?=
                                        abs($totalAllocationPercent - 100) < 0.01 ? 'text-success' :
                                                ($totalAllocationPercent > 100 ? 'text-warning' : 'text-info')
                                        ?>">
                                            <?= number_format($totalAllocationPercent, 1) ?>%
                                        </h5>
                                        <small class="text-muted">Meta: 100%</small>
                                    </div>
                                    <div class="
                                    <?=
                                    abs($totalAllocationPercent - 100) < 0.01 ? 'bg-success' :
                                            ($totalAllocationPercent > 100 ? 'bg-warning' : 'bg-info')
                                    ?> bg-opacity-10 rounded-circle p-3">
                                        <i class="bi
                                        <?=
                                        abs($totalAllocationPercent - 100) < 0.01 ? 'bi-check-circle-fill text-success' :
                                                ($totalAllocationPercent > 100 ? 'bi-exclamation-triangle-fill text-warning' :
                                                        'bi-info-circle-fill text-info')
                                        ?> fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cards das Ações -->
            <?php if (empty($stocks)): ?>
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-pie-chart text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="text-muted mb-3">Nenhuma ação na carteira</h5>
                        <p class="text-muted mb-4">Adicione ações para começar a compor sua carteira de investimentos.</p>
                        <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/create/' . $wallet['id']) ?>" class="btn btn-primary px-4">
                            <i class="bi bi-plus-lg me-1"></i> Adicionar Primeira Ação
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php
                    usort($stocks, function($a, $b) {
                        return strcmp($a['ticker'], $b['ticker']);
                    });
                    foreach ($stocks as $stock):
                        $currentValue = $stock['quantity'] * $stock['last_price'];
                        $profitLoss = $currentValue - $stock['total_invested'];
                        $profitLossPercent = $stock['total_invested'] > 0 ? ($profitLoss / $stock['total_invested'] * 100) : 0;
                        ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm rounded-4 hover-shadow transition-all">
                                <div class="card-header bg-white border-0 pt-4 pb-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                                    <i class="bi bi-currency-dollar text-primary"></i>
                                                </div>
                                                <div>
                                                    <h5 class="card-title mb-0 fw-bold"><?= htmlspecialchars($stock['ticker']) ?></h5>
                                                    <small class="text-muted">Ação</small>
                                                </div>
                                            </div>

                                            <!-- Indicador de Alocação na Ação -->
                                            <?php
                                            $actualAllocation = $summary['current_value'] > 0 ?
                                                    ($currentValue / $summary['current_value']) * 100 : 0;
                                            ?>
                                            <small class="d-flex align-items-center mt-2">
                                                <span class="badge
                                                    <?=
                                                abs($stock['target_allocation'] * 100 - $actualAllocation) < 1 ? 'bg-success' : 'bg-light text-dark'
                                                ?>
                                                    rounded-pill me-2">
                                                    <?= number_format($stock['target_allocation'] * 100, 1) ?>%
                                                </span>
                                                <span class="text-muted small">
                                                    Real: <?= number_format($actualAllocation, 1) ?>%
                                                </span>
                                            </small>
                                        </div>

                                        <div class="dropdown">
                                            <!-- CORREÇÃO: Adicionado data-bs-display="static" para evitar que o menu seja cortado ou fique escondido -->
                                            <button class="btn btn-sm btn-outline-secondary border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-display="static">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <!-- CORREÇÃO: Adicionado z-index alto e alinhamento à direita -->
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="z-index: 1050;">
                                                <li>
                                                    <a class="dropdown-item" href="/index.php?url=<?= obfuscateUrl('wallet_stocks/edit/' . $stock['id']) ?>">
                                                        <i class="bi bi-pencil me-2"></i> Editar
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger"
                                                       href="/index.php?url=<?= obfuscateUrl('wallet_stocks/delete/' . $stock['id']) ?>"
                                                       onclick="return confirm('Tem certeza que deseja remover esta ação da carteira?')">
                                                        <i class="bi bi-trash me-2"></i> Remover
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-body p-4 pt-2">
                                    <!-- Informações da Ação -->
                                    <div class="mb-4">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <small class="text-muted d-block">Quantidade</small>
                                                <div class="fw-bold fs-5"><?= number_format($stock['quantity']) ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Preço Médio</small>
                                                <div class="fw-bold fs-5">R$ <?= number_format($stock['average_cost_per_share'], 2, ',', '.') ?></div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Último Preço</small>
                                                <div class="fw-bold fs-5">
                                                    R$ <?= $stock['last_price'] ? number_format($stock['last_price'], 2, ',', '.') : 'N/A' ?>
                                                    <?php if ($stock['last_market_price_updated']): ?>
                                                        <small class="d-block text-muted smaller">
                                                            <i class="bi bi-clock-history me-1"></i>
                                                            <?= date('d/m H:i', strtotime($stock['last_market_price_updated'])) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block">Alocação Alvo</small>
                                                <div class="fw-bold fs-5"><?= number_format($stock['target_allocation'] * 100, 1) ?>%</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Performance -->
                                    <div class="bg-light rounded-3 p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted d-block">Investimento Total</small>
                                                <div class="fw-bold">R$ <?= number_format($stock['total_invested'], 2, ',', '.') ?></div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block">Valor Atual</small>
                                                <div class="fw-bold">R$ <?= number_format($currentValue, 2, ',', '.') ?></div>
                                            </div>
                                        </div>

                                        <div class="progress mt-2" style="height: 6px;">
                                            <div class="progress-bar bg-primary"
                                                 style="width: <?= min(100, ($currentValue / max(1, $summary['current_value'])) * 100) ?>%"></div>
                                        </div>

                                        <div class="d-flex justify-content-between mt-3">
                                            <div>
                                                <small class="text-muted d-block">Lucro/Prejuízo</small>
                                                <div class="fw-bold <?= $profitLoss >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    R$ <?= number_format($profitLoss, 2, ',', '.') ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block">Variação</small>
                                                <div class="fw-bold <?= $profitLossPercent >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= ($profitLossPercent >= 0 ? '+' : '') ?><?= number_format($profitLossPercent, 2, ',', '.') ?>%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-white border-0 pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            Adicionado em <?= date('d/m/Y', strtotime($stock['created_at'])) ?>
                                        </small>
                                        <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/edit/' . $stock['id']) ?>"
                                           class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <i class="bi bi-pencil me-1"></i> Editar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-4 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Total de <?= count($stocks) ?> ação(ões) na carteira
                </div>
            <?php endif; ?>

            <!-- Botões de Navegação -->
            <div class="d-flex justify-content-between mt-5 pt-4 border-top">
                <a href="/index.php?url=<?= obfuscateUrl('wallet') ?>" class="btn btn-outline-secondary px-4 rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Voltar para Carteiras
                </a>
                <a href="/index.php?url=<?= obfuscateUrl('wallet/view/' . $wallet['id']) ?>" class="btn btn-outline-primary px-4 rounded-pill">
                    <i class="bi bi-eye me-1"></i> Ver Detalhes da Carteira
                </a>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>