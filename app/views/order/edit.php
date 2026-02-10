<?php
// app/views/order/edit.php

use App\Core\Session;

$statusOptions = [
    'pending' => 'Pendente',
    'processing' => 'Processando',
    'shipped' => 'Enviado',
    'delivered' => 'Entregue',
    'cancelled' => 'Cancelado'
];

$statusConfig = [
    'pending' => ['class' => 'bg-warning bg-opacity-10 text-warning', 'icon' => 'bi-clock'],
    'processing' => ['class' => 'bg-primary bg-opacity-10 text-primary', 'icon' => 'bi-gear'],
    'shipped' => ['class' => 'bg-info bg-opacity-10 text-info', 'icon' => 'bi-truck'],
    'delivered' => ['class' => 'bg-success bg-opacity-10 text-success', 'icon' => 'bi-check-circle'],
    'cancelled' => ['class' => 'bg-danger bg-opacity-10 text-danger', 'icon' => 'bi-x-circle']
];

$title = 'Editar Pedido: ' . htmlspecialchars($order['order_number'] . ' - ' . $order['customer_name']);
$additional_css = '
    <link rel="stylesheet" href="/css/order.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
';
$additional_js = '
    <script src="/js/order.js" defer></script>
';
ob_start();
?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                            <i class="bi bi-receipt text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">Editar Pedido</h4>
                            <p class="text-muted mb-0">Atualize as informações do pedido.</p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="/index.php?url=<?php echo obfuscateUrl('order/update/' . $order['id']); ?>" id="orderForm">
                        <input type="hidden" name="csrf_token" value="<?php echo Session::getCsrfToken(); ?>">

                        <!-- Seção: Dados do Cliente -->
                        <div class="mb-5">
                            <h5 class="fw-bold mb-3 text-primary">
                                <i class="bi bi-person-badge me-2"></i>
                                Dados do Cliente
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customer_name" class="form-label fw-bold">
                                        Nome do Cliente <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="customer_name"
                                           name="customer_name"
                                           required
                                           value="<?= htmlspecialchars($order['customer_name']) ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="customer_email" class="form-label fw-bold">E-mail</label>
                                    <input type="email"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="customer_email"
                                           name="customer_email"
                                           value="<?= htmlspecialchars($order['customer_email'] ?? '') ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="customer_phone" class="form-label fw-bold">Telefone</label>
                                    <input type="tel"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="customer_phone"
                                           name="customer_phone"
                                           value="<?= htmlspecialchars($order['customer_phone'] ?? '') ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="order_date" class="form-label fw-bold">
                                        Data do Pedido <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="order_date"
                                           name="order_date"
                                           required
                                           value="<?= $order['order_date'] ?>">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="customer_address" class="form-label fw-bold">Endereço de Entrega</label>
                                    <textarea class="form-control border-0 bg-light rounded-3"
                                              id="customer_address"
                                              name="customer_address"
                                              rows="2"><?= htmlspecialchars($order['customer_address'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Itens do Pedido -->
                        <div class="mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0 text-primary">
                                    <i class="bi bi-cart me-2"></i>
                                    Itens do Pedido
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" id="addItemBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Adicionar Item
                                </button>
                            </div>

                            <div id="itemsContainer">
                                <!-- Cabeçalho das Colunas (Escondido em Mobile) -->
                                <div class="d-none d-md-flex row px-3 mb-2 text-muted small fw-bold">
                                    <div class="col-md-4">PRODUTO</div>
                                    <div class="col-md-2 text-center">CÓDIGO</div>
                                    <div class="col-md-2 text-center">QUANTIDADE</div>
                                    <div class="col-md-2 text-center">PREÇO UNIT.</div>
                                    <div class="col-md-2 text-end">AÇÕES</div>
                                </div>
                                <?php if (!empty($order['items'])): ?>
                                    <?php foreach ($order['items'] as $index => $item): ?>
                                        <div class="item-row card border-0 bg-white shadow-sm mb-3">
                                            <div class="card-body p-3">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4 mb-2">
                                                        <label class="form-label d-md-none small fw-bold text-muted">PRODUTO</label>
                                                        <input type="text"
                                                               class="form-control border-0 bg-light"
                                                               name="items[<?= $index ?>][product_name]"
                                                               value="<?= htmlspecialchars($item['product_name']) ?>"
                                                               required>
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label class="form-label d-md-none small fw-bold text-muted">CÓDIGO</label>
                                                        <input type="text"
                                                               class="form-control border-0 bg-light text-center"
                                                               name="items[<?= $index ?>][product_code]"
                                                               value="<?= htmlspecialchars($item['product_code'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label class="form-label d-md-none small fw-bold text-muted">QUANTIDADE</label>
                                                        <input type="number"
                                                               class="form-control border-0 bg-light text-center quantity"
                                                               name="items[<?= $index ?>][quantity]"
                                                               min="1"
                                                               value="<?= $item['quantity'] ?>"
                                                               required>
                                                    </div>
                                                    <div class="col-md-2 mb-2">
                                                        <label class="form-label d-md-none small fw-bold text-muted">PREÇO UNIT.</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text border-0 bg-light small text-muted">R$</span>
                                                            <input type="text"
                                                                   class="form-control border-0 bg-light price"
                                                                   name="items[<?= $index ?>][unit_price]"
                                                                   value="<?= number_format($item['unit_price'], 2, ',', '.') ?>"
                                                                   required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 mb-2 text-end">
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item rounded-pill">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                    <div class="col-12">
                                                        <hr class="my-2 d-md-none">
                                                        <textarea class="form-control border-0 bg-light mt-2 small"
                                                                  name="items[<?= $index ?>][description]"
                                                                  rows="1"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="card border-0 bg-light mt-3">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-bold">Total do Pedido:</span>
                                            <div class="text-muted small" id="totalItems"><?= count($order['items'] ?? []) ?> itens</div>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="fw-bold text-success mb-0" id="orderTotal">R$ <?= number_format($order['total_amount'], 2, ',', '.') ?></h4>
                                            <small class="text-muted" id="totalCalculated">Valor calculado automaticamente</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seção: Informações Adicionais -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3 text-primary">
                                <i class="bi bi-info-circle me-2"></i>
                                Informações Adicionais
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label fw-bold">Status do Pedido</label>
                                    <select class="form-select border-0 bg-light rounded-3" id="status" name="status">
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $value == ($order['status'] ?? 'pending') ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="delivery_date" class="form-label fw-bold">Data de Entrega Prevista</label>
                                    <input type="date"
                                           class="form-control border-0 bg-light rounded-3"
                                           id="delivery_date"
                                           name="delivery_date"
                                           value="<?= $order['delivery_date'] ?? '' ?>">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="notes" class="form-label fw-bold">Observações</label>
                                    <textarea class="form-control border-0 bg-light rounded-3"
                                              id="notes"
                                              name="notes"
                                              rows="3"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between pt-4 border-top">
                            <div>
                                <a href="/index.php?url=<?= obfuscateUrl('order/view/' . $order['id']) ?>" class="btn btn-outline-secondary px-4 rounded-pill me-2">
                                    <i class="bi bi-x-lg me-1"></i> Cancelar
                                </a>
                                <a href="/index.php?url=<?= obfuscateUrl('order/delete/' . $order['id']) ?>"
                                   class="btn btn-outline-danger px-4 rounded-pill"
                                   onclick="return confirm('Tem certeza que deseja arquivar este pedido?')">
                                    <i class="bi bi-archive me-1"></i> Arquivar
                                </a>
                            </div>
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-save me-1"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <div class="card border-0 bg-light rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3">Informações do Sistema</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Status Atual</small>
                                <div class="mt-1">
                                    <?php $config = $statusConfig[$order['status']] ?? $statusConfig['pending']; ?>
                                    <span class="badge <?= $config['class'] ?> px-3 py-2 rounded-pill">
                                    <i class="bi <?= $config['icon'] ?> me-1"></i>
                                    <?= $statusOptions[$order['status']] ?? $order['status'] ?>
                                </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Número do Pedido</small>
                                <div class="fw-bold">
                                    <i class="bi bi-hash me-1"></i>
                                    <?= $order['order_number'] ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Data de Criação</small>
                                <div class="fw-bold">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Última Atualização</small>
                                <div class="fw-bold">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">ID do Pedido</small>
                                <div class="fw-bold">
                                    <i class="bi bi-hash me-1"></i>
                                    <?= $order['id'] ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <small class="text-muted d-block">Responsável</small>
                                <div class="fw-bold">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($order['username']) ?>
                                </div>
                            </div>
                            <?php if ($order['deleted_at']): ?>
                                <div class="col-md-6 mb-3">
                                    <small class="text-muted d-block">Arquivado em</small>
                                    <div class="fw-bold text-danger">
                                        <i class="bi bi-archive me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($order['deleted_at'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Template para novo item -->
    <template id="itemTemplate">
        <div class="item-row card border-0 bg-white shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-2">
                        <label class="form-label d-md-none small fw-bold text-muted">PRODUTO</label>
                        <input type="text"
                               class="form-control border-0 bg-light"
                               name="items[ITEM_INDEX][product_name]"
                               placeholder="Nome do produto"
                               required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label d-md-none small fw-bold text-muted">CÓDIGO</label>
                        <input type="text"
                               class="form-control border-0 bg-light text-center"
                               name="items[ITEM_INDEX][product_code]"
                               placeholder="Ex: 001">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label d-md-none small fw-bold text-muted">QUANTIDADE</label>
                        <input type="number"
                               class="form-control border-0 bg-light text-center quantity"
                               name="items[ITEM_INDEX][quantity]"
                               min="1"
                               value="1"
                               required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label d-md-none small fw-bold text-muted">PREÇO UNIT.</label>
                        <div class="input-group">
                            <span class="input-group-text border-0 bg-light small text-muted">R$</span>
                            <input type="text"
                                   class="form-control border-0 bg-light price"
                                   name="items[ITEM_INDEX][unit_price]"
                                   placeholder="0,00"
                                   required>
                        </div>
                    </div>
                    <div class="col-md-2 mb-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-item rounded-pill">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                    <div class="col-12">
                        <hr class="my-2 d-md-none">
                        <textarea class="form-control border-0 bg-light mt-2 small"
                                  name="items[ITEM_INDEX][description]"
                                  rows="1"
                                  placeholder="Descrição do item (opcional)"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </template>

<?php
$content = ob_get_clean();
include_once __DIR__ . '/../layouts/main.php';
?>