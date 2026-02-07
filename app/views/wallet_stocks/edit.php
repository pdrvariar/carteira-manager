<?php
// app/views/wallet_stocks/edit.php

use App\Core\Session;

$title = 'Editar Ação: ' . htmlspecialchars($stock['ticker']);
$additional_css = '<link rel="stylesheet" href="/css/wallet_stocks.css">';
ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-pencil-square text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Editar Ação</h4>
                            <p class="text-muted mb-0">
                                <?= htmlspecialchars($stock['ticker']) ?> - <?= htmlspecialchars($stock['wallet_name']) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?= obfuscateUrl('wallet_stocks/update/' . $stock['id']) ?>">
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
                                       value="<?= htmlspecialchars($stock['ticker']) ?>"
                                       required
                                       placeholder="Ex: PETR4, VALE3, ITSA4"
                                       maxlength="10">
                            </div>

                            <div class="col-md-6 mb-4">
                                <label for="quantity" class="form-label fw-bold">
                                    Quantidade <span class="text-danger">*</span>
                                </label>
                                <input type="number"
                                       class="form-control form-control-lg border-0 bg-light rounded-3"
                                       id="quantity"
                                       name="quantity"
                                       value="<?= $stock['quantity'] ?>"
                                       required
                                       min="1"
                                       step="1"
                                       placeholder="Ex: 100">
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
                                       value="<?= number_format($stock['average_cost_per_share'], 2, ',', '') ?>"
                                       required
                                       placeholder="Ex: 32,50"
                                       inputmode="decimal">
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
                                           value="<?= number_format($stock['target_allocation'] * 100, 2, '.', '') ?>"
                                           required
                                           min="0.01"
                                           max="100"
                                           step="0.01"
                                           placeholder="Ex: 15,00">
                                    <span class="input-group-text bg-light border-0">%</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card border-0 bg-light rounded-4">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-3">Informações Atuais</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Último Preço de Mercado</small>
                                            <div class="fw-bold">
                                                R$ <?= $stock['last_market_price'] ? number_format($stock['last_market_price'], 2, ',', '.') : 'N/A' ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Total Investido</small>
                                            <div class="fw-bold">
                                                R$ <?= number_format($stock['total_cost'], 2, ',', '.') ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Última Atualização</small>
                                            <div class="fw-bold">
                                                <?= $stock['last_market_price_updated'] ? date('d/m/Y H:i', strtotime($stock['last_market_price_updated'])) : 'Nunca' ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Criado em</small>
                                            <div class="fw-bold">
                                                <?= date('d/m/Y H:i', strtotime($stock['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <div>
                                <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/index/' . $stock['wallet_id']) ?>"
                                   class="btn btn-outline-secondary px-4 rounded-pill me-2">
                                    <i class="bi bi-x-lg me-1"></i> Cancelar
                                </a>
                                <a href="/index.php?url=<?= obfuscateUrl('wallet_stocks/delete/' . $stock['id']) ?>"
                                   class="btn btn-outline-danger px-4 rounded-pill"
                                   onclick="return confirm('Tem certeza que deseja remover esta ação da carteira?')">
                                    <i class="bi bi-trash me-1"></i> Remover
                                </a>
                            </div>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-save me-1"></i> Salvar Alterações
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
        });
    </script>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>