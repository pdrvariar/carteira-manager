<?php
// app/views/wallet_stocks/create.php

use App\Core\Session;

$title = 'Adicionar Ação - ' . htmlspecialchars($wallet['name']);
$additional_css = '<link rel="stylesheet" href="/css/wallet_stocks.css">';
ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-plus-circle text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Adicionar Ação à Carteira</h4>
                            <p class="text-muted mb-0"><?= htmlspecialchars($wallet['name']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?= obfuscateUrl('wallet_stocks/create/' . $wallet['id']) ?>">
                        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken(); ?>">

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="ticker" class="form-label fw-bold">
                                    Ticker da Ação <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-lg border-0 bg-light rounded-3"
                                       id="ticker"
                                       name="ticker"
                                       required
                                       placeholder="Ex: PETR4, VALE3, ITSA4"
                                       maxlength="10">
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Digite o código da ação na B3 (ex: PETR4, VALE3)
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="quantity" class="form-label fw-bold">
                                    Quantidade <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control form-control-lg border-0 bg-light rounded-3"
                                       id="quantity"
                                       name="quantity"
                                       required
                                       min="1"
                                       step="1"
                                       placeholder="Ex: 100">
                                <div class="form-text">
                                    <i class="bi bi-123 me-1"></i>
                                    Número de cotas adquiridas
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="average_cost_per_share" class="form-label fw-bold">
                                    Preço Médio por Ação (R$) <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control form-control-lg border-0 bg-light rounded-3"
                                       id="average_cost_per_share"
                                       name="average_cost_per_share"
                                       required
                                       placeholder="Ex: 32,50"
                                       inputmode="decimal">
                                <div class="form-text">
                                    <i class="bi bi-cash-coin me-1"></i>
                                    Preço médio pago por cada ação
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="target_allocation" class="form-label fw-bold">
                                    % Alocação Alvo <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number"
                                           class="form-control form-control-lg border-0 bg-light rounded-3"
                                           id="target_allocation"
                                           name="target_allocation"
                                           required
                                           min="0.01"
                                           max="100"
                                           step="0.01"
                                           placeholder="Ex: 15,00"
                                           value="0">
                                    <span class="input-group-text bg-light border-0">%</span>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-pie-chart me-1"></i>
                                    Percentual desejado desta ação na carteira
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="alert alert-info border-0 bg-opacity-10 rounded-3">
                                <div class="d-flex">
                                    <i class="bi bi-lightbulb text-info fs-4 me-3"></i>
                                    <div>
                                        <h6 class="fw-bold mb-1">Dicas para preencher:</h6>
                                        <ul class="mb-0 ps-3 small">
                                            <li>O preço médio deve incluir todas as taxas (corretagem, emolumentos)</li>
                                            <li>A alocação alvo deve somar 100% entre todas as ações da carteira</li>
                                            <li>Você pode atualizar os preços de mercado após adicionar a ação</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/index/' . $wallet['id']) ?>"
                               class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-check-lg me-1"></i> Adicionar Ação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Formatar entrada do preço médio
            const priceInput = document.getElementById('average_cost_per_share');
            priceInput.addEventListener('blur', function() {
                let value = this.value.replace(',', '.');
                if (value && !isNaN(value)) {
                    this.value = parseFloat(value).toFixed(2).replace('.', ',');
                }
            });

            // Calcular total automaticamente
            const quantityInput = document.getElementById('quantity');
            const averageCostInput = document.getElementById('average_cost_per_share');

            function calculateTotal() {
                const quantity = parseInt(quantityInput.value) || 0;
                const price = parseFloat(averageCostInput.value.replace(',', '.')) || 0;
                if (quantity > 0 && price > 0) {
                    // Mostrar cálculo em tempo real (opcional)
                    console.log('Total investido: R$', (quantity * price).toFixed(2));
                }
            }

            quantityInput.addEventListener('input', calculateTotal);
            averageCostInput.addEventListener('input', calculateTotal);
        });
    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>