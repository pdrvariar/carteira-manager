<?php
// app/views/rebalance/index.php

use App\Core\Session;
use App\Models\WalletStock;

$walletStockModel = new WalletStock();
$currentStocks = $walletStockModel->findByWalletId($wallet['id']);
$totalAllocation = 0;

foreach ($currentStocks as $stock) {
    $totalAllocation += $stock['target_allocation'] * 100;
}

$title = 'Rebalancear Carteira: ' . htmlspecialchars($wallet['name']);
$additional_css = '
    <link rel="stylesheet" href="/css/wallet_stocks.css">
    <style>
        .composition-item {
            transition: all 0.3s ease;
        }
        .composition-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .allocation-bar {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #e9ecef;
        }
        .allocation-fill {
            height: 100%;
            transition: width 0.5s ease;
        }
        .ticker-badge {
            font-family: "Courier New", monospace;
            font-weight: bold;
        }
        .step-card {
            border-left: 4px solid #0d6efd;
        }
        .action-sell { border-left-color: #dc3545 !important; }
        .action-buy { border-left-color: #198754 !important; }
        .action-keep { border-left-color: #6c757d !important; }
        .action-adjust { border-left-color: #ffc107 !important; }
    </style>
';
ob_start();
?>

    <div class="row">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="bi bi-arrow-left-right me-2"></i>Rebalancear Carteira
                    </h2>
                    <p class="text-muted small mb-0">
                        <?= htmlspecialchars($wallet['name']) ?> •
                        Ajuste a composição para atingir sua alocação alvo
                    </p>
                </div>
                <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/index/' . $wallet['id']) ?>"
                   class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i> Voltar
                </a>
            </div>

            <!-- Wizard de Rebalanceamento -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white py-3">
                    <ul class="nav nav-pills nav-justified" id="rebalanceTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill" id="step1-tab" data-bs-toggle="tab"
                                    data-bs-target="#step1" type="button" role="tab">
                                <span class="badge bg-primary rounded-circle me-2">1</span>
                                Composição Atual
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill" id="step2-tab" data-bs-toggle="tab"
                                    data-bs-target="#step2" type="button" role="tab">
                                <span class="badge bg-secondary rounded-circle me-2">2</span>
                                Nova Composição
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill" id="step3-tab" data-bs-toggle="tab"
                                    data-bs-target="#step3" type="button" role="tab">
                                <span class="badge bg-secondary rounded-circle me-2">3</span>
                                Revisar & Calcular
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content" id="rebalanceTabsContent">

                        <!-- PASSO 1: Composição Atual -->
                        <div class="tab-pane fade show active" id="step1" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h5 class="fw-bold mb-4">
                                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i>
                                        Sua Composição Atual
                                    </h5>

                                    <?php if (empty($currentStocks)): ?>
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Esta carteira ainda não possui ações.
                                        </div>
                                    <?php else: ?>
                                        <div class="row g-3">
                                            <?php foreach ($currentStocks as $stock):
                                                $allocationPercent = $stock['target_allocation'] * 100;
                                                $actualAllocation = 0; // Seria calculado com o valor total
                                                ?>
                                                <div class="col-md-6">
                                                    <div class="card border-0 shadow-sm rounded-3 composition-item h-100">
                                                        <div class="card-body">
                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                <div>
                                                            <span class="ticker-badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">
                                                                <?= htmlspecialchars($stock['ticker']) ?>
                                                            </span>
                                                                    <h6 class="mt-2 mb-1 fw-bold"><?= number_format($stock['quantity']) ?> cotas</h6>
                                                                    <small class="text-muted">
                                                                        Preço médio: R$ <?= number_format($stock['average_cost_per_share'], 2, ',', '.') ?>
                                                                    </small>
                                                                </div>
                                                                <div class="text-end">
                                                                    <div class="fw-bold text-primary fs-5">
                                                                        <?= number_format($allocationPercent, 1) ?>%
                                                                    </div>
                                                                    <small class="text-muted">Alvo</small>
                                                                </div>
                                                            </div>

                                                            <div class="allocation-bar mb-2">
                                                                <div class="allocation-fill bg-primary"
                                                                     style="width: <?= min(100, $allocationPercent) ?>%"></div>
                                                            </div>

                                                            <div class="d-flex justify-content-between small text-muted">
                                                                <span>Investido: R$ <?= number_format($stock['total_cost'], 2, ',', '.') ?></span>
                                                                <span>Atual: R$ <?= number_format($stock['quantity'] * ($stock['last_price'] ?? $stock['average_cost_per_share']), 2, ',', '.') ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="mt-4 p-3 bg-light rounded-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <small class="text-muted">Soma das Alocações Atuais</small>
                                                    <div class="fw-bold fs-4 <?= abs($totalAllocation - 100) < 0.1 ? 'text-success' : 'text-warning' ?>">
                                                        <?= number_format($totalAllocation, 1) ?>%
                                                    </div>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Total de Ações</small>
                                                    <div class="fw-bold"><?= count($currentStocks) ?></div>
                                                </div>
                                                <div>
                                                    <small class="text-muted">Próximo Passo</small>
                                                    <div>
                                                        <button class="btn btn-primary btn-sm" onclick="nextStep()">
                                                            Definir Nova Composição <i class="bi bi-arrow-right ms-1"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card border-0 bg-light rounded-4 h-100">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3">
                                                <i class="bi bi-lightbulb text-warning me-2"></i>
                                                Como Funciona o Rebalanceamento
                                            </h6>
                                            <div class="mb-3">
                                                <div class="d-flex mb-2">
                                                    <div class="me-3">
                                                        <i class="bi bi-1-circle text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <small class="fw-bold">Venda do Excessivo</small>
                                                        <p class="small text-muted mb-0">Vendemos ações que ultrapassam o percentual alvo</p>
                                                    </div>
                                                </div>
                                                <div class="d-flex mb-2">
                                                    <div class="me-3">
                                                        <i class="bi bi-2-circle text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <small class="fw-bold">Compra do Insuficiente</small>
                                                        <p class="small text-muted mb-0">Compramos ações abaixo do percentual alvo</p>
                                                    </div>
                                                </div>
                                                <div class="d-flex">
                                                    <div class="me-3">
                                                        <i class="bi bi-3-circle text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <small class="fw-bold">Ajuste de Lotes</small>
                                                        <p class="small text-muted mb-0">Respeitamos lotes padrão (100) quando necessário</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr>

                                            <h6 class="fw-bold mb-2">Dicas Importantes</h6>
                                            <ul class="small text-muted ps-3">
                                                <li>As alocações devem somar 100%</li>
                                                <li>Considere incluir caixa disponível para compras</li>
                                                <li>Ações fracionárias podem ser ajustadas em qualquer quantidade</li>
                                                <li>Taxas de corretagem não estão incluídas no cálculo</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PASSO 2: Nova Composição -->
                        <div class="tab-pane fade" id="step2" role="tabpanel">
                            <form id="rebalanceForm" method="POST"
                                  action="/index.php?url=<?= obfuscateUrl('rebalance/calculate/' . $wallet['id']) ?>">
                                <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">

                                <div class="row">
                                    <div class="col-lg-8">
                                        <h5 class="fw-bold mb-4">
                                            <i class="bi bi-sliders me-2 text-primary"></i>
                                            Defina a Nova Composição
                                        </h5>

                                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                                            <div class="card-header bg-white">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-0 fw-bold">Ações e Percentuais</h6>
                                                        <small class="text-muted">Adicione todas as ações da nova composição</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                            onclick="addNewStock()">
                                                        <i class="bi bi-plus-lg me-1"></i> Nova Ação
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div id="compositionContainer">
                                                    <!-- Linha de exemplo -->
                                                    <div class="row g-3 mb-3 composition-row">
                                                        <div class="col-md-5">
                                                            <label class="form-label small fw-bold">Ticker</label>
                                                            <input type="text"
                                                                   class="form-control ticker-input"
                                                                   name="tickers[]"
                                                                   placeholder="Ex: PETR4, VALE3"
                                                                   maxlength="10"
                                                                   required>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label class="form-label small fw-bold">% Alocação Alvo</label>
                                                            <div class="input-group">
                                                                <input type="number"
                                                                       class="form-control allocation-input"
                                                                       name="allocations[]"
                                                                       min="0.01"
                                                                       max="100"
                                                                       step="0.01"
                                                                       placeholder="15,00"
                                                                       required
                                                                       onchange="updateTotalAllocation()">
                                                                <span class="input-group-text">%</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 d-flex align-items-end">
                                                            <button type="button" class="btn btn-outline-danger btn-sm w-100"
                                                                    onclick="removeStock(this)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Ações Sugeridas da Carteira Atual -->
                                                <?php if (!empty($currentStocks)): ?>
                                                    <div class="mt-4">
                                                        <h6 class="fw-bold mb-3">Sugestões da Carteira Atual</h6>
                                                        <div class="d-flex flex-wrap gap-2">
                                                            <?php foreach ($currentStocks as $stock):
                                                                $allocationPercent = $stock['target_allocation'] * 100;
                                                                ?>
                                                                <button type="button" class="btn btn-outline-primary btn-sm"
                                                                        onclick="suggestStock('<?= $stock['ticker'] ?>', <?= number_format($allocationPercent, 2, '.', '') ?>)">
                                                                    <?= $stock['ticker'] ?> (<?= number_format($allocationPercent, 1) ?>%)
                                                                </button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="card border-0 shadow-sm rounded-4">
                                            <div class="card-body">
                                                <h6 class="fw-bold mb-3">Caixa Disponível para Compras</h6>
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <p class="small text-muted mb-2">
                                                            Informe o valor em caixa disponível para compras adicionais.
                                                            Se deixar zero, o rebalanceamento usará apenas a venda de ações para financiar as compras.
                                                        </p>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="input-group">
                                                            <span class="input-group-text">R$</span>
                                                            <input type="text"
                                                                   class="form-control"
                                                                   name="available_cash"
                                                                   id="available_cash"
                                                                   value="0,00"
                                                                   placeholder="0,00">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-4">
                                        <div class="sticky-top" style="top: 20px;">
                                            <div class="card border-0 bg-primary bg-opacity-10 rounded-4 mb-3">
                                                <div class="card-body">
                                                    <h6 class="fw-bold mb-3">Resumo da Composição</h6>

                                                    <div class="mb-4">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <small class="text-muted">Soma dos Percentuais</small>
                                                            <small class="text-muted" id="totalAllocation">0%</small>
                                                        </div>
                                                        <div class="progress" style="height: 10px;">
                                                            <div class="progress-bar" id="allocationBar"
                                                                 role="progressbar" style="width: 0%"></div>
                                                        </div>
                                                        <div class="mt-2 text-center">
                                                            <small id="allocationStatus" class="text-muted">
                                                                Adicione ações para começar
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small class="text-muted">Total de Ações</small>
                                                            <small class="fw-bold" id="totalStocks">0</small>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-1">
                                                            <small class="text-muted">Status da Soma</small>
                                                            <small class="fw-bold" id="sumStatus">-</small>
                                                        </div>
                                                    </div>

                                                    <hr>

                                                    <div class="d-grid">
                                                        <button type="submit" class="btn btn-primary btn-lg rounded-pill"
                                                                id="calculateBtn" disabled>
                                                            <i class="bi bi-calculator me-2"></i>
                                                            Calcular Rebalanceamento
                                                        </button>
                                                        <small class="text-center mt-2 text-muted">
                                                            O cálculo considerará preços de mercado atuais
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card border-0 bg-light rounded-4">
                                                <div class="card-body">
                                                    <h6 class="fw-bold mb-3">
                                                        <i class="bi bi-info-circle text-info me-2"></i>
                                                        Sobre Lotes
                                                    </h6>
                                                    <div class="alert alert-info border-0 bg-opacity-10 small">
                                                        <p class="mb-2">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            <strong>Ações Fracionárias:</strong> Qualquer quantidade (ex: B3SA3F, ETFs)
                                                        </p>
                                                        <p class="mb-0">
                                                            <i class="bi bi-123 me-1"></i>
                                                            <strong>Lotes Padrão:</strong> Múltiplos de 100 (ex: PETR4, VALE3)
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar com uma linha vazia
            updateTotalAllocation();

            // Formatação de moeda
            const cashInput = document.getElementById('available_cash');
            cashInput.addEventListener('blur', function() {
                let value = this.value.replace(',', '.');
                if (value && !isNaN(value)) {
                    this.value = parseFloat(value).toFixed(2).replace('.', ',');
                }
            });

            // Atualizar alocação quando inputs mudam
            document.querySelectorAll('.allocation-input').forEach(input => {
                input.addEventListener('input', updateTotalAllocation);
            });
        });

        function nextStep() {
            const step2Tab = document.querySelector('#step2-tab');
            const step2Pane = document.querySelector('#step2');

            // Ativar passo 2
            document.querySelector('#step1-tab').classList.remove('active');
            document.querySelector('#step1').classList.remove('show', 'active');

            step2Tab.classList.add('active');
            step2Pane.classList.add('show', 'active');

            // Preencher com ações atuais se não houver dados
            if (document.querySelectorAll('.composition-row').length === 1) {
                <?php foreach ($currentStocks as $stock): ?>
                addNewStock('<?= $stock['ticker'] ?>', <?= number_format($stock['target_allocation'] * 100, 2, '.', '') ?>);
                <?php endforeach; ?>
                updateTotalAllocation();
            }
        }

        function addNewStock(ticker = '', allocation = '') {
            const container = document.getElementById('compositionContainer');
            const rowCount = container.querySelectorAll('.composition-row').length;

            // Limitar a 15 ações
            if (rowCount >= 15) {
                alert('Máximo de 15 ações permitidas para uma carteira diversificada.');
                return;
            }

            const newRow = document.createElement('div');
            newRow.className = 'row g-3 mb-3 composition-row';
            newRow.innerHTML = `
        <div class="col-md-5">
            <label class="form-label small fw-bold">Ticker</label>
            <input type="text"
                   class="form-control ticker-input"
                   name="tickers[]"
                   value="${ticker}"
                   placeholder="Ex: PETR4, VALE3"
                   maxlength="10"
                   required>
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-bold">% Alocação Alvo</label>
            <div class="input-group">
                <input type="number"
                       class="form-control allocation-input"
                       name="allocations[]"
                       value="${allocation}"
                       min="0.01"
                       max="100"
                       step="0.01"
                       placeholder="15,00"
                       required
                       onchange="updateTotalAllocation()">
                <span class="input-group-text">%</span>
            </div>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger btn-sm w-100"
                    onclick="removeStock(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;

            container.appendChild(newRow);

            // Adicionar event listener para o novo input
            newRow.querySelector('.allocation-input').addEventListener('input', updateTotalAllocation);

            updateTotalAllocation();
        }

        function removeStock(button) {
            const row = button.closest('.composition-row');
            if (document.querySelectorAll('.composition-row').length > 1) {
                row.remove();
                updateTotalAllocation();
            } else {
                alert('É necessário pelo menos uma ação na composição.');
            }
        }

        function suggestStock(ticker, allocation) {
            addNewStock(ticker, allocation);
        }

        function updateTotalAllocation() {
            let total = 0;
            let validRows = 0;

            document.querySelectorAll('.composition-row').forEach(row => {
                const allocationInput = row.querySelector('.allocation-input');
                const tickerInput = row.querySelector('.ticker-input');

                if (allocationInput && tickerInput.value.trim() !== '') {
                    const value = parseFloat(allocationInput.value) || 0;
                    total += value;
                    validRows++;
                }
            });

            // Atualizar display
            document.getElementById('totalAllocation').textContent = total.toFixed(1) + '%';
            document.getElementById('totalStocks').textContent = validRows;

            const allocationBar = document.getElementById('allocationBar');
            const allocationStatus = document.getElementById('allocationStatus');
            const calculateBtn = document.getElementById('calculateBtn');

            // Cor da barra baseada no total
            if (total === 0) {
                allocationBar.style.width = '0%';
                allocationBar.className = 'progress-bar';
                allocationStatus.textContent = 'Adicione ações para começar';
                allocationStatus.className = 'text-muted';
                calculateBtn.disabled = true;
                document.getElementById('sumStatus').innerHTML = '<span class="text-muted">-</span>';
            } else if (Math.abs(total - 100) < 0.1) {
                allocationBar.style.width = '100%';
                allocationBar.className = 'progress-bar bg-success';
                allocationStatus.textContent = 'Perfeito! Pronto para calcular';
                allocationStatus.className = 'text-success fw-bold';
                calculateBtn.disabled = false;
                document.getElementById('sumStatus').innerHTML = '<span class="text-success fw-bold">✓ 100%</span>';
            } else if (total > 100) {
                allocationBar.style.width = '100%';
                allocationBar.className = 'progress-bar bg-danger';
                allocationStatus.textContent = `Excedido em ${(total - 100).toFixed(1)}%`;
                allocationStatus.className = 'text-danger fw-bold';
                calculateBtn.disabled = true;
                document.getElementById('sumStatus').innerHTML = `<span class="text-danger fw-bold">${total.toFixed(1)}%</span>`;
            } else {
                const percent = Math.min(total, 100);
                allocationBar.style.width = percent + '%';
                allocationBar.className = 'progress-bar bg-warning';
                allocationStatus.textContent = `Faltam ${(100 - total).toFixed(1)}%`;
                allocationStatus.className = 'text-warning fw-bold';
                calculateBtn.disabled = false;
                document.getElementById('sumStatus').innerHTML = `<span class="text-warning fw-bold">${total.toFixed(1)}%</span>`;
            }
        }

        // Navegação entre tabs
        const triggerTabList = document.querySelectorAll('#rebalanceTabs button');
        triggerTabList.forEach(triggerEl => {
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                const tabTrigger = new bootstrap.Tab(triggerEl);
                tabTrigger.show();
            });
        });
    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>